<?php
defined('BASEPATH') OR exit('No direct script access allowed');


// Halaman Default awal membuka Portal Auth Login
$route['default_controller']   = 'auth';
$route['404_override']         = '';
$route['translate_uri_dashes'] = FALSE;

/* Route Login Auth */
$route['auth']                 = 'auth/index';
$route['auth/login_staff']     = 'auth/process_login_staff';
$route['auth/login_driver']    = 'auth/process_login_driver';
$route['auth/logout']          = 'auth/logout';

/* Route Interface Supir (Driver) */
$route['driver']               = 'driver/index';

/* Route Interface Yardman */
$route['yardman']              = 'yardman/index';

/* Route Interface Control Tower */
$route['control_tower']        = 'control_tower/index';

/* Route Interface Gate */
$route['gate']              = 'gate/index';
$route['gate/process_scan'] = 'gate/process_scan';