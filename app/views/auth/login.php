<?php
// Postavljamo naslov stranice koji će se koristiti u main.php layout-u
if (!defined('PAGE_TITLE')) {
    define('PAGE_TITLE', 'Prijava korisnika');
}
?>

<!-- Canvas element za JS animaciju, postavlja se kao pozadina -->
<canvas id="particle-canvas"></canvas>

<!-- Kontejner koji centrira formu na ekranu -->
<div class="login-container">
    <div class="login-form">
        <!-- Logo vaše kompanije -->
        <img src="<?php echo rtrim(APP_URL, '/'); ?>/public/images/logo.png" alt="Logo" style="max-width: 150px; margin-bottom: 20px;">
        
        <h2>EVIDENCIJA KONTROLE PROIZVODA</h2>

        <?php
        // Prikaz poruka o uspehu ili grešci unutar forme
        if (isset($_SESSION['success_message'])) {
            echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
            unset($_SESSION['success_message']);
        }
        if (isset($_SESSION['error_message'])) {
            echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
            unset($_SESSION['error_message']);
        }
        ?>
        
        <!-- Forma za prijavu -->
        <form id="login-forma" action="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php" method="POST">
            <!-- Skriveno polje koje govori sistemu koju akciju da izvrši -->
            <input type="hidden" name="action" value="login">
            
            <div class="input-group">
                <label for="korisnicko_ime">Korisničko ime</label>
                <input type="text" class="form-control" id="korisnicko_ime" name="korisnicko_ime" value="<?php echo isset($_POST['korisnicko_ime']) ? htmlspecialchars($_POST['korisnicko_ime']) : ''; ?>" required>
            </div>
            
            <div class="input-group">
                <label for="lozinka">Lozinka</label>
                <input type="password" class="form-control" id="lozinka" name="lozinka" required>
            </div>
            
            <button type="submit" class="login-button">Prijavi se</button>
        </form>
    </div>
</div>
