<?php
if (!defined('PAGE_TITLE')) {
    define('PAGE_TITLE', 'Odabir Vrste Kontrole');
}
?>
<div class="container text-center">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?page=kontrolor_dashboard">Kontrolna tabla</a></li>
            <li class="breadcrumb-item active" aria-current="page">Odabir vrste</li>
        </ol>
    </nav>

    <h1 class="mb-4"><?php echo htmlspecialchars(PAGE_TITLE); ?></h1>
    <p class="lead">Molimo izaberite vrstu kontrole koju želite da evidentirate.</p>

    <div class="d-grid gap-3 col-md-6 mx-auto mt-5">
        <a href="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?page=kontrolor_novi_zapis&vrsta=redovna_kontrola" class="btn btn-primary btn-lg p-3">
            Redovna kontrola gotovog proizvoda
        </a>
        <a href="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?page=kontrolor_novi_zapis&vrsta=kontrola_pre_isporuke" class="btn btn-info btn-lg p-3 text-dark">
            Kontrola pre isporuke
        </a>
    </div>

    <div class="mt-5">
         <a href="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?page=kontrolor_dashboard" class="btn btn-secondary">Odustani i vrati se nazad</a>
    </div>
</div>