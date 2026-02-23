<?php

defined('BASEPATH') or exit('No direct script access allowed');

class FcmTokens extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();
        $this->load->model('Fcm_token_model', 'TokenModel');
    }

    public function save(): void
    {
        $in = $this->json_input();
        $token = trim((string)($in['token'] ?? ''));
        $device_type = trim((string)($in['device_type'] ?? 'web'));

        if (!$token) {
            api_validation_error(['token' => 'Token wajib disematkan']);
            return;
        }

        $this->TokenModel->save_token($this->auth_user['id'], $token, $device_type);
        api_ok(['saved' => true]);
    }

    public function remove(): void
    {
        $in = $this->json_input();
        $token = trim((string)($in['token'] ?? ''));

        if ($token) {
            $this->TokenModel->remove_token($token);
        }
        
        api_ok(['removed' => true]);
    }
}
