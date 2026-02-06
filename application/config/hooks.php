<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$hook['post_controller_constructor'][] = array(
    'class'    => 'ApiBootstrap',
    'function' => 'handle',
    'filename' => 'ApiBootstrap.php',
    'filepath' => 'hooks',
    'params'   => array()
);
