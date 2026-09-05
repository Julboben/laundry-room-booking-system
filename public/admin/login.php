<?php

declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';
require __DIR__ . '/../../app/View/layout.php';

use LaundryBooking\Auth\AdminAuth;
use LaundryBooking\Http\Csrf;
use LaundryBooking\Http\Request;
use LaundryBooking\Http\Response;
use LaundryBooking\Database\Connection;
use LaundryBooking\Security\RateLimiter;

use function LaundryBooking\Support\e;

$adminAuth = new AdminAuth(new RateLimiter(Connection::get()), Request::ip());

if ($adminAuth->isAuthenticated()) {
    Response::redirect('/admin/index.php');
}

$error = null;

if (Request::isPost()) {
    if (!Csrf::verify(Request::post('csrf_token'))) {
        $error = 'Sikkerhedstjek fejlede. Prøv igen.';
    } elseif ($adminAuth->isLockedOut()) {
        $minutes = (int) ceil($adminAuth->lockoutRemainingSeconds() / 60);
        $error = "For mange forsøg. Prøv igen om ca. {$minutes} minutter.";
    } else {
        $username = Request::post('username', '') ?? '';
        $password = Request::post('password', '') ?? '';

        if ($adminAuth->attempt($username, $password)) {
            Response::redirect('/admin/index.php');
        }

        $error = 'Forkert brugernavn eller adgangskode.';
    }
}

layout_start('Administrator login', showNav: false);
?>
<section class="card">
    <h2>Administrator login</h2>
    <?php if ($error !== null): ?>
        <p class="alert alert-error" role="alert"><?= e($error) ?></p>
    <?php endif; ?>
    <form method="post" action="/admin/login.php" novalidate>
        <?= Csrf::field() ?>
        <label for="username">Brugernavn</label>
        <input type="text" id="username" name="username" autocomplete="username" required>

        <label for="password">Adgangskode</label>
        <input type="password" id="password" name="password" autocomplete="current-password" required>

        <button type="submit" class="btn btn-primary">Log ind</button>
    </form>
</section>
<?php
layout_end();
