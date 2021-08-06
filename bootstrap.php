<?php

require_once __DIR__ . '/vendor/autoload.php';

WP_Mock::bootstrap();

require_once __DIR__ . '/includes/class-admin.php';
require_once __DIR__ . '/includes/class-login.php';
require_once __DIR__ . '/tests/WP_User.php';
require_once __DIR__ . '/tests/WP_Error.php';
