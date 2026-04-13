<?php

defined('BASEPATH') or exit('No direct script access allowed');

use Cloudinary\Cloudinary;

class Cloudinary_uploader
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function is_configured(): bool
    {
        return !empty($this->config['cloud_name'])
            && !empty($this->config['api_key'])
            && !empty($this->config['api_secret']);
    }

    public function upload_image(string $tmpPath, string $originalName): array
    {
        if (!$this->is_configured()) {
            throw new RuntimeException('Cloudinary belum dikonfigurasi.');
        }

        if (!class_exists(Cloudinary::class)) {
            throw new RuntimeException('Cloudinary PHP SDK belum tersedia. Jalankan composer install.');
        }

        $cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => $this->config['cloud_name'],
                'api_key' => $this->config['api_key'],
                'api_secret' => $this->config['api_secret'],
            ],
            'url' => [
                'secure' => true,
            ],
        ]);

        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
        $safeName = trim(substr((string)$safeName, 0, 60), '_');
        $publicId = date('Ymd_His') . '_' . substr(md5(uniqid('', true)), 0, 10);
        if ($safeName !== '') {
            $publicId .= '_' . $safeName;
        }

        $result = $cloudinary->uploadApi()->upload($tmpPath, [
            'resource_type' => 'image',
            'folder' => trim((string)($this->config['folder'] ?? 'sis-harmoni'), '/'),
            'public_id' => $publicId,
            'overwrite' => false,
            'unique_filename' => false,
            'use_filename' => false,
            'quality_analysis' => true,
        ]);

        $secureUrl = (string)($result['secure_url'] ?? '');
        $publicId = (string)($result['public_id'] ?? '');

        if ($secureUrl === '' || $publicId === '') {
            throw new RuntimeException('Cloudinary tidak mengembalikan URL upload.');
        }

        $optimizedUrl = $this->build_optimized_url($secureUrl);

        return [
            'url' => $optimizedUrl,
            'secure_url' => $optimizedUrl,
            'original_url' => $secureUrl,
            'path' => $publicId,
            'public_id' => $publicId,
            'provider' => 'cloudinary',
            'bytes' => isset($result['bytes']) ? (int)$result['bytes'] : null,
            'format' => $result['format'] ?? null,
            'width' => isset($result['width']) ? (int)$result['width'] : null,
            'height' => isset($result['height']) ? (int)$result['height'] : null,
        ];
    }

    private function build_optimized_url(string $secureUrl): string
    {
        $transform = trim((string)($this->config['delivery_transform'] ?? ''));
        if ($transform === '') {
            return $secureUrl;
        }

        return str_replace('/image/upload/', '/image/upload/' . $transform . '/', $secureUrl);
    }
}
