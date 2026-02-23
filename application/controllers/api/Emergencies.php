<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Emergencies extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();
        $this->load->model('Emergency_report_model', 'EmergencyModel');
        $this->load->model('Fcm_token_model', 'TokenModel');
        $this->load->model('House_model', 'HouseModel');
        $this->load->model('Person_model', 'PersonModel');
        $this->load->library('whatsapp');
    }

    public function index(): void
    {
        $this->require_permission('app.services.security.emergencies.manage');

        $p = $this->get_pagination_params();
        $page = $p['page'];
        $per  = $p['per_page'];

        $filters = [
            'q' => $this->_get_q(),
            'status' => $this->input->get('status') ? (string)$this->input->get('status') : null,
            'type' => $this->input->get('type') ? (string)$this->input->get('type') : null,
        ];

        $res = $this->EmergencyModel->paginate($page, $per, $filters);
        api_ok(['items' => $res['items']], $res['meta']);
    }

    public function store(): void
    {
        $in = $this->json_input();
        $type = $in['type'] ?? 'other';
        $allowed = ['medical','fire','crime','accident','lost_child','other'];
        if (!in_array($type, $allowed, true)) {
            api_validation_error(['type' => 'Nilai tidak valid']);
            return;
        }

        $payload = $in;
        $payload['type'] = $type;
        $payload['created_by'] = (int)$this->auth_user['id'];
        $payload['reporter_person_id'] = (int)($this->auth_user['person_id'] ?? 0) ?: null;
        if (empty($payload['house_id']) && !empty($this->auth_house_id)) {
            $payload['house_id'] = (int)$this->auth_house_id;
        }

        $id = $this->EmergencyModel->create($payload);
        $record = $this->EmergencyModel->find_by_id($id);

        // Send Push Notifications
        $this->_send_panic_push($record);

        // Send WA Notification
        $admin_wa = $this->whatsapp->get_admin_security();
        if ($admin_wa) {
            $type_labels = [
                'medical' => 'Darurat Medis',
                'fire' => 'Kebakaran',
                'crime' => 'Tindak Kriminal',
                'accident' => 'Kecelakaan',
                'lost_child' => 'Anak Hilang',
                'other' => 'Darurat Lainnya'
            ];
            $label = $type_labels[$record['type']] ?? 'Darurat';
            $loc_text = $record['location_text'] ?: 'Lokasi Tidak Diketahui';
            
            $reporter_name = 'Warga';
            $unit_str = '';
            
            if (!empty($record['reporter_person_id'])) {
                $person_id = (int)$record['reporter_person_id'];
                $person = $this->PersonModel->find_by_id($person_id);
                if ($person && !empty($person['full_name'])) {
                    $reporter_name = trim($person['full_name']);
                    
                    $sql = "
                        SELECT hs.block, hs.number 
                        FROM household_members hm
                        JOIN house_occupancies oc ON oc.household_id = hm.household_id AND oc.status = 'active'
                        JOIN houses hs ON hs.id = oc.house_id
                        WHERE hm.person_id = ?
                        ORDER BY hm.id DESC, oc.start_date DESC
                        LIMIT 1
                    ";
                    $q = $this->db->query($sql, [$person_id]);
                    if ($q && $q->num_rows() > 0) {
                        $r = $q->row_array();
                        $unit_str = trim(($r['block'] ?? '') . '-' . ($r['number'] ?? ''));
                    }
                }
            }
            
            if ($unit_str === '' && !empty($record['house_id'])) {
                $h = $this->HouseModel->find_by_id($record['house_id']);
                if ($h) {
                    $unit_str = trim(($h['block'] ?? '') . '-' . ($h['number'] ?? ''));
                }
            }
            
            if ($unit_str !== '' && $reporter_name !== 'Warga') {
                $loc_text = "{$reporter_name}, unit {$unit_str}";
            } elseif ($unit_str !== '') {
                $loc_text = "Unit {$unit_str}";
            } elseif ($reporter_name !== 'Warga') {
                $loc_text = "{$reporter_name} (Lokasi Tidak Diketahui)";
            }
            
            $wa_msg = "*[Info SIS]*\n\n🚨 *PANIC BUTTON DITEKAN!* 🚨\n\nJenis Darurat: *{$label}*\nLokasi/Warga: *{$loc_text}*\n\nMohon tim keamanan segera merapat ke lokasi sekarang juga!";
            $this->whatsapp->send_message($admin_wa, $wa_msg);
        }

        api_ok($record, null, 201);
    }

    private function _send_panic_push(array $emergency): void
    {
        $sender_id = (int)$this->auth_user['id'];
        
        log_message('error', "FCM Trace: Starting push for Emergency ID " . $emergency['id'] . ", Sender ID: " . $sender_id);
    
        $tokens = $this->TokenModel->get_tokens_except_user($sender_id);
        
        if (empty($tokens)) {
            log_message('error', "FCM Trace: ABORTED! No other user tokens found to send the push to. Sender ID was: " . $sender_id);
            return;
        }

        log_message('error', "FCM Trace: Found " . count($tokens) . " tokens. Preparing payload.");

        $type_labels = [
            'medical' => 'Darurat Medis',
            'fire' => 'Kebakaran',
            'crime' => 'Tindak Kriminal',
            'accident' => 'Kecelakaan',
            'lost_child' => 'Anak Hilang',
            'other' => 'Darurat Lainnya'
        ];
        
        $label = $type_labels[$emergency['type']] ?? 'Darurat';
        
        // Build address / reporter info
        $loc_text = $emergency['location_text'] ?: 'Lokasi Tidak Diketahui';
        
        $reporter_name = 'Warga';
        $unit_str = '';
        
        $this->load->model('Person_model');
        if (!empty($emergency['reporter_person_id'])) {
            $person_id = (int)$emergency['reporter_person_id'];
            $person = $this->Person_model->find_by_id($person_id);
            if ($person && !empty($person['full_name'])) {
                $reporter_name = trim($person['full_name']);
                
                // Fetch the unit from their active household occupancy
                $sql = "
                    SELECT hs.block, hs.number 
                    FROM household_members hm
                    JOIN house_occupancies oc ON oc.household_id = hm.household_id AND oc.status = 'active'
                    JOIN houses hs ON hs.id = oc.house_id
                    WHERE hm.person_id = ?
                    ORDER BY hm.id DESC, oc.start_date DESC
                    LIMIT 1
                ";
                $q = $this->db->query($sql, [$person_id]);
                if ($q && $q->num_rows() > 0) {
                    $r = $q->row_array();
                    $unit_str = trim(($r['block'] ?? '') . '-' . ($r['number'] ?? ''));
                }
            }
        }
        
        // Fallback to emergency house_id if the direct mapping failed
        if ($unit_str === '' && !empty($emergency['house_id'])) {
            $h = $this->HouseModel->find_by_id($emergency['house_id']);
            if ($h) {
                $unit_str = trim(($h['block'] ?? '') . '-' . ($h['number'] ?? ''));
            }
        }
        
        if ($unit_str !== '' && $reporter_name !== 'Warga') {
            $loc_text = "{$reporter_name}, unit {$unit_str}";
        } elseif ($unit_str !== '') {
            $loc_text = "Unit {$unit_str}";
        } elseif ($reporter_name !== 'Warga') {
            $loc_text = "{$reporter_name} (Lokasi Tidak Diketahui)";
        }

        $title = "🚨 PANIC: {$label} 🚨";
        $body = "Lokasi: {$loc_text}\nBuka aplikasi sekarang untuk info lanjut.";

        log_message('error', "FCM Trace: Payload ready. Title: [{$title}]. Attempting Firebase SDK load.");

        try {
            $factory = (new \Kreait\Firebase\Factory)
                ->withServiceAccount(APPPATH . 'config/firebase-service-account.json');
            
            $messaging = $factory->createMessaging();
            
            log_message('error', "FCM Trace: SDK Loaded successfully. Sending chunk...");

            foreach ($tokens as $token) {
                try {
                    $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token', $token)
                        ->withNotification(\Kreait\Firebase\Messaging\Notification::create($title, $body))
                        ->withWebPushConfig([
                            'notification' => [
                                'title' => $title,
                                'body' => $body,
                                'icon' => '/icons/icon-192x192.png',
                                'requireInteraction' => true,
                                'vibrate' => [500, 250, 500, 250, 500, 250, 500],
                            ],
                            'fcm_options' => [
                                'link' => '/community/emergencies'
                            ]
                        ])
                        ->withData([
                            'type' => 'panic_button',
                            'emergency_id' => (string)$emergency['id'],
                            'emergency_type' => $emergency['type'],
                            'location_text' => $loc_text,
                            'timestamp' => (string)time()
                        ]);

                    $messaging->send($message);
                    log_message('error', "FCM Trace: Push sent successfully to token: " . substr($token, 0, 15) . "...");
                } catch (\Exception $subE) {
                    log_message('error', 'FCM Panic Push chunk failed for token ' . substr($token, 0, 15) . '... Error: ' . $subE->getMessage());
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'FCM Panic Push Failed at factory level: ' . $e->getMessage());
        }
    }

    public function acknowledge(int $id = 0): void
    {
        $this->require_permission('app.services.security.emergencies.manage');
        if ($id <= 0) {
            api_not_found();
            return;
        }
        $row = $this->EmergencyModel->find_by_id($id);
        if (!$row) {
            api_not_found();
            return;
        }

        $this->EmergencyModel->update($id, [
            'status' => 'acknowledged',
            'acknowledged_by' => (int)$this->auth_user['id'],
            'acknowledged_at' => date('Y-m-d H:i:s'),
        ]);
        api_ok($this->EmergencyModel->find_by_id($id));
    }

    public function resolve(int $id = 0): void
    {
        $this->require_permission('app.services.security.emergencies.manage');
        if ($id <= 0) {
            api_not_found();
            return;
        }
        $row = $this->EmergencyModel->find_by_id($id);
        if (!$row) {
            api_not_found();
            return;
        }

        $in = $this->json_input();
        $this->EmergencyModel->update($id, [
            'status' => 'resolved',
            'resolved_by' => (int)$this->auth_user['id'],
            'resolved_at' => date('Y-m-d H:i:s'),
            'resolution_note' => $in['resolution_note'] ?? null,
        ]);
        api_ok($this->EmergencyModel->find_by_id($id));
    }

    public function cancel(int $id = 0): void
    {
        if ($id <= 0) {
            api_not_found();
            return;
        }
        $row = $this->EmergencyModel->find_by_id($id);
        if (!$row) {
            api_not_found();
            return;
        }

        $can_manage = $this->has_permission('app.services.security.emergencies.manage');
        $pid = (int)($this->auth_user['person_id'] ?? 0);
        if (!$can_manage && (int)($row['reporter_person_id'] ?? 0) !== $pid) {
            api_error('FORBIDDEN', 'Akses ditolak', 403);
            return;
        }

        $this->EmergencyModel->update($id, ['status' => 'cancelled']);
        api_ok($this->EmergencyModel->find_by_id($id));
    }

    private function _get_q(): ?string
    {
        $q = $this->input->get('q');
        $q = is_string($q) ? trim($q) : '';
        return $q !== '' ? $q : null;
    }
}
