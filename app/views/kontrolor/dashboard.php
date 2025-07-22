<?php
if (!defined('PAGE_TITLE')) {
    define('PAGE_TITLE', 'Kontrolor - Kontrolna tabla');
}
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>

<div class="container">
    <h1><?php echo htmlspecialchars(PAGE_TITLE); ?></h1>
    <p>Dobrodošli, <strong><?php echo htmlspecialchars($_SESSION['user_ime'] ?? $_SESSION['user_korisnicko_ime']); ?></strong>!</p>

    <div class="row mt-4">
        <div class="col-md-4 mb-3">
            <div class="card text-center text-bg-success">
                <div class="card-body">
                    <h5 class="card-title">Broj Vaših Zapisa</h5>
                    <p class="card-text fs-1 fw-bold"><?php echo $stats['my_total_records'] ?? 0; ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6 mb-3">
            <div class="card h-100"><div class="card-body text-center">
                <h5 class="card-title">Nova Kontrola</h5>
                <p class="card-text">Započnite novu evidenciju kontrole.</p>
                <a href="?page=kontrolor_biraj_vrstu" class="btn btn-primary btn-lg">Započni Novi Zapis</a>
            </div></div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100"><div class="card-body text-center">
                <h5 class="card-title">Pregled Zapisa</h5>
                <p class="card-text">Pregledajte sve vaše prethodno kreirane zapise.</p>
                <a href="?page=kontrolor_moji_zapisi" class="btn btn-secondary btn-lg">Moji Zapisi</a>
            </div></div>
        </div>
    </div>
</div>
