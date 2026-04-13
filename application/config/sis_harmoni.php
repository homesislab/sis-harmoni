<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * SIS Harmoni custom config
 */
$config['sis_uploads'] = [
    // absolute path (recommended) OR relative to FCPATH
    'proof_dir' => FCPATH . 'uploads/proofs/',

    // public base url for returned proof urls
    // NOTE: pastikan base_url di config.php sudah benar
    'proof_base_url' => rtrim(base_url('uploads/proofs/'), '/') . '/',

    // limits
    'max_size_kb' => 4096, // 4MB
    'allowed_ext' => ['jpg','jpeg','png','webp','pdf'],

    // optional: allow using external proof URL (e.g. cloudinary) via whitelist
    'proof_url_whitelist_domains' => [
        // contoh:
        // 'res.cloudinary.com',
        // 'storage.googleapis.com',
        // 'your-cdn.example.com',
    ],
];
