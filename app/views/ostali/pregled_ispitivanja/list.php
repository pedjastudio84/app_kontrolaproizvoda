<?php
if (!defined('PAGE_TITLE')) { define('PAGE_TITLE', 'Pregled Evidencija Kontrole'); }
if (session_status() == PHP_SESSION_NONE) { session_start(); }

// Pravimo query string za paginaciju koji čuva parametre pretrage
$pagination_query_params = http_build_query([
    'search_ident' => $search_params['ident'] ?? '',
    'search_kataloska' => $search_params['kataloska'] ?? '',
    'search_serijski' => $search_params['serijski'] ?? '',
    'search_kontrolor' => $search_params['kontrolor'] ?? '',
]);
?>

<h1><?php echo htmlspecialchars(PAGE_TITLE); ?></h1>
<p class="text-muted">Prikaz svih zapisa o izvršenim kontrolama sa mogućnošću pretrage.</p>

<div class="card mb-4">
    <div class="card-header">Pretraga Evidencija</div>
    <div class="card-body">
        <form action="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php" method="GET">
            <input type="hidden" name="page" value="pregled_svih_zapisa">
            <div class="row align-items-end">
                <div class="col-md-3 mb-2"><input type="text" class="form-control" name="search_ident" placeholder="Ident proizvoda..." value="<?php echo htmlspecialchars($search_params['ident'] ?? ''); ?>"></div>
                <div class="col-md-3 mb-2"><input type="text" class="form-control" name="search_kataloska" placeholder="Kat. oznaka..." value="<?php echo htmlspecialchars($search_params['kataloska'] ?? ''); ?>"></div>
                <div class="col-md-3 mb-2"><input type="text" class="form-control" name="search_serijski" placeholder="Serijski broj..." value="<?php echo htmlspecialchars($search_params['serijski'] ?? ''); ?>"></div>
                <div class="col-md-3 mb-2"><input type="text" class="form-control" name="search_kontrolor" placeholder="Ime kontrolora..." value="<?php echo htmlspecialchars($search_params['kontrolor'] ?? ''); ?>"></div>
            </div>
            <div class="d-flex justify-content-end mt-2">
                <a href="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?page=pregled_svih_zapisa" class="btn btn-secondary me-2">Poništi</a>
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
                            <a href="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?page=kontrolor_zapis_show&id=<?php echo $evidencija['id']; ?>" class="btn btn-info btn-sm" title="Pregledaj detalje">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="9" class="text-center">Nema rezultata za zadate kriterijume.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if (isset($total_pages) && $total_pages > 1): ?>
<nav aria-label="Navigacija kroz stranice">
    <ul class="pagination justify-content-center">
        <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>"><a class="page-link" href="?page=pregled_svih_zapisa&p=<?php echo $current_page - 1; ?>&<?php echo $pagination_query_params; ?>">Prethodna</a></li>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?><li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>"><a class="page-link" href="?page=pregled_svih_zapisa&p=<?php echo $i; ?>&<?php echo $pagination_query_params; ?>"><?php echo $i; ?></a></li><?php endfor; ?>
        <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>"><a class="page-link" href="?page=pregled_svih_zapisa&p=<?php echo $current_page + 1; ?>&<?php echo $pagination_query_params; ?>">Sledeća</a></li>
    </ul>
</nav>
<?php endif; ?>