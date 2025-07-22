<?php
if (!defined('PAGE_TITLE')) {
    define('PAGE_TITLE', 'Moji Zapisi o Kontroli');
}
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Pravimo query string za paginaciju koji čuva trenutne parametre pretrage
// kako pretraga ne bi bila resetovana prilikom promene stranice.
$pagination_query_params = http_build_query([
    'search_ident' => $search_params['ident'] ?? '',
    'search_kataloska' => $search_params['kataloska'] ?? '',
    'search_serijski' => $search_params['serijski'] ?? '',
]);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><?php echo htmlspecialchars(PAGE_TITLE); ?></h1>
    <a href="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?page=kontrolor_biraj_vrstu" class="btn btn-success"> <i class="fa-solid fa-plus me-1"></i>Novi zapis</a>
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

<div class="card mb-4">
    <div class="card-header">
        Pretraga Mojih Zapisa
    </div>
    <div class="card-body">
        <form action="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php" method="GET">
            <input type="hidden" name="page" value="kontrolor_moji_zapisi">
            <div class="row align-items-end">
                <div class="col-md-4 mb-2">
                    <label for="search_ident" class="form-label">Ident</label>
                    <input type="text" class="form-control" id="search_ident" name="search_ident" placeholder="Unesite ident..." value="<?php echo htmlspecialchars($search_params['ident'] ?? ''); ?>">
                </div>
                <div class="col-md-4 mb-2">
                    <label for="search_kataloska" class="form-label">Kataloška oznaka</label>
                    <input type="text" class="form-control" id="search_kataloska" name="search_kataloska" placeholder="Unesite kat. oznaku..." value="<?php echo htmlspecialchars($search_params['kataloska'] ?? ''); ?>">
                </div>
                <div class="col-md-4 mb-2">
                    <label for="search_serijski" class="form-label">Serijski broj</label>
                    <input type="text" class="form-control" id="search_serijski" name="search_serijski" placeholder="Unesite serijski broj..." value="<?php echo htmlspecialchars($search_params['serijski'] ?? ''); ?>">
                </div>
            </div>
            <div class="d-flex justify-content-end mt-2">
                <a href="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?page=kontrolor_moji_zapisi" class="btn btn-secondary me-2">Poništi</a>
                <button type="submit" class="btn btn-primary">Pretraži</button>
            </div>
        </form>
    </div>
</div>


<div class="table-responsive">
    <table class="table table-striped table-bordered table-hover">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Vrsta Kontrole</th>
                <th>Ident</th>
                <th>Kataloška oznaka</th>
                <th>Naziv Proizvoda</th>
                <th>Serijski Broj</th>
                <th>Datum i Vreme</th>
                <th>Akcije</th>
            </tr>
        </thead>
        <tbody>
            <?php if (isset($evidencije) && !empty($evidencije)): ?>
                <?php foreach ($evidencije as $evidencija): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($evidencija['id']); ?></td>
                        <td><?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($evidencija['vrsta_kontrole']))); ?></td>
                        <td><?php echo htmlspecialchars($evidencija['product_ident_sken']); ?></td>
                        <td><?php echo htmlspecialchars($evidencija['product_kataloska_oznaka_sken']); ?></td>
                        <td><?php echo htmlspecialchars($evidencija['product_naziv_sken']); ?></td>
                        <td><?php echo htmlspecialchars($evidencija['product_serijski_broj_sken']); ?></td>
                        <td><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($evidencija['datum_vreme_ispitivanja']))); ?></td>
                        <td>
                            <div class="btn-group dropend">
                                <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-bars"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?page=kontrolor_zapis_show&id=<?php echo $evidencija['id']; ?>"><i class="fa-solid fa-eye me-2"></i>Pregledaj</a></li>
                                    <li><a class="dropdown-item" href="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?page=kontrolor_zapis_edit&id=<?php echo $evidencija['id']; ?>"><i class="fa-solid fa-pen-to-square me-2"></i>Izmeni</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                         <a class="dropdown-item text-danger" href="#" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#confirmDeleteModal" 
                                            data-delete-url="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?action=evidencija_delete&id=<?php echo $evidencija['id']; ?>">
                                            <i class="fa-solid fa-trash me-2"></i>Obriši
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center">Nema rezultata za zadate kriterijume.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if (isset($total_pages) && $total_pages > 1): ?>
<nav aria-label="Navigacija kroz stranice">
    <ul class="pagination justify-content-center">
        <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
            <a class="page-link" href="?page=kontrolor_moji_zapisi&p=<?php echo $current_page - 1; ?>&<?php echo $pagination_query_params; ?>">Prethodna</a>
        </li>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                <a class="page-link" href="?page=kontrolor_moji_zapisi&p=<?php echo $i; ?>&<?php echo $pagination_query_params; ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
            <a class="page-link" href="?page=kontrolor_moji_zapisi&p=<?php echo $current_page + 1; ?>&<?php echo $pagination_query_params; ?>">Sledeća</a>
        </li>
    </ul>
</nav>
<?php endif; ?>