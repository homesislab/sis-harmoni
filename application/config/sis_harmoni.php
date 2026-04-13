<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * SIS Harmoni custom config
 */
$config['sis_uploads'] = [
    // auto = pakai Cloudinary jika credential tersedia, fallback ke local.
    // cloudinary = wajib Cloudinary, error jika credential belum lengkap.
    // local = simpan ke uploads/ seperti implementasi lama.
    'driver' => $_ENV['SIS_UPLOAD_DRIVER'] ?? getenv('SIS_UPLOAD_DRIVER') ?: 'auto',

    // absolute path (recommended) OR relative to FCPATH
    'proof_dir' => FCPATH . 'uploads/proofs/',

    // public base url for returned proof urls
    // NOTE: pastikan base_url di config.php sudah benar
    'proof_base_url' => rtrim(base_url('uploads/proofs/'), '/') . '/',

    // limits
    'max_size_kb' => 4096, // 4MB untuk proof/dokumen
    'image_max_size_kb' => 10240, // 10MB, sesuai limit image Cloudinary free plan
    'allowed_ext' => ['jpg','jpeg','png','webp','pdf'],

    // optional: allow using external proof URL (e.g. cloudinary) via whitelist
    'proof_url_whitelist_domains' => [
        // contoh:
        'res.cloudinary.com',
        // 'storage.googleapis.com',
        // 'your-cdn.example.com',
    ],

    'cloudinary' => [
        'cloud_name' => $_ENV['CLOUDINARY_CLOUD_NAME'] ?? getenv('CLOUDINARY_CLOUD_NAME') ?: '',
        'api_key' => $_ENV['CLOUDINARY_API_KEY'] ?? getenv('CLOUDINARY_API_KEY') ?: '',
        'api_secret' => $_ENV['CLOUDINARY_API_SECRET'] ?? getenv('CLOUDINARY_API_SECRET') ?: '',
        'folder' => $_ENV['CLOUDINARY_FOLDER'] ?? getenv('CLOUDINARY_FOLDER') ?: 'sis-harmoni',
        'delivery_transform' => $_ENV['CLOUDINARY_DELIVERY_TRANSFORM'] ?? getenv('CLOUDINARY_DELIVERY_TRANSFORM') ?: 'f_auto,q_auto:eco,c_limit,w_1600',
    ],
];
