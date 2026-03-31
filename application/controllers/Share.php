<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Share extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['url', 'api']);
        $this->load->model('Event_model', 'EventModel');
        $this->load->model('Post_model', 'PostModel');
        $this->load->model('Fundraiser_model', 'FundraiserModel');
        $this->load->model('Local_business_model', 'BusinessModel');
        $this->load->model('Local_product_model', 'ProductModel');
        $this->load->model('Meeting_minute_model', 'MinutesModel');
    }

    private function resolve_app_redirect_url(): ?string
    {
        $origin = trim((string) $this->input->get('open_app', true));
        if ($origin === '') {
            return null;
        }

        $parts = parse_url($origin);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
            return null;
        }

        $origin = rtrim($scheme . '://' . $host . (!empty($parts['port']) ? ':' . $parts['port'] : ''), '/');
        $path = parse_url((string) current_url(), PHP_URL_PATH) ?: '/';

        return $origin . $path;
    }

    private function render_page(array $payload): void
    {
        $payload['app_redirect_url'] = $this->resolve_app_redirect_url();
        $this->load->view('share_page', $payload);
    }

    public function event(string $slug = ''): void
    {
        $row = $this->EventModel->find_public_by_slug($slug);
        if (!$row) show_404();
        $this->render_page([
            'title' => $row['title'] ?? 'Kegiatan',
            'description' => $row['description'] ?? 'Detail kegiatan warga.',
            'image' => absolute_url($row['image_url'] ?? null) ?: absolute_url('assets/favicon/web-app-manifest-512x512.png'),
            'meta_url' => current_url(),
            'eyebrow' => 'Kegiatan Warga',
            'meta_lines' => array_values(array_filter([
                !empty($row['event_at']) ? date('d M Y H:i', strtotime($row['event_at'])) : null,
                $row['location'] ?? null,
            ])),
            'body' => $row['description'] ?? '',
        ]);
    }

    public function post(string $slug = ''): void
    {
        $row = $this->PostModel->find_public_by_slug($slug);
        if (!$row || ($row['status'] ?? '') !== 'published') show_404();
        $this->render_page([
            'title' => $row['title'] ?? 'Info Warga',
            'description' => $row['content'] ?? 'Detail informasi warga.',
            'image' => absolute_url($row['image_url'] ?? null) ?: absolute_url('assets/favicon/web-app-manifest-512x512.png'),
            'meta_url' => current_url(),
            'eyebrow' => 'Info Warga',
            'meta_lines' => array_values(array_filter([
                !empty($row['created_at']) ? date('d M Y H:i', strtotime($row['created_at'])) : null,
                $row['category'] ?? null,
            ])),
            'body' => $row['content'] ?? '',
        ]);
    }

    public function fundraiser(string $slug = ''): void
    {
        $row = $this->FundraiserModel->find_public_by_slug($slug);
        if (!$row) show_404();
        $this->render_page([
            'title' => $row['title'] ?? 'Program Donasi',
            'description' => $row['description'] ?? 'Detail program donasi warga.',
            'image' => absolute_url($row['image_url'] ?? null) ?: absolute_url('assets/favicon/web-app-manifest-512x512.png'),
            'meta_url' => current_url(),
            'eyebrow' => 'Program Donasi',
            'meta_lines' => array_values(array_filter([
                isset($row['target_amount']) ? 'Target Rp ' . number_format((float)$row['target_amount'], 0, ',', '.') : null,
                isset($row['collected_amount']) ? 'Terkumpul Rp ' . number_format((float)$row['collected_amount'], 0, ',', '.') : null,
            ])),
            'body' => $row['description'] ?? '',
        ]);
    }

    public function business(string $slug = ''): void
    {
        $row = $this->BusinessModel->find_public_by_slug($slug);
        if (!$row || ($row['status'] ?? '') !== 'active') show_404();
        $products = $this->ProductModel->list_by_business((int)$row['id'], 'active');
        $firstProduct = $products[0] ?? [];
        $this->render_page([
            'title' => $row['name'] ?? 'Usaha Warga',
            'description' => $row['description'] ?? 'Detail usaha warga dan produk yang tersedia.',
            'image' => absolute_url($firstProduct['image_url'] ?? null) ?: absolute_url('assets/favicon/web-app-manifest-512x512.png'),
            'meta_url' => current_url(),
            'eyebrow' => 'Usaha Warga',
            'meta_lines' => array_values(array_filter([
                $row['category'] ?? null,
                $row['house_code'] ?? null,
            ])),
            'body' => $row['description'] ?? '',
        ]);
    }

    public function meeting_minute(string $slug = ''): void
    {
        $row = $this->MinutesModel->find_public_by_slug($slug);
        if (!$row || ($row['status'] ?? '') !== 'published') show_404();
        $this->render_page([
            'title' => $row['title'] ?? 'Notulen Rapat',
            'description' => $row['summary'] ?? 'Ringkasan hasil rapat warga.',
            'image' => absolute_url('assets/favicon/web-app-manifest-512x512.png'),
            'meta_url' => current_url(),
            'eyebrow' => 'Notulen Rapat',
            'meta_lines' => array_values(array_filter([
                !empty($row['meeting_at']) ? date('d M Y H:i', strtotime($row['meeting_at'])) : null,
                $row['location_text'] ?? null,
            ])),
            'body' => $row['summary'] ?? '',
        ]);
    }
}
