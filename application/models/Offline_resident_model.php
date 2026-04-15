<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Offline_resident_model extends CI_Model
{
    private string $csv_path = APPPATH . 'data/offline_residents.csv';

    public function dashboard(string $status = 'all', string $q = ''): array
    {
        $rows = $this->compare_rows();
        $summary = $this->summarize($rows);

        $status = strtolower(trim($status));
        $q = $this->normalize_text($q);

        $filtered = array_values(array_filter($rows, function ($row) use ($status, $q) {
            if ($status === 'registered' && empty($row['is_registered'])) {
                return false;
            }
            if ($status === 'unregistered' && !empty($row['is_registered'])) {
                return false;
            }
            if ($status === 'not_found' && !empty($row['db_house'])) {
                return false;
            }
            if ($q === '') {
                return true;
            }

            $haystack = $this->normalize_text(implode(' ', [
                $row['unit_code'] ?? '',
                $row['csv_display_name'] ?? '',
                $row['csv_suami'] ?? '',
                $row['csv_istri'] ?? '',
                $row['whatsapp_number'] ?? '',
                $row['db_head_name'] ?? '',
            ]));

            return strpos($haystack, $q) !== false;
        }));

        return [
            'items' => $filtered,
            'summary' => $summary,
            'all_items' => $rows,
            'filters' => [
                'status' => $status ?: 'all',
                'q' => $q,
            ],
            'generated_at' => date('Y-m-d H:i:s'),
            'csv_path' => $this->csv_path,
        ];
    }

    public function compare_rows(): array
    {
        $csvRows = $this->read_csv_rows();
        $dbUnits = $this->db_units_by_key();
        $items = [];

        foreach ($csvRows as $row) {
            $key = $this->unit_key($row['block'], $row['house_number']);
            $candidateKeys = $this->candidate_unit_keys($row['block'], $row['house_number']);
            $matchedKey = '';
            $db = null;
            foreach ($candidateKeys as $candidateKey) {
                if (isset($dbUnits[$candidateKey])) {
                    $matchedKey = $candidateKey;
                    $db = $dbUnits[$candidateKey];
                    break;
                }
            }
            $accountCount = (int)($db['active_user_count'] ?? 0);
            $memberCount = (int)($db['member_count'] ?? 0);
            $isRegistered = $accountCount > 0;

            $items[] = [
                'no' => $row['no'],
                'unit_key' => $key,
                'unit_code' => $this->format_unit($row['block'], $row['house_number']),
                'block' => $row['block'],
                'house_number' => $row['house_number'],
                'matched_unit_key' => $matchedKey,
                'matched_unit_code' => $matchedKey !== '' ? str_replace('-', ' ', $matchedKey) : '',
                'csv_display_name' => $row['display_name'],
                'csv_suami' => $row['suami'],
                'csv_istri' => $row['istri'],
                'whatsapp_number' => $this->normalize_phone($row['whatsapp_number']),
                'raw_whatsapp_number' => $row['whatsapp_number'],
                'db_house' => $db,
                'db_head_name' => $db['head_name'] ?? '',
                'db_kk_number' => $db['kk_number'] ?? '',
                'db_household_id' => (int)($db['household_id'] ?? 0),
                'db_house_id' => (int)($db['house_id'] ?? 0),
                'member_count' => $memberCount,
                'active_user_count' => $accountCount,
                'registered_usernames' => $this->split_csv_value($db['registered_usernames'] ?? ''),
                'registered_names' => $this->split_csv_value($db['registered_names'] ?? ''),
                'is_registered' => $isRegistered,
                'status_label' => $isRegistered ? 'Sudah daftar' : 'Belum daftar',
                'match_label' => $db ? 'Unit ditemukan' : 'Unit belum ditemukan',
            ];
        }

        usort($items, function ($a, $b) {
            $block = strcmp($a['block'], $b['block']);
            if ($block !== 0) {
                return $block;
            }
            return (int)$a['house_number'] <=> (int)$b['house_number'];
        });

        return $items;
    }

    public function default_template(): string
    {
        return trim((string)getenv('OFFLINE_RESIDENT_WA_TEMPLATE')) ?: implode("\n", [
            'Assalamualaikum Bapak/Ibu {nama},',
            '',
            'Kami dari admin SIS Harmoni ingin mengingatkan bahwa unit {unit} belum terdaftar di aplikasi SIS Harmoni.',
            '',
            'Mohon bantu daftar agar data warga, informasi lingkungan, pembayaran, dan layanan warga bisa terhubung rapi.',
            '',
            'Terima kasih.',
        ]);
    }

    private function summarize(array $rows): array
    {
        $total = count($rows);
        $registered = 0;
        $notFound = 0;
        $withWhatsapp = 0;

        foreach ($rows as $row) {
            if (!empty($row['is_registered'])) {
                $registered++;
            }
            if (empty($row['db_house'])) {
                $notFound++;
            }
            if (!empty($row['whatsapp_number'])) {
                $withWhatsapp++;
            }
        }

        $unregistered = max(0, $total - $registered);

        return [
            'total' => $total,
            'registered' => $registered,
            'unregistered' => $unregistered,
            'not_found' => $notFound,
            'with_whatsapp' => $withWhatsapp,
            'registered_percent' => $this->percent($registered, $total),
            'unregistered_percent' => $this->percent($unregistered, $total),
        ];
    }

    private function read_csv_rows(): array
    {
        if (!is_file($this->csv_path)) {
            return [];
        }

        $handle = fopen($this->csv_path, 'r');
        if (!$handle) {
            return [];
        }

        $rows = [];
        $headers = null;
        while (($data = fgetcsv($handle)) !== false) {
            if ($headers === null) {
                $headers = $data;
                continue;
            }
            if (!$data || count(array_filter($data, fn ($v) => trim((string)$v) !== '')) === 0) {
                continue;
            }

            $rows[] = [
                'no' => trim((string)($data[0] ?? '')),
                'block' => strtoupper(trim((string)($data[1] ?? ''))),
                'house_number' => trim((string)($data[2] ?? '')),
                'display_name' => trim((string)($data[3] ?? '')),
                'whatsapp_number' => trim((string)($data[4] ?? '')),
                'suami' => trim((string)($data[6] ?? '')),
                'istri' => trim((string)($data[7] ?? '')),
            ];
        }
        fclose($handle);

        return $rows;
    }

    private function db_units_by_key(): array
    {
        $rows = $this->db
            ->select("
                h.id AS house_id,
                h.block,
                h.number AS house_number,
                h.code AS house_code,
                hh.id AS household_id,
                hh.kk_number,
                p.full_name AS head_name,
                COUNT(DISTINCT hm.person_id) AS member_count,
                COUNT(DISTINCT CASE WHEN u.status = 'active' THEN u.id END) AS active_user_count,
                GROUP_CONCAT(DISTINCT CASE WHEN u.status = 'active' THEN u.username END ORDER BY u.username SEPARATOR ', ') AS registered_usernames,
                GROUP_CONCAT(DISTINCT CASE WHEN u.status = 'active' THEN up.full_name END ORDER BY up.full_name SEPARATOR ', ') AS registered_names
            ", false)
            ->from('houses h')
            ->join('house_occupancies ho', "ho.house_id = h.id AND ho.status = 'active'", 'left', false)
            ->join('households hh', 'hh.id = ho.household_id', 'left')
            ->join('persons p', 'p.id = hh.head_person_id', 'left')
            ->join('household_members hm', 'hm.household_id = hh.id', 'left')
            ->join('users u', '(u.person_id = hm.person_id OR u.person_id = hh.head_person_id)', 'left', false)
            ->join('persons up', 'up.id = u.person_id', 'left')
            ->group_by('h.id, h.block, h.number, h.code, hh.id, hh.kk_number, p.full_name')
            ->get()
            ->result_array();

        $map = [];
        foreach ($rows as $row) {
            $key = $this->unit_key((string)$row['block'], (string)$row['house_number']);
            if ($key !== '') {
                $map[$key] = $row;
            }
        }

        return $map;
    }

    private function unit_key(string $block, string $number): string
    {
        $block = $this->normalize_block($block);
        $number = trim($number);
        $number = ltrim($number, '0');
        if ($number === '') {
            $number = '0';
        }
        return $block . '-' . $number;
    }

    private function format_unit(string $block, string $number): string
    {
        return $this->normalize_block($block) . '-' . trim($number);
    }

    private function candidate_unit_keys(string $block, string $number): array
    {
        $block = $this->normalize_block($block);
        $number = trim($number);
        $candidates = [$this->unit_key($block, $number)];

        $parts = preg_split('/[\/,&]+/', $number);
        if (is_array($parts) && count($parts) > 1) {
            foreach ($parts as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $candidates[] = $this->unit_key($block, $part);
                }
            }
        }

        return array_values(array_unique($candidates));
    }

    private function normalize_block(string $block): string
    {
        $value = strtoupper(trim($block));
        $value = preg_replace('/\s+/', ' ', $value);
        $value = str_replace(['JL. ', 'JALAN '], '', $value);
        if (in_array($value, ['JL UTAMA', 'UTAMA'], true)) {
            return 'UTAMA';
        }
        return $value;
    }

    private function normalize_phone(string $phone): string
    {
        $phone = preg_replace('/[^\d+]/', '', trim($phone));
        if ($phone === '') {
            return '';
        }
        if (strpos($phone, '+') === 0) {
            $phone = substr($phone, 1);
        }
        if (strpos($phone, '0') === 0) {
            return '62' . substr($phone, 1);
        }
        if (strpos($phone, '8') === 0) {
            return '62' . $phone;
        }
        return $phone;
    }

    private function normalize_text(string $value): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $value)));
    }

    private function split_csv_value(?string $value): array
    {
        $value = trim((string)$value);
        if ($value === '') {
            return [];
        }
        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }

    private function percent(int $value, int $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }
        return round(($value / $total) * 100, 1);
    }
}
