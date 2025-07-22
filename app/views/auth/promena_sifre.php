<?php
if (!defined('PAGE_TITLE')) {
    define('PAGE_TITLE', 'Promena Lozinke');
}
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <h1><?php echo htmlspecialchars(PAGE_TITLE); ?></h1>
        <hr>
        <?php
        if (isset($_SESSION['form_errors'])) {
            echo '<div class="alert alert-danger"><ul>';
            foreach ($_SESSION['form_errors'] as $error) {
                echo '<li>' . htmlspecialchars($error) . '</li>';
            }
            echo '</ul></div>';
            unset($_SESSION['form_errors']);
        }
        ?>
        <form id="promena-sifre-forma" action="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?action=promena_sifre" method="POST">
            <div class="mb-3">
                <label for="stara_lozinka" class="form-label">Stara lozinka</label>
                <input type="password" class="form-control" id="stara_lozinka" name="stara_lozinka" required>
            </div>
            <div class="mb-3">
                <label for="nova_lozinka" class="form-label">Nova lozinka</label>
                <input type="password" class="form-control" id="nova_lozinka" name="nova_lozinka" required>
                <div class="form-text">Lozinka mora imati najmanje 6 karaktera.</div>
            </div>
            <div class="mb-3">
                <label for="potvrda_nove_lozinke" class="form-label">Potvrdite novu lozinku</label>
                <input type="password" class="form-control" id="potvrda_nove_lozinke" name="potvrda_nove_lozinke" required>
            </div>
            <button type="submit" class="btn btn-primary">Promeni lozinku</button>
            <a href="javascript:history.back()" class="btn btn-secondary">Odustani</a>
        </form>
    </div>
</div>