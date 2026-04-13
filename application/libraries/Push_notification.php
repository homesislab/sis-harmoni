<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Push_notification
{
    public function send_to_all(string $title, string $body, string $url, array $data = [], ?int $exclude_user_id = null): void
    {
        $CI =& get_instance();

        if (!$CI->db->table_exists('fcm_tokens')) {
            return;
        }

        $serviceAccount = APPPATH . 'config/firebase-service-account.json';
        if (!is_file($serviceAccount)) {
            log_message('error', 'Push notification skipped: firebase-service-account.json not found.');
            return;
        }

        $qb = $CI->db
            ->select('ft.token')
            ->from('fcm_tokens ft')
            ->join('users u', 'u.id = ft.user_id', 'left')
            ->where('u.status', 'active');
        if ($exclude_user_id !== null && $exclude_user_id > 0) {
            $qb->where('ft.user_id !=', $exclude_user_id);
        }

        $rows = $qb->group_by('ft.token')->get()->result_array();
        $tokens = array_values(array_filter(array_map(static fn ($row) => trim((string)($row['token'] ?? '')), $rows)));
        if (!$tokens) {
            return;
        }

        $payload = array_merge($data, [
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'click_action' => $url,
            'type' => $data['type'] ?? 'content_update',
            'timestamp' => (string)time(),
        ]);

        try {
            $factory = (new \Kreait\Firebase\Factory)->withServiceAccount($serviceAccount);
            $messaging = $factory->createMessaging();

            foreach ($tokens as $token) {
                try {
                    $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token', $token)
                        ->withData($payload);
                    $messaging->send($message);
                } catch (\Throwable $e) {
                    $message = $e->getMessage();
                    log_message('error', 'Push notification failed for token ' . substr($token, 0, 15) . ': ' . $message);

                    if (
                        strpos($message, 'not known to the Firebase') !== false ||
                        strpos($message, 'NotRegistered') !== false ||
                        strpos($message, 'Invalid registration token') !== false
                    ) {
                        $CI->db->where('token', $token)->delete('fcm_tokens');
                    }
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'Push notification factory failed: ' . $e->getMessage());
        }
    }
}
