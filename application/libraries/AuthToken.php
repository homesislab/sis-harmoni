<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * AuthToken (CI3 Library)
 * Simple JWT HS256 (no composer)
 */
class AuthToken
{
    protected $CI;
    protected $secret;
    protected $ttl;

    public function __construct($params = [])
    {
        $this->CI =& get_instance();

        $this->secret = isset($params['secret'])
            ? $params['secret']
            : ($this->CI->config->item('jwt_secret') ?: 'CHANGE_ME_SECRET');

        $this->ttl = isset($params['ttl'])
            ? (int)$params['ttl']
            : (int)($this->CI->config->item('jwt_ttl') ?: 86400);
    }

    /**
     * Issue token
     * Supported calls:
     *   - issue(['user_id'=>1, ...], $ttl=null)
     *   - issue(1, ['roles'=>...])  (backward-compatible)
     */
    public function issue($payload = [], $ttl = null): string
    {
        if (is_int($payload)) {
            $userId = $payload;
            $claims = is_array($ttl) ? $ttl : [];
            $payload = array_merge(['user_id' => $userId], $claims);
            $ttl = null;
        }

        if (!is_array($payload)) {
            $payload = [];
        }

        $now = time();
        $exp = $now + (int)($ttl ?? $this->ttl);

        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $body = array_merge($payload, [
            'iat' => $now,
            'exp' => $exp,
        ]);

        $segments = [];
        $segments[] = $this->base64url_encode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $segments[] = $this->base64url_encode(json_encode($body, JSON_UNESCAPED_SLASHES));
        $signingInput = $segments[0] . '.' . $segments[1];

        $signature = $this->sign($signingInput, $this->secret);
        $segments[] = $this->base64url_encode($signature);

        return implode('.', $segments);
    }

    /**
     * Verify token and return payload array if valid; otherwise null
     */
    public function verify(string $token): ?array
    {
        $token = trim($token);
        if ($token === '') return null;

        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;

        [$h64, $p64, $s64] = $parts;

        $headerJson  = $this->base64url_decode($h64);
        $payloadJson = $this->base64url_decode($p64);
        $sig         = $this->base64url_decode($s64);

        if ($headerJson === null || $payloadJson === null || $sig === null) return null;

        $header  = json_decode($headerJson, true);
        $payload = json_decode($payloadJson, true);

        if (!is_array($header) || !is_array($payload)) return null;
        if (($header['alg'] ?? '') !== 'HS256') return null;

        $signingInput = $h64 . '.' . $p64;
        $expected = $this->sign($signingInput, $this->secret);

        if (!hash_equals($expected, $sig)) return null;

        $exp = (int)($payload['exp'] ?? 0);
        if ($exp > 0 && time() > $exp) return null;

        return $payload;
    }

    public function get_bearer_token(): string
    {
        $auth = $this->CI->input->get_request_header('Authorization', true);
        if (!$auth) return '';

        if (stripos($auth, 'Bearer ') === 0) {
            return trim(substr($auth, 7));
        }
        return '';
    }

    protected function sign(string $data, string $secret): string
    {
        return hash_hmac('sha256', $data, $secret, true);
    }

    protected function base64url_encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    protected function base64url_decode(string $data): ?string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        $decoded = base64_decode(strtr($data, '-_', '+/'), true);
        return ($decoded === false) ? null : $decoded;
    }
}
