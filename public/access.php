<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/View/layout.php';

use LaundryBooking\Auth\ResidentAccess;
use LaundryBooking\Database\Connection;
use LaundryBooking\Http\Csrf;
use LaundryBooking\Http\Request;
use LaundryBooking\Http\Response;
use LaundryBooking\Models\Setting;
use LaundryBooking\Security\RateLimiter;
use LaundryBooking\Services\SettingsService;

use function LaundryBooking\Support\e;
use function LaundryBooking\Support\t;
use function LaundryBooking\Support\tn;

$pdo = Connection::get();
$settingsService = new SettingsService(new Setting($pdo));
$residentAccess = new ResidentAccess($settingsService, new RateLimiter($pdo), Request::ip());

if ($residentAccess->isAuthenticated()) {
    Response::redirect('/calendar.php');
}

$error = null;

if (Request::isPost()) {
    if (!Csrf::verify(Request::post('csrf_token'))) {
        $error = t('Sikkerhedstjekket mislykkedes. Prøv igen.');
    } elseif ($residentAccess->isLockedOut()) {
        $minutes = (int) ceil($residentAccess->lockoutRemainingSeconds() / 60);
        $error = tn($minutes, 'For mange forsøg. Prøv igen om ca. %d minut.', 'For mange forsøg. Prøv igen om ca. %d minutter.');
    } else {
        $code = Request::post('property_code', '') ?? '';

        if ($residentAccess->attempt($code)) {
            Response::redirect('/calendar.php');
        }

        $error = t('Forkert kode. Prøv igen.');
    }
}

layout_start('Adgang', showNav: false, bodyClass: 'page-kiosk page-access');
?>
<section class="card kiosk-card access-card" aria-labelledby="page-heading">
    <?= illustration('tip', 'card-illustration') ?>
    <h1 id="page-heading"><?= e(t('Velkommen')) ?></h1>
    <p><?= e(t('Indtast ejendommens fælles kode for at se og booke vasketider.')) ?></p>
    <?php if ($error !== null): ?>
        <p class="alert alert-error" role="alert"><?= e($error) ?></p>
    <?php endif; ?>
    <form method="post" action="/access.php" novalidate>
        <?= Csrf::field() ?>
        <label for="property_code"><?= e(t('Adgangskode')) ?></label>
        <input
            type="password"
            id="property_code"
            name="property_code"
            class="code-input"
            autocomplete="off"
            inputmode="numeric"
            enterkeyhint="go"
            required
        >
        <button type="submit" class="btn btn-primary"><?= e(t('Fortsæt')) ?></button>
    </form>
</section>
<?php
layout_end();
