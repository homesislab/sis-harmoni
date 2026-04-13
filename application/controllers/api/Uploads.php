<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Uploads extends MY_Controller
{
    private array $uploadConfig = [];

    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();
        $this->config->load('sis_harmoni', true);
        $this->uploadConfig = $this->config->item('sis_uploads', 'sis_harmoni') ?: [];
    }

    public function image(): void
    {
        if (empty($_FILES) || empty($_FILES['file'])) {
            api_validation_error(['file' => 'File wajib diisi (form-data key: file).']);
            return;
        }

        $file = $_FILES['file'];

        $errCode = isset($file['error']) ? (int)$file['error'] : 0;
        if ($errCode !== UPLOAD_ERR_OK) {
            api_validation_error(['file' => $this->upload_error_message($errCode)]);
            return;
        }

        if (empty($file['tmp_name']) || !is_string($file['tmp_name'])) {
            api_validation_error(['file' => 'Upload tidak valid (tmp_name kosong).']);
            return;
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            api_validation_error(['file' => 'Upload tidak valid. Pastikan request multipart/form-data.']);
            return;
        }

        $maxKb = (int)($this->uploadConfig['image_max_size_kb'] ?? 10240);
        $maxBytes = max($maxKb, 1) * 1024;
        $size = isset($file['size']) ? (int)$file['size'] : 0;
        if ($size > $maxBytes) {
            api_validation_error(['file' => 'File terlalu besar. Maksimal '.ceil($maxBytes / 1024 / 1024).'MB.']);
            return;
        }

        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        $allowedExt = ['jpg','jpeg','png','webp'];
        if (!in_array($ext, $allowedExt, true)) {
            api_validation_error(['file' => 'Format harus jpg/jpeg/png/webp']);
            return;
        }

        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
        $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : null;
        if ($finfo) {
            finfo_close($finfo);
        }

        $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
        if ($mime && !in_array($mime, $allowedMime, true)) {
            api_validation_error(['file' => 'Tipe file tidak valid.']);
            return;
        }

        if ($this->should_use_cloudinary()) {
            $uploaded = $this->upload_to_cloudinary($file);
            if ($uploaded !== null) {
                api_ok($uploaded, null, 201);
                return;
            }
        }

        if ($this->upload_driver() === 'cloudinary') {
            api_error('UPLOAD_FAILED', 'Cloudinary belum siap. Periksa konfigurasi CLOUDINARY_* di server.', 500);
            return;
        }

        $stored = $this->store_local($file, $ext);
        if ($stored === null) {
            api_error('UPLOAD_FAILED', 'Gagal menyimpan file', 500);
            return;
        }

        api_ok($stored, null, 201);
    }

    public function cloudinary_signature(): void
    {
        $driver = $this->upload_driver();
        $cloudinary = $this->uploadConfig['cloudinary'] ?? [];

        if ($driver === 'local' || ($driver === 'auto' && !$this->cloudinary_configured())) {
            api_ok(['enabled' => false]);
            return;
        }

        if (!$this->cloudinary_configured()) {
            api_error('CLOUDINARY_NOT_CONFIGURED', 'Cloudinary belum dikonfigurasi di server.', 500);
            return;
        }

        $timestamp = time();
        $folder = trim((string)($cloudinary['folder'] ?? 'sis-harmoni'), '/');
        $params = [
            'folder' => $folder,
            'timestamp' => $timestamp,
        ];

        $signature = $this->cloudinary_signature_for($params, (string)$cloudinary['api_secret']);

        api_ok([
            'enabled' => true,
            'cloud_name' => $cloudinary['cloud_name'],
            'api_key' => $cloudinary['api_key'],
            'folder' => $folder,
            'timestamp' => $timestamp,
            'signature' => $signature,
            'delivery_transform' => $cloudinary['delivery_transform'] ?? '',
            'upload_url' => 'https://api.cloudinary.com/v1_1/'.$cloudinary['cloud_name'].'/image/upload',
        ]);
    }

    private function should_use_cloudinary(): bool
    {
        $driver = $this->upload_driver();
        if ($driver === 'cloudinary') {
            return true;
        }

        if ($driver !== 'auto') {
            return false;
        }

        return $this->cloudinary_configured();
    }

    private function cloudinary_configured(): bool
    {
        $cloudinary = $this->uploadConfig['cloudinary'] ?? [];
        return !empty($cloudinary['cloud_name'])
            && !empty($cloudinary['api_key'])
            && !empty($cloudinary['api_secret']);
    }

    private function upload_driver(): string
    {
        $driver = strtolower((string)($this->uploadConfig['driver'] ?? 'auto'));
        return in_array($driver, ['auto', 'cloudinary', 'local'], true) ? $driver : 'auto';
    }

    private function upload_to_cloudinary(array $file): ?array
    {
        $this->load->library('cloudinary_uploader', $this->uploadConfig['cloudinary'] ?? []);

        try {
            return $this->cloudinary_uploader->upload_image($file['tmp_name'], (string)$file['name']);
        } catch (Throwable $e) {
            log_message('error', 'Cloudinary upload failed: ' . $e->getMessage());
            return null;
        }
    }

    private function cloudinary_signature_for(array $params, string $apiSecret): string
    {
        ksort($params);

        $parts = [];
        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $parts[] = $key . '=' . $value;
        }

        return sha1(implode('&', $parts) . $apiSecret);
    }

    private function store_local(array $file, string $ext): ?array
    {
        $dir = rtrim(FCPATH, '/').'/uploads/images';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $safe = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo((string)$file['name'], PATHINFO_FILENAME));
        $safe = substr($safe, 0, 60);

        $name = date('Ymd_His').'_'.substr(md5(uniqid('', true)), 0, 10).'_'.$safe.'.'.$ext;
        $path = $dir.'/'.$name;

        if (!@move_uploaded_file($file['tmp_name'], $path)) {
            return null;
        }

        $rel = 'uploads/images/'.$name;
        $base = rtrim((string)config_item('base_url'), '/').'/';

        return [
            'url' => $base.$rel,
            'path' => $rel,
            'provider' => 'local',
        ];
    }

    private function upload_error_message(int $code): string
    {
        switch ($code) {
            case UPLOAD_ERR_OK: return 'OK';
            case UPLOAD_ERR_INI_SIZE: return 'File melebihi batas upload server.';
            case UPLOAD_ERR_FORM_SIZE: return 'File melebihi batas upload form.';
            case UPLOAD_ERR_PARTIAL: return 'File terupload sebagian. Coba lagi.';
            case UPLOAD_ERR_NO_FILE: return 'Tidak ada file yang diupload.';
            case UPLOAD_ERR_NO_TMP_DIR: return 'Folder temporary tidak tersedia di server.';
            case UPLOAD_ERR_CANT_WRITE: return 'Gagal menulis file ke disk.';
            case UPLOAD_ERR_EXTENSION: return 'Upload dihentikan oleh extension PHP.';
            default: return 'Upload gagal.';
        }
    }
}
