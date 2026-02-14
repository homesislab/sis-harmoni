<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ApiBootstrap
{
    public function handle(): void
    {
        if (php_sapi_name() === 'cli') {
            return;
        }

        $CI = function_exists('get_instance') ? get_instance() : null;

        $uri = $this->safeUriString($CI);

        if ($uri === '' || strpos($uri, 'api/v1/') !== 0) {
            return;
        }

        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');

        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        header('Content-Type: application/json; charset=utf-8');

        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {

            if ($CI && isset($CI->input)) {
                $raw = $CI->input->raw_input_stream;
            } else {
                $raw = file_get_contents('php://input');
            }

            if (is_string($raw) && $raw !== '') {
                $json = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                    $_POST = array_merge($_POST, $json);
                }
            }
        }
    }

    private function safeUriString($CI): string
    {
        if ($CI && isset($CI->uri) && is_object($CI->uri) && method_exists($CI->uri, 'uri_string')) {
            $u = (string) $CI->uri->uri_string();
            return ltrim($u, '/');
        }

        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        if ($requestUri === '') {
            return '';
        }

        $path = parse_url($requestUri, PHP_URL_PATH) ?: '';

        $path = ltrim($path, '/');

        if (strpos($path, 'index.php/') === 0) {
            $path = substr($path, strlen('index.php/'));
        } elseif ($path === 'index.php') {
            $path = '';
        }

        return $path;
    }
}
