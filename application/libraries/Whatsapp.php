<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Whatsapp
{
    protected $CI;
    protected $base_url = 'https://wabot.homesislab.my.id';
    
    // Configurations from env
    protected $username;
    protected $password;
    protected $session_id;

    // In-memory jwt token storage
    protected static $jwt_token = null;

    public function __construct()
    {
        $this->CI =& get_instance();
        
        $this->username   = $_ENV['WABOT_USERNAME'] ?? 'admin';
        $this->password   = $_ENV['WABOT_PASSWORD'] ?? 'adminpassword';
        $this->session_id = $_ENV['WABOT_SESSION_ID'] ?? 'homesislab';
    }

    /**
     * Set a custom session ID at runtime if needed
     */
    public function set_session(string $session_id): void
    {
        $this->session_id = $session_id;
    }

    /**
     * Attempt login to wabot service to get JWT token
     */
    private function login(): bool
    {
        // Use cached token if available and likely still valid 
        // (Assuming token is valid for current PHP request cycle, or we could decode expiration, 
        // but for short lived request cycle, in-memory static is usually sufficient to avoid multiple logins).
        if (self::$jwt_token !== null) {
            return true;
        }

        $endpoint = $this->base_url . '/api/auth/login';
        
        $payload = json_encode([
            'username' => $this->username,
            'password' => $this->password
        ]);

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Max 5 seconds so it doesn't block API
        
        $response = curl_exec($ch);
        $err = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            log_message('error', 'Whatsapp Login cURL Error: ' . $err);
            return false;
        }

        if ($http_code >= 200 && $http_code < 300) {
            $data = json_decode($response, true);
            if (isset($data['token'])) {
                self::$jwt_token = $data['token'];
                return true;
            }
        }

        log_message('error', "Whatsapp Login Failed. HTTP Code: {$http_code}, Response: {$response}");
        return false;
    }

    /**
     * Sanitize phone number: remove non-numeric chars, replace leading 0 with 62.
     * Safely ignores group IDs (e.g. 120363344727107749@g.us or 1234-5678)
     */
    private function _sanitize_phone(string $phone): string
    {
        $phone = trim($phone);
        
        // Pengecekan ID Grup:
        // Jika mengandung '@' (seperti @g.us) atau '-' (format grup lama),
        // abaikan formatting untuk menghindari rusaknya ID grup WA.
        if (strpos($phone, '@') !== false || strpos($phone, '-') !== false) {
            return preg_replace('/\s+/', '', $phone); // Remove any accidental spaces
        }

        // Hapus karakter non-numerik (spasi, +, dll) untuk nomor perorangan
        $clean = preg_replace('/[^0-9]/', '', $phone);
        
        // Nomor WA wajar (personal) biasanya maksimal 15 digit.
        // ID Grup versi baru bisa murni puluhan angka tanpa karakter khusus (meski harusnya dikasih @g.us).
        // Kalau kelewat panjang, kembalikan string aslinya.
        if (strlen($clean) > 15) {
            return preg_replace('/\s+/', '', $phone); 
        }

        // Jika diawali dengan 0, ubah awalan menjadi 62
        if (strpos($clean, '0') === 0) {
            $clean = '62' . substr($clean, 1);
        }
        
        return $clean;
    }

    /**
     * Send message. Fails silently (returns false) if something goes wrong.
     */
    public function send_message(string $to, string $content): bool
    {
        $to = $this->_sanitize_phone($to);
        if (empty($to)) {
            return false;
        }

        // Try to login
        if (!$this->login()) {
            return false;
        }

        $endpoint = $this->base_url . '/api/messages/send';

        $payload = json_encode([
            'sessionId' => $this->session_id,
            'to'        => $to,
            'type'      => 'TEXT',
            'content'   => $content
        ]);

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . self::$jwt_token
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6); // Timeout set to 6 seconds

        $response = curl_exec($ch);
        $err = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            log_message('error', 'Whatsapp Send cURL Error: ' . $err);
            return false;
        }

        if ($http_code >= 200 && $http_code < 300) {
            log_message('info', "Whatsapp message sent to {$to}.");
            return true;
        }

        log_message('error', "Whatsapp Send Failed to {$to}. HTTP Code: {$http_code}, Response: {$response}");
        return false;
    }

    /**
     * Helper to get target phone numbers from env
     */
    public function get_group_pengurus(): string
    {
        return $_ENV['WABOT_GROUP_PENGURUS_PHONE'] ?? '';
    }

    public function get_admin_keuangan(): string
    {
        return $_ENV['WABOT_ADMIN_KEUANGAN_PHONE'] ?? '';
    }

    public function get_admin_security(): string
    {
        return $_ENV['WABOT_ADMIN_SECURITY_PHONE'] ?? '';
    }
}
