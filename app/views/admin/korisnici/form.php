<?php
// app/views/admin/korisnici/form.php
$isEdit = isset($korisnik) && $korisnik;
$pageTitle = $isEdit ? 'Izmena korisnika: ' . htmlspecialchars($korisnik['korisnicko_ime']) : 'Dodavanje novog korisnika';
if (!defined('PAGE_TITLE')) { define('PAGE_TITLE', $pageTitle); }
?>

<div class="row">
    <div class="col-md-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?page=admin_dashboard">Admin Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?page=admin_users">Upravljanje korisnicima</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo $isEdit ? 'Izmena' : 'Dodavanje'; ?></li>
            </ol>
        </nav>
        
        <h1><?php echo $pageTitle; ?></h1>

        <?php
        if (isset($_SESSION['form_errors'])) {
            echo '<div class="alert alert-danger">';
            foreach ($_SESSION['form_errors'] as $error) { echo htmlspecialchars($error) . '<br>'; }
            echo '</div>';
            unset($_SESSION['form_errors']);
        }
        ?>

        <form id="korisnik-forma" action="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?action=<?php echo $isEdit ? 'admin_user_update&id=' . $korisnik['id'] : 'admin_user_store'; ?>" method="POST">
            <div class="mb-3">
                <label for="korisnicko_ime" class="form-label">Korisničko ime <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="korisnicko_ime" name="korisnicko_ime" value="<?php echo htmlspecialchars($korisnik['korisnicko_ime'] ?? ($_SESSION['form_data']['korisnicko_ime'] ?? '')); ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($korisnik['email'] ?? ($_SESSION['form_data']['email'] ?? '')); ?>" required>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="ime" class="form-label">Ime</label>
                    <input type="text" class="form-control" id="ime" name="ime" value="<?php echo htmlspecialchars($korisnik['ime'] ?? ($_SESSION['form_data']['ime'] ?? '')); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="prezime" class="form-label">Prezime</label>
                    <input type="text" class="form-control" id="prezime" name="prezime" value="<?php echo htmlspecialchars($korisnik['prezime'] ?? ($_SESSION['form_data']['prezime'] ?? '')); ?>">
                </div>
            </div>
            <div class="mb-3">
                <label for="lozinka" class="form-label">Lozinka <?php if (!$isEdit) echo '<span class="text-danger">*</span>'; ?></label>
                <input type="password" class="form-control" id="lozinka" name="lozinka" <?php if (!$isEdit) echo 'required'; ?>>
                <?php if ($isEdit): ?>
                    <div class="form-text">Ostavite prazno ako ne želite da menjate lozinku.</div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label for="potvrda_lozinke" class="form-label">Potvrda lozinke <?php if (!$isEdit) echo '<span class="text-danger">*</span>'; ?></label>
                <input type="password" class="form-control" id="potvrda_lozinke" name="potvrda_lozinke" <?php if (!$isEdit) echo 'required'; ?>>
            </div>
            <div class="mb-3">
                <label for="uloga" class="form-label">Uloga <span class="text-danger">*</span></label>
                <select class="form-select" id="uloga" name="uloga" required>
                    <?php
                    $trenutnaUloga = $korisnik['uloga'] ?? ($_SESSION['form_data']['uloga'] ?? '');
                    $uloge = ['administrator', 'kontrolor', 'ostali'];
                    foreach ($uloge as $uloga) {
                        $selected = ($trenutnaUloga === $uloga) ? 'selected' : '';
                        echo "<option value=\"$uloga\" $selected>" . ucfirst($uloga) . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="mb-3 form-check">
                <?php
                $aktivan = $isEdit ? $korisnik['aktivan'] : 1;
                if (isset($_SESSION['form_data']['aktivan'])) { $aktivan = $_SESSION['form_data']['aktivan']; }
                ?>
                <input type="hidden" name="aktivan" value="0">
                <input type="checkbox" class="form-check-input" id="aktivan" name="aktivan" value="1" <?php echo ($aktivan == 1) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="aktivan">Aktivan</label>
            </div>
            <a href="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?page=admin_users" class="btn btn-secondary">Odustani</a>
            <button type="submit" class="btn btn-primary"><?php echo $isEdit ? 'Sačuvaj izmene' : 'Kreiraj korisnika'; ?></button>
        </form>
        <?php
        if (isset($_SESSION['form_data'])) { unset($_SESSION['form_data']); }
        ?>
    </div>
</div>