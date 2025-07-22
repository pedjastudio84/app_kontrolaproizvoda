<?php
if (!defined('PAGE_TITLE')) {
    define('PAGE_TITLE', 'Korisnička tabla');
}
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<div class="container">
    <h1><?php echo htmlspecialchars(PAGE_TITLE); ?></h1>
    <p>Dobrodošli, <strong><?php echo htmlspecialchars($_SESSION['user_ime'] ?? $_SESSION['user_korisnicko_ime'] ?? 'Korisnik'); ?></strong>!</p>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card text-center text-white bg-info">
                <div class="card-body">
                    <h5 class="card-title">Broj Evidencija (Danas)</h5>
                    <p class="card-text fs-1 fw-bold"><?php echo $stats['records_today'] ?? 0; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card text-center text-white bg-warning">
                <div class="card-body">
                    <h5 class="card-title">Broj Evidencija (Ovaj Mesec)</h5>
                    <p class="card-text fs-1 fw-bold"><?php echo $stats['records_this_month'] ?? 0; ?></p>
                </div>
            </div>
        </div>
    </div>

    <p>Vaše opcije:</p>
    <div class="list-group">
        <a href="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?page=pregled_svih_zapisa" class="list-group-item list-group-item-action">
            <i class="fa-solid fa-folder-open me-2"></i>Pregled rezultata ispitivanja
        </a>
    </div>
</div>