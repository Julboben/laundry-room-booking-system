<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use LaundryBooking\Auth\ResidentAccess;
use LaundryBooking\Database\Connection;
use LaundryBooking\Http\Response;
use LaundryBooking\Models\Setting;
use LaundryBooking\Services\SettingsService;

$pdo = Connection::get();
$residentAccess = new ResidentAccess(new SettingsService(new Setting($pdo)));
$residentAccess->logout();

session_regenerate_id(true);

Response::redirect('/access.php');
