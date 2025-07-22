<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$currentPage = $_GET['page'] ?? '';
$isUserLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo defined('PAGE_TITLE') ? htmlspecialchars(PAGE_TITLE) . ' - ' . htmlspecialchars(SITE_NAME) : htmlspecialchars(SITE_NAME); ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="<?php echo rtrim(APP_URL, '/'); ?>/public/css/style.css">
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container">
                <a class="navbar-brand" href="<?php echo rtrim(APP_URL, '/'); ?>/public/">
                    <img src="<?php echo rtrim(APP_URL, '/'); ?>/public/images/logo.png" alt="Logo Fabrike" style="height: 30px; margin-right: 10px;">
                    SKK
                </a>
                
                <div class="d-flex align-items-center">
                    <?php if ($isUserLoggedIn): ?>
                        <div class="dropdown d-lg-none">
                            <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-user"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><h6 class="dropdown-header">Prijavljeni ste kao:</h6></li>
                                <li><span class="dropdown-item-text"><em><?php echo htmlspecialchars($_SESSION['user_ime'] ?? $_SESSION['user_korisnicko_ime']); ?></em></span></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="?page=promena_sifre">Promena lozinke</a></li>
                                <li><a class="dropdown-item text-danger" href="?action=logout">Odjavi se</a></li>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <button class="navbar-toggler ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                </div>

                <div class="collapse navbar-collapse" id="mainNavbar">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <?php if ($isUserLoggedIn): ?>
                            <?php if ($_SESSION['user_uloga'] === 'administrator'): ?>
            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'admin_dashboard') ? 'active' : ''; ?>" href="?page=admin_dashboard"><i class="fa-solid fa-house me-1"></i>Početna</a></li>
            
            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'admin_plans') ? 'active' : ''; ?>" href="?page=admin_plans"><i class="fa-solid fa-clipboard-list me-1"></i>Planovi Kontrole</a></li>
            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'admin_evidencije') ? 'active' : ''; ?>" href="?page=admin_evidencije"><i class="fa-solid fa-table-list me-1"></i>Evidencije</a></li>
            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'admin_reports') ? 'active' : ''; ?>" href="?page=admin_reports"><i class="fa-solid fa-file-lines me-1"></i>Izveštaji</a></li>
            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'admin_users') ? 'active' : ''; ?>" href="?page=admin_users"><i class="fa-solid fa-users me-1"></i>Korisnici</a></li>
                            <?php elseif ($_SESSION['user_uloga'] === 'kontrolor'): ?>
            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'kontrolor_dashboard') ? 'active' : ''; ?>" href="?page=kontrolor_dashboard"><i class="fa-solid fa-house me-1"></i>Početna</a></li>
            <li class="nav-item"><a class="nav-link <?php echo (($currentPage === 'kontrolor_biraj_vrstu') || ($currentPage === 'kontrolor_novi_zapis')) ? 'active' : ''; ?>" href="?page=kontrolor_biraj_vrstu"><i class="fa-solid fa-file-circle-plus me-1"></i>Novi Zapis</a></li>
            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'kontrolor_moji_zapisi') ? 'active' : ''; ?>" href="?page=kontrolor_moji_zapisi"><i class="fa-solid fa-file-lines me-1"></i>Moji Zapisi</a></li>
                            <?php elseif ($_SESSION['user_uloga'] === 'ostali'): ?>
            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'ostali_dashboard') ? 'active' : ''; ?>" href="?page=ostali_dashboard"><i class="fa-solid fa-house me-1"></i>Početna</a></li>
            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'pregled_svih_zapisa') ? 'active' : ''; ?>" href="?page=pregled_svih_zapisa"><i class="fa-solid fa-table-list me-1"></i>Pregled Evidencija</a></li>
            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'pregled_planova') ? 'active' : ''; ?>" href="?page=pregled_planova"><i class="fa-solid fa-clipboard-list me-1"></i>Pregled Planova</a></li>
                            <?php endif; ?>
                        <?php endif; ?>
                    </ul>
                    
                    <?php if ($isUserLoggedIn): ?>
                        <div class="dropdown d-none d-lg-block">
                            <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-user me-1"></i> 
                                <span class="d-none d-lg-inline"><?php echo htmlspecialchars($_SESSION['user_ime'] ?? $_SESSION['user_korisnicko_ime']); ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="?page=promena_sifre">Promena lozinke</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="?action=logout"><i class="fa-solid fa-right-from-bracket me-1"></i>Odjavi se</a></li>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
                 </div>
        </nav>
    </header>

    <main class="container-fluid container-lg bg-white p-1 p-md-4 my-1 flex-shrink-0 shadow-sm rounded">
        <?php
        if (isset($view_file_path) && !empty($view_file_path) && file_exists($view_file_path)) {
            include $view_file_path;
        } else {
            echo "<div class='alert alert-danger'>Sadržaj stranice nije moguće učitati.</div>";
        }
        ?>
    </main>

    <footer class="text-center p-3 mt-auto">
        <div class="container">
            <p class="mb-0 text-muted">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(SITE_NAME); ?>. Sva prava zadržana.</p>
        </div>
    </footer>

   <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Potvrda brisanja</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zatvori"></button>
      </div>
      <div class="modal-body">
        Da li ste sigurni da želite da obrišete ovaj plan kontrole?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Otkaži</button>
        <a href="#" class="btn btn-danger" id="confirmDeleteButton">Obriši</a>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="confirmDomRemoveModal" tabindex="-1" aria-labelledby="confirmDomRemoveModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="confirmDomRemoveModalLabel">Potvrda Uklanjanja</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="confirmDomRemoveModalBody">
            Da li ste sigurni?
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Odustani</button>
            <button type="button" id="confirmDomRemoveButton" class="btn btn-danger">Ukloni</button>
          </div>
        </div>
      </div>
    </div>

   <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/browser-image-compression@2.0.2/dist/browser-image-compression.js"></script>
    
    <script src="<?php echo rtrim(APP_URL, '/'); ?>/public/js/custom_scripts.js"></script>
</body>
</html>