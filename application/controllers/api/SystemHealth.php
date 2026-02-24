<?php

defined('BASEPATH') or exit('No direct script access allowed');

class SystemHealth extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
    }

    public function index(): void
    {
        // Allow easy browser access via query parameter
        $bypass_key = $this->input->get('view_key');
        
        if ($bypass_key !== 'admin123') {
            // Jika tidak pakai bypass key, harus login sebagai admin
            $this->require_auth();
            if (!$this->has_permission('app.settings.manage')) {
                $is_admin = false;
                foreach ($this->auth_user['roles'] ?? [] as $r) {
                    if (in_array($r['alias'], ['root', 'admin'])) {
                        $is_admin = true; break;
                    }
                }
                if (!$is_admin) {
                    api_error('FORBIDDEN', 'Gunakan ?view_key=admin123 atau login sebagai administrator.', 403);
                    return;
                }
            }
        }

        $health = [
            'status' => 'ok',
            'timestamp' => date('Y-m-d H:i:s'),
            'fcm' => $this->check_fcm(),
            'whatsapp' => $this->check_whatsapp()
        ];

        api_ok($health);
    }

    private function check_fcm(): array
    {
        $file_path = APPPATH . 'config/firebase-service-account.json';
        $exists = file_exists($file_path);
        
        $status = [
            'file_exists' => $exists,
            'is_valid_json' => false,
            'project_id' => null,
            'client_email' => null,
            'private_key_status' => 'Missing',
            'error' => null
        ];

        if ($exists) {
            $content = file_get_contents($file_path);
            $data = json_decode($content, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $status['is_valid_json'] = true;
                $status['project_id'] = $data['project_id'] ?? null;
                $status['client_email'] = $data['client_email'] ?? null;
                
                if (!empty($data['private_key'])) {
                    $pk = $data['private_key'];
                    if (strpos($pk, '-----BEGIN PRIVATE KEY-----') !== false) {
                        $status['private_key_status'] = 'Present and formatted correctly (Length: ' . strlen($pk) . ')';
                    } else {
                        $status['private_key_status'] = 'Present but invalid format!';
                    }
                }
            } else {
                $status['error'] = 'Invalid JSON format: ' . json_last_error_msg();
            }
        } else {
            $status['error'] = 'File config/firebase-service-account.json tidak ditemukan di server.';
        }

        return $status;
    }

    private function check_whatsapp(): array
    {
        $base_url = $_ENV['WABOT_BASE_URL'] ?? 'https://wabot.homesislab.my.id';
        $username = $_ENV['WABOT_USERNAME'] ?? 'admin';
        $password = $_ENV['WABOT_PASSWORD'] ?? 'adminpassword';
        
        $status = [
            'configured_url' => $base_url,
            'connection_status' => 'Testing...',
            'http_code' => null,
            'error' => null
        ];

        // Lakukan test login ke WABOT API
        $endpoint = $base_url . '/api/auth/login';
        $payload = json_encode([
            'username' => $username,
            'password' => $password
        ]);

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); 
        
        $response = curl_exec($ch);
        $err = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $status['http_code'] = $http_code;

        if ($err) {
            $status['connection_status'] = 'Failed';
            $status['error'] = 'cURL Error: ' . $err;
        } elseif ($http_code >= 200 && $http_code < 300) {
            $data = json_decode($response, true);
            if (isset($data['token'])) {
                $status['connection_status'] = 'Connected (Login Success)';
            } else {
                $status['connection_status'] = 'Connected but login failed (No token returned)';
                $status['error'] = $response;
            }
        } else {
            $status['connection_status'] = 'Failed';
            $status['error'] = "HTTP {$http_code} - " . $response;
        }

        return $status;
    }
}
