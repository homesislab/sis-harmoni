<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Minimal JWT HS256 (no external dependency)
 * - encode(payload) -> token
 * - decode(token) -> payload array
 */
class JwtService
{
    private $secret;
    private $issuer;
    private $ttl;

    public function __construct()
    {
        $CI =& get_instance();
        $jwt = $CI->config->item('jwt');
        $this->secret = $CI->config->item('jwt_secret', 'jwt');
        $this->issuer = $CI->config->item('jwt_issuer', 'jwt');
        $this->ttl    = (int)$CI->config->item('jwt_ttl_seconds', 'jwt');
    }

    public function encode(array $claims): string
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];

        $now = time();
        $payload = array_merge([
            'iss' => $this->issuer,
            'iat' => $now,
            'exp' => $now + $this->ttl,
        ], $claims);

        $h = $this->b64url(json_encode($header));
        $p = $this->b64url(json_encode($payload));
        $sig = $this->sign("$h.$p");

        return "$h.$p.$sig";
    }

    public function decode(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new Exception('Invalid token');
        }

        [$h, $p, $s] = $parts;

        $header = json_decode($this->b64url_decode($h), true);
        $payload = json_decode($this->b64url_decode($p), true);

        if (!is_array($header) || !is_array($payload)) {
            throw new Exception('Invalid token');
        }

        if (($header['alg'] ?? '') !== 'HS256') {
            throw new Exception('Unsupported alg');
        }

        $expected = $this->sign("$h.$p");
        if (!hash_equals($expected, $s)) {
            throw new Exception('Bad signature');
        }

        $now = time();
        if (!empty($payload['exp']) && $now >= (int)$payload['exp']) {
            throw new Exception('Token expired');
        }

        if (!empty($payload['iss']) && $payload['iss'] !== $this->issuer) {
            throw new Exception('Bad issuer');
        }

        return $payload;
    }

    private function sign(string $data): string
    {
        return $this->b64url(hash_hmac('sha256', $data, $this->secret, true));
    }

    private function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function b64url_decode(string $data): string
    {
        $pad = strlen($data) % 4;
        if ($pad) $data .= str_repeat('=', 4 - $pad);
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
