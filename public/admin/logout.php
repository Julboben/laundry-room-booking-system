<?php

declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

use LaundryBooking\Auth\AdminAuth;
use LaundryBooking\Http\Response;

$adminAuth = new AdminAuth();
$adminAuth->logout();

session_regenerate_id(true);

Response::redirect('/admin/login.php');
