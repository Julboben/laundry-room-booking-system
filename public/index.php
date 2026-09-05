<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use LaundryBooking\Http\Response;

if (($_SESSION['resident_authenticated'] ?? false) === true) {
    Response::redirect('/calendar.php');
}

Response::redirect('/access.php');
