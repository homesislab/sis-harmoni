<?php

defined('BASEPATH') or exit('No direct script access allowed');

class OfflineResidents extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['url', 'html']);
        $this->load->model('Offline_resident_model', 'OfflineResidentModel');
        $this->load->library('whatsapp');
    }

    public function index(): void
    {
        $this->guard();

        $status = trim((string)$this->input->get('status', true));
        $q = trim((string)$this->input->get('q', true));
        $data = $this->OfflineResidentModel->dashboard($status, $q);
        $data['template'] = $this->OfflineResidentModel->default_template();
        $data['page_url'] = site_url('admin/offline-residents');
        $data['export_url'] = site_url('admin/offline-residents/export');
        $data['send_url'] = site_url('admin/offline-residents/send') . $this->key_query_suffix();

        $this->load->view('offline_residents', $data);
    }

    public function send(): void
    {
        $this->guard();

        header('Content-Type: application/json; charset=utf-8');

        $in = $this->json_input();
        $template = trim((string)($in['template'] ?? ''));
        if ($template === '') {
            $template = $this->OfflineResidentModel->default_template();
        }

        $unitKeys = $in['unit_keys'] ?? [];
        if (!is_array($unitKeys)) {
            $unitKeys = [];
        }
        $unitKeys = array_values(array_unique(array_map('strval', $unitKeys)));

        $status = trim((string)($in['status'] ?? 'unregistered'));
        $q = trim((string)($in['q'] ?? ''));
        $limit = max(1, min(100, (int)($in['limit'] ?? 100)));
        $dryRun = !empty($in['dry_run']);
        $includeRegistered = !empty($in['include_registered']);

        $data = $this->OfflineResidentModel->dashboard($status, $q);
        $items = $data['items'];
        if ($unitKeys) {
            $lookup = array_flip($unitKeys);
            $items = array_values(array_filter($items, fn ($row) => isset($lookup[(string)$row['unit_key']])));
        }

        $targets = [];
        foreach ($items as $row) {
            if (empty($row['whatsapp_number'])) {
                continue;
            }
            if (!$includeRegistered && !empty($row['is_registered'])) {
                continue;
            }
            $targets[] = $row;
            if (count($targets) >= $limit) {
                break;
            }
        }

        $sent = [];
        $failed = [];
        foreach ($targets as $row) {
            $message = $this->render_template($template, $row);
            $ok = $dryRun ? true : $this->whatsapp->send_message((string)$row['whatsapp_number'], $message);
            $entry = [
                'unit_key' => $row['unit_key'],
                'unit_code' => $row['unit_code'],
                'name' => $this->target_name($row),
                'phone' => $row['whatsapp_number'],
            ];
            if ($ok) {
                $sent[] = $entry;
            } else {
                $failed[] = $entry;
            }
        }

        echo json_encode([
            'ok' => empty($failed),
            'sent_count' => count($sent),
            'failed_count' => count($failed),
            'sent' => $sent,
            'failed' => $failed,
        ], JSON_UNESCAPED_UNICODE);
    }

    public function export(): void
    {
        $this->guard();

        $status = trim((string)$this->input->get('status', true)) ?: 'unregistered';
        $q = trim((string)$this->input->get('q', true));
        $data = $this->OfflineResidentModel->dashboard($status, $q);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="offline-residents-' . date('Ymd-His') . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, [
            'unit',
            'status',
            'nama_csv',
            'suami',
            'istri',
            'whatsapp',
            'kk_database',
            'kepala_keluarga_database',
            'jumlah_akun_aktif',
            'akun_terdaftar',
            'unit_database',
        ]);

        foreach ($data['items'] as $row) {
            fputcsv($out, [
                $row['unit_code'],
                $row['status_label'],
                $row['csv_display_name'],
                $row['csv_suami'],
                $row['csv_istri'],
                $row['whatsapp_number'],
                $row['db_kk_number'],
                $row['db_head_name'],
                $row['active_user_count'],
                implode(', ', $row['registered_usernames']),
                $row['match_label'],
            ]);
        }

        fclose($out);
    }

    private function guard(): void
    {
        $requiredKey = trim((string)getenv('OFFLINE_RESIDENT_ADMIN_KEY'));
        if ($requiredKey === '') {
            return;
        }

        $givenKey = trim((string)$this->input->get('key', true));
        if (!hash_equals($requiredKey, $givenKey)) {
            show_error('Akses ditolak.', 403, 'Forbidden');
        }
    }

    private function key_query_suffix(): string
    {
        $key = trim((string)$this->input->get('key', true));
        return $key !== '' ? '?' . http_build_query(['key' => $key]) : '';
    }

    private function json_input(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '[]', true);
        return is_array($data) ? $data : [];
    }

    private function render_template(string $template, array $row): string
    {
        $replace = [
            '{nama}' => $this->target_name($row),
            '{unit}' => (string)($row['unit_code'] ?? ''),
            '{unit_db}' => (string)($row['matched_unit_code'] ?: ($row['unit_code'] ?? '')),
            '{suami}' => (string)($row['csv_suami'] ?? ''),
            '{istri}' => (string)($row['csv_istri'] ?? ''),
            '{kk}' => (string)($row['db_kk_number'] ?? ''),
        ];

        return strtr($template, $replace);
    }

    private function target_name(array $row): string
    {
        foreach (['csv_display_name', 'csv_suami', 'csv_istri', 'db_head_name'] as $key) {
            $value = trim((string)($row[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }
        return 'Unit ' . (string)($row['unit_code'] ?? '');
    }
}
