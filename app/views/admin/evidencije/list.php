<?php
if (!defined('PAGE_TITLE')) { define('PAGE_TITLE', 'Pregled Svih Evidencija'); }
if (session_status() == PHP_SESSION_NONE) { session_start(); }

$pagination_query_params = http_build_query([
    'search' => $search_params['query'] ?? '',
    'search_kontrolor' => $search_params['kontrolor'] ?? '',
]);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><?php echo htmlspecialchars(PAGE_TITLE); ?></h1>
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

<form action="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php" method="GET" class="mb-4">
    <input type="hidden" name="page" value="admin_evidencije">
    <div class="d-flex flex-wrap align-items-center gap-2">
        <div class="flex-grow-1" style="min-width: 250px;">
            <input type="text" 
                   name="search" 
                   class="form-control" 
                   placeholder="Pretraži proizvod (ident, kat. oznaka, ser. broj)..."
                   value="<?php echo htmlspecialchars($search_params['query'] ?? ''); ?>">
        </div>
        <div class="flex-grow-1" style="min-width: 200px;">
            <input type="text" 
                   name="search_kontrolor" 
                   class="form-control" 
                   placeholder="Pretraži po kontroloru..."
                   value="<?php echo htmlspecialchars($search_params['kontrolor'] ?? ''); ?>">
        </div>
        <div class="btn-group">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-search me-1"></i>Pretraži
            </button>
            <a href="?page=admin_evidencije" class="btn btn-outline-secondary" title="Poništi filtere">
                <i class="fa-solid fa-xmark"></i>
            </a>
        </div>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-striped table-bordered table-hover">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Kontrolor</th>
                <th>Vrsta Kontrole</th>
                <th>Ident</th>
                <th>Kataloška oznaka</th>
                <th>Naziv Proizvoda</th>
                <th>Serijski Broj</th>
                <th>Datum</th>
                <th>Akcije</th>
            </tr>
        </thead>
        <tbody>
            <?php if (isset($evidencije) && !empty($evidencije)): ?>
                <?php foreach ($evidencije as $evidencija): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($evidencija['id']); ?></td>
                        <td><?php echo htmlspecialchars($evidencija['kontrolor_puno_ime']); ?></td>
                        <td><?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($evidencija['vrsta_kontrole']))); ?></td>
                        <td><?php echo htmlspecialchars($evidencija['product_ident_sken']); ?></td>
                        <td><?php echo htmlspecialchars($evidencija['product_kataloska_oznaka_sken'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($evidencija['product_naziv_sken']); ?></td>
                        <td><?php echo htmlspecialchars($evidencija['product_serijski_broj_sken']); ?></td>
                        <td><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($evidencija['datum_vreme_ispitivanja']))); ?></td>
                        <td>
                            <div class="btn-group dropend">
                                <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-bars"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?page=kontrolor_zapis_show&id=<?php echo $evidencija['id']; ?>">
                                            <i class="fa-solid fa-eye me-2"></i>Pregledaj
                                        </a>
                                    </li>
                                    <?php // ===== ISPRAVKA: Prikazujemo linkove samo adminu ===== ?>
                                    <?php if (isset($_SESSION['user_uloga']) && $_SESSION['user_uloga'] === 'administrator'): ?>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?page=kontrolor_zapis_edit&id=<?php echo $evidencija['id']; ?>">
                                            <i class="fa-solid fa-pen-to-square me-2"></i>Izmeni
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="#" 
                                           data-bs-toggle="modal" 
                                           data-bs-target="#confirmDeleteModal" 
                                           data-delete-url="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?action=evidencija_delete&id=<?php echo $evidencija['id']; ?>">
                                           <i class="fa-solid fa-trash me-2"></i>Obriši
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php // ===== KRAJ ISPRAVKE ===== ?>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" class="text-center">Nema rezultata za zadate kriterijume.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if (isset($total_pages) && $total_pages > 1): ?>
<nav aria-label="Navigacija kroz stranice">
    <ul class="pagination justify-content-center">
        <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
            <a class="page-link" href="?page=admin_evidencije&p=<?php echo $current_page - 1; ?>&<?php echo $pagination_query_params; ?>">Prethodna</a>
        </li>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                <a class="page-link" href="?page=admin_evidencije&p=<?php echo $i; ?>&<?php echo $pagination_query_params; ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
            <a class="page-link" href="?page=admin_evidencije&p=<?php echo $current_page + 1; ?>&<?php echo $pagination_query_params; ?>">Sledeća</a>
        </li>
    </ul>
</nav>
<?php endif; ?>