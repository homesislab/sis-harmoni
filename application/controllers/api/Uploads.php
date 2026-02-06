<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Uploads extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();
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

        $maxBytes = 20 * 1024 * 1024; // 20MB
        $size = isset($file['size']) ? (int)$file['size'] : 0;
        if ($size > $maxBytes) {
            api_validation_error(['file' => 'File terlalu besar. Maksimal 20MB.']);
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
        if ($finfo) finfo_close($finfo);

        $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
        if ($mime && !in_array($mime, $allowedMime, true)) {
            api_validation_error(['file' => 'Tipe file tidak valid.']);
            return;
        }

        $dir = rtrim(FCPATH, '/').'/uploads/images';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $safe = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo((string)$file['name'], PATHINFO_FILENAME));
        $safe = substr($safe, 0, 60);

        $name = date('Ymd_His').'_'.substr(md5(uniqid('', true)), 0, 10).'_'.$safe.'.'.$ext;
        $path = $dir.'/'.$name;

        if (!@move_uploaded_file($file['tmp_name'], $path)) {
            api_error('UPLOAD_FAILED', 'Gagal menyimpan file', 500);
            return;
        }

        $rel = 'uploads/images/'.$name;
        $base = rtrim((string)config_item('base_url'), '/').'/';

        api_ok([
            'url' => $base.$rel,
            'path' => $rel,
        ], null, 201);
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
