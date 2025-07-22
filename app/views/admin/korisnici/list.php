<?php
if (!defined('PAGE_TITLE')) {
    define('PAGE_TITLE', 'Upravljanje korisnicima');
}
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="mb-0"><?php echo htmlspecialchars(PAGE_TITLE); ?></h1>
    <a href="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?page=admin_user_create" class="btn btn-success">Dodaj novog korisnika</a>
</div>

<?php
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['success_message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['error_message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    unset($_SESSION['error_message']);
}
?>

<div class="table-responsive">
    <table class="table table-striped table-bordered table-hover">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Korisničko ime</th>
                <th>Ime i Prezime</th>
                <th>Email</th>
                <th>Uloga</th>
                <th>Aktivan</th>
                <th>Akcije</th>
            </tr>
        </thead>
        <tbody>
            <?php if (isset($korisnici) && !empty($korisnici)): ?>
                <?php foreach ($korisnici as $korisnik): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($korisnik['id']); ?></td>
                        <td><?php echo htmlspecialchars($korisnik['korisnicko_ime']); ?></td>
                        <td><?php echo htmlspecialchars(($korisnik['ime'] ?? '') . ' ' . ($korisnik['prezime'] ?? '')); ?></td>
                        <td><?php echo htmlspecialchars($korisnik['email'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($korisnik['uloga'])); ?></td>
                        <td><?php echo $korisnik['aktivan'] ? '<span class="badge bg-success">Da</span>' : '<span class="badge bg-danger">Ne</span>'; ?></td>
                        <td>
                            <div class="btn-group dropend">
                                <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-bars"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?page=admin_user_edit&id=<?php echo $korisnik['id']; ?>">
                                            <i class="fa-solid fa-pen-to-square me-2"></i>Izmeni
                                        </a>
                                    </li>
                                    <?php if ($_SESSION['user_id'] != $korisnik['id']): ?>
                                    <li>
                                        <a class="dropdown-item text-danger" href="#"
   data-bs-toggle="modal" 
   data-bs-target="#confirmDeleteModal"
   data-delete-url="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?action=admin_user_delete&id=<?php echo $korisnik['id']; ?>">
    <i class="fa-solid fa-trash me-2"></i>Obriši
</a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center">Nema unetih korisnika.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

