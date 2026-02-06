<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Auto-load Packages
|--------------------------------------------------------------------------
*/
$autoload['packages'] = array();

/*
|--------------------------------------------------------------------------
| Auto-load Libraries
|--------------------------------------------------------------------------
| - database: wajib untuk API
| - session: optional, tapi aman untuk CI3 internal (flash, dll)
| - form_validation: dipakai hampir semua endpoint
*/
$autoload['libraries'] = array(
    'database',
    'session',
    'form_validation',
);

/*
|--------------------------------------------------------------------------
| Auto-load Drivers
|--------------------------------------------------------------------------
*/
$autoload['drivers'] = array();

/*
|--------------------------------------------------------------------------
| Auto-load Helper Files
|--------------------------------------------------------------------------
| - url: base helper
| - security: xss_clean, do_hash, dll (meski xss global OFF)
| - api_response: helper response standar (api_ok/api_error)
|
| NOTE:
| file helper kamu saat ini autoload 'api'. Aku ganti jadi 'api_response'
| agar jelas dan tidak tabrakan dengan helper lain.
*/
$autoload['helper'] = array(
    'url',
    'security',
    'api_response',
);

/*
|--------------------------------------------------------------------------
| Auto-load Config files
|--------------------------------------------------------------------------
*/
$autoload['config'] = array();

/*
|--------------------------------------------------------------------------
| Auto-load Language files
|--------------------------------------------------------------------------
*/
$autoload['language'] = array();

/*
|--------------------------------------------------------------------------
| Auto-load Models
|--------------------------------------------------------------------------
| Disarankan kosong.
| Model dipanggil per controller agar jelas dependency-nya.
*/
$autoload['model'] = array();
