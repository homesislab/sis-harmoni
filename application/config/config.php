<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Base Site URL
|--------------------------------------------------------------------------
| Sesuaikan untuk environment kamu.
| Jika pakai Apache/Nginx dengan docroot di project:
| contoh: http://localhost/
*/
$config['base_url'] = ($_ENV['SIS_PAGUYUBAN_BASE_URL'] ?? getenv('SIS_PAGUYUBAN_BASE_URL')) ?: 'http://localhost/';

/*
|--------------------------------------------------------------------------
| Index File
|--------------------------------------------------------------------------
| Kosong kalau rewrite / .htaccess aktif.
*/
$config['index_page'] = '';

/*
|--------------------------------------------------------------------------
| URI PROTOCOL
|--------------------------------------------------------------------------
*/
$config['uri_protocol'] = 'REQUEST_URI';

/*
|--------------------------------------------------------------------------
| URL suffix
|--------------------------------------------------------------------------
*/
$config['url_suffix'] = '';

/*
|--------------------------------------------------------------------------
| Language & Charset
|--------------------------------------------------------------------------
*/
$config['language'] = 'english';
$config['charset']  = 'UTF-8';

/*
|--------------------------------------------------------------------------
| Enable/Disable System Hooks
|--------------------------------------------------------------------------
| WAJIB TRUE karena kita pakai ApiCorsHook (Tahap 0).
*/
$config['enable_hooks'] = TRUE;

/*
|--------------------------------------------------------------------------
| Class Extension Prefix
|--------------------------------------------------------------------------
*/
$config['subclass_prefix'] = 'MY_';

/*
|--------------------------------------------------------------------------
| Composer auto-loading
|--------------------------------------------------------------------------
*/
$config['composer_autoload'] = FALSE;

/*
|--------------------------------------------------------------------------
| Allowed URL Characters
|--------------------------------------------------------------------------
*/
$config['permitted_uri_chars'] = 'a-z 0-9~%.:_\-';

/*
|--------------------------------------------------------------------------
| Enable Query Strings
|--------------------------------------------------------------------------
*/
$config['enable_query_strings'] = FALSE;
$config['controller_trigger']   = 'c';
$config['function_trigger']     = 'm';
$config['directory_trigger']    = 'd';

/*
|--------------------------------------------------------------------------
| Allow $_GET array
|--------------------------------------------------------------------------
*/
$config['allow_get_array'] = TRUE;

/*
|--------------------------------------------------------------------------
| Error Logging Threshold
|--------------------------------------------------------------------------
| 1 (error) recommended untuk dev/prod awal.
| Kamu sebelumnya 0 (off). Itu bikin debugging susah.
*/
$config['log_threshold'] = 1;
$config['log_path'] = '';
$config['log_file_extension'] = '';
$config['log_file_permissions'] = 0644;
$config['log_date_format'] = 'Y-m-d H:i:s';

/*
|--------------------------------------------------------------------------
| Error Views Directory Path
|--------------------------------------------------------------------------
*/
$config['error_views_path'] = '';

/*
|--------------------------------------------------------------------------
| Cache Directory Path
|--------------------------------------------------------------------------
*/
$config['cache_path'] = '';
$config['cache_query_string'] = FALSE;

/*
|--------------------------------------------------------------------------
| Encryption Key (WAJIB)
|--------------------------------------------------------------------------
| Minimal 32 char random.
*/
$config['encryption_key'] = 'CHANGE_ME_TO_A_LONG_RANDOM_SECRET_32CHARS_MIN';

/*
|--------------------------------------------------------------------------
| Session Variables
|--------------------------------------------------------------------------
| - files driver butuh path absolute.
| - Jangan NULL. CI3 bisa error "You are REQUIRED to set a valid save path!"
*/
$config['sess_driver']            = 'files';
$config['sess_cookie_name']       = 'ci_session';
$config['sess_samesite']          = 'Lax';
$config['sess_expiration']        = 0; // 0 = sampai browser ditutup
$config['sess_save_path']         = sys_get_temp_dir(); // absolute path
$config['sess_match_ip']          = FALSE;
$config['sess_time_to_update']    = 300;
$config['sess_regenerate_destroy']= FALSE;

/*
|--------------------------------------------------------------------------
| Cookie Related Variables
|--------------------------------------------------------------------------
*/
$config['cookie_prefix']   = '';
$config['cookie_domain']   = '';
$config['cookie_path']     = '/';
$config['cookie_secure']   = FALSE;     // set TRUE jika HTTPS
$config['cookie_httponly'] = TRUE;
$config['cookie_samesite'] = 'Lax';

/*
|--------------------------------------------------------------------------
| Standardize newlines (deprecated)
|--------------------------------------------------------------------------
*/
$config['standardize_newlines'] = FALSE;

/*
|--------------------------------------------------------------------------
| Global XSS Filtering (deprecated)
|--------------------------------------------------------------------------
*/
$config['global_xss_filtering'] = FALSE;

/*
|--------------------------------------------------------------------------
| Cross Site Request Forgery
|--------------------------------------------------------------------------
| API JWT => CSRF OFF.
*/
$config['csrf_protection']   = FALSE;
$config['csrf_token_name']   = 'csrf_test_name';
$config['csrf_cookie_name']  = 'csrf_cookie_name';
$config['csrf_expire']       = 7200;
$config['csrf_regenerate']   = TRUE;
$config['csrf_exclude_uris'] = array();

/*
|--------------------------------------------------------------------------
| Output Compression
|--------------------------------------------------------------------------
*/
$config['compress_output'] = FALSE;

/*
|--------------------------------------------------------------------------
| Master Time Reference
|--------------------------------------------------------------------------
*/
$config['time_reference'] = 'local';

/*
|--------------------------------------------------------------------------
| Rewrite PHP Short Tags
|--------------------------------------------------------------------------
*/
$config['rewrite_short_tags'] = FALSE;

/*
|--------------------------------------------------------------------------
| Reverse Proxy IPs
|--------------------------------------------------------------------------
*/
$config['proxy_ips'] = '';

/*
|--------------------------------------------------------------------------
| SIS PAGUYUBAN - API SETTINGS
|--------------------------------------------------------------------------
| Ini sudah ada di config kamu: api_jwt_secret dan api_token_ttl.
| Aku pertahankan (lebih aman daripada ganti nama key).
|
| - api_prefix: agar konsisten dengan OpenAPI http://localhost/api/v1
| - api_debug: untuk tambahan debug (optional)
*/
$config['api_prefix']    = 'api/v1';
$config['api_debug']     = FALSE;

$config['api_jwt_secret'] = 'SIS_HARMONI_CHANGE_ME_64CHAR_RANDOM';
$config['api_token_ttl']  = 604800; // 7 hari (detik)

/*
|--------------------------------------------------------------------------
| CORS defaults (dipakai ApiCorsHook)
|--------------------------------------------------------------------------
*/
$config['api_cors_allow_origin']  = '*';
$config['api_cors_allow_headers'] = 'Content-Type, Authorization, X-Requested-With';
$config['api_cors_allow_methods'] = 'GET, POST, PUT, PATCH, DELETE, OPTIONS';
