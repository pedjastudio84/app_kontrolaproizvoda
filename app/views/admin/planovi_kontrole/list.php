<?php
// Postavljanje naslova stranice
if (!defined('PAGE_TITLE')) {
    define('PAGE_TITLE', 'Upravljanje Planovima Kontrole');
}
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Pravimo query string za paginaciju koji čuva postojeće parametre pretrage
// kako bi pretraga radila i nakon promene stranice.
$pagination_query_params = http_build_query([
    'search_broj_plana' => $search_params['broj_plana'] ?? '',
    'search_ident' => $search_params['ident'] ?? '',
    'search_kataloska' => $search_params['kataloska'] ?? '',
    'search_naziv' => $search_params['naziv'] ?? '',
]);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="mb-0"><?php echo htmlspecialchars(PAGE_TITLE); ?></h1>
    <a href="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?page=admin_plan_create" class="btn btn-success">Dodaj novi plan</a>
</div>

<?php // Prikaz poruka iz sesije
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
        <i class="fas fa-search"></i> Pretraga Planova Kontrole
    </div>
    <div class="card-body">
        <form action="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php" method="GET">
            <input type="hidden" name="page" value="admin_plans">
            <div class="row align-items-end">
                <div class="col-md-3 mb-2">
                    <label for="search_broj_plana" class="form-label">Broj plana</label>
                    <input type="text" class="form-control" id="search_broj_plana" name="search_broj_plana" placeholder="Unesite broj plana..." value="<?php echo htmlspecialchars($search_params['broj_plana'] ?? ''); ?>">
                </div>
                <div class="col-md-3 mb-2">
                    <label for="search_ident" class="form-label">Ident proizvoda</label>
                    <input type="text" class="form-control" id="search_ident" name="search_ident" placeholder="Unesite ident..." value="<?php echo htmlspecialchars($search_params['ident'] ?? ''); ?>">
                </div>
                <div class="col-md-3 mb-2">
                    <label for="search_kataloska" class="form-label">Kataloška oznaka</label>
                    <input type="text" class="form-control" id="search_kataloska" name="search_kataloska" placeholder="Unesite kat. oznaku..." value="<?php echo htmlspecialchars($search_params['kataloska'] ?? ''); ?>">
                </div>
                <div class="col-md-3 mb-2">
                    <label for="search_naziv" class="form-label">Naziv proizvoda</label>
                    <input type="text" class="form-control" id="search_naziv" name="search_naziv" placeholder="Unesite naziv..." value="<?php echo htmlspecialchars($search_params['naziv'] ?? ''); ?>">
                </div>
            </div>
            <div class="d-flex justify-content-end mt-2">
                <a href="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?page=admin_plans" class="btn btn-secondary me-2">Poništi filtere</a>
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
                <th>Broj Plana</th>
                <th>Ident Proizvoda</th>
                <th>Kataloška oznaka</th>
                <th>Naziv Proizvoda</th>
                <th>Kreirao</th>
                <th>Kreiran</th>
                <th>Akcije</th>
            </tr>
        </thead>
        <tbody>
            <?php if (isset($planovi) && !empty($planovi)): ?>
                <?php foreach ($planovi as $plan): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($plan['id']); ?></td>
                        <td><?php echo htmlspecialchars($plan['broj_plana_kontrole']); ?></td>
                        <td><?php echo htmlspecialchars($plan['ident_proizvoda']); ?></td>
                        <td><?php echo htmlspecialchars($plan['kataloska_oznaka'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($plan['naziv_proizvoda']); ?></td>
                        <td><?php echo htmlspecialchars(trim($plan['kreator_puno_ime']) ?: 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars(date('d.m.Y', strtotime($plan['kreiran_datuma']))); ?></td>
                       <td>
    <div class="btn-group dropend">
        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fa-solid fa-bars"></i>
        </button>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?page=admin_plan_show&id=<?php echo $plan['id']; ?>"><i class="fa-solid fa-eye me-2"></i>Pregledaj</a></li>
            <li><a class="dropdown-item" href="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?page=admin_plan_edit&id=<?php echo $plan['id']; ?>"><i class="fa-solid fa-pen-to-square me-2"></i>Izmeni</a></li>
            <li><a class="dropdown-item" href="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?page=admin_plan_copy&id=<?php echo $plan['id']; ?>"><i class="fa-solid fa-copy me-2"></i>Kopiraj</a></li>
            </li>
        </ul>
    </div>
    </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center">Nema rezultata za zadate kriterijume pretrage.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if (isset($total_pages) && $total_pages > 1): ?>
<nav aria-label="Navigacija kroz stranice">
    <ul class="pagination justify-content-center">
        <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
            <a class="page-link" href="?page=admin_plans&p=<?php echo $current_page - 1; ?>&<?php echo $pagination_query_params; ?>">Prethodna</a>
        </li>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                <a class="page-link" href="?page=admin_plans&p=<?php echo $i; ?>&<?php echo $pagination_query_params; ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>
        
        <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
            <a class="page-link" href="?page=admin_plans&p=<?php echo $current_page + 1; ?>&<?php echo $pagination_query_params; ?>">Sledeća</a>
        </li>
    </ul>
</nav>
<?php endif; ?>