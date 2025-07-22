<?php
// app/views/auth/login.php

// Postavljamo naslov stranice koji će se koristiti u main.php layout-u
if (!defined('PAGE_TITLE')) {
    define('PAGE_TITLE', 'Prijava korisnika');
}
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header">
                <h3 class="mb-0"><?php echo htmlspecialchars(PAGE_TITLE); ?></h3>
            </div>
            <div class="card-body">
                <?php
                // Prikaz poruka o uspehu ili grešci
                if (isset($_SESSION['success_message'])) {
                    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
                    unset($_SESSION['success_message']);
                }
                if (isset($_SESSION['error_message'])) {
                    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
                    unset($_SESSION['error_message']);
                }
                ?>
                <form id="login-forma" action="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?action=login" method="POST">
                    <div class="mb-3">
                        <label for="korisnicko_ime" class="form-label">Korisničko ime</label>
                        <input type="text" class="form-control" id="korisnicko_ime" name="korisnicko_ime" value="<?php echo isset($_POST['korisnicko_ime']) ? htmlspecialchars($_POST['korisnicko_ime']) : ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="lozinka" class="form-label">Lozinka</label>
                        <input type="password" class="form-control" id="lozinka" name="lozinka" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Prijavi se</button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
            </div>
        </div>
    </div>
</div>