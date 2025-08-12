<?php 
if (!defined('PAGE_TITLE')) { 
    define('PAGE_TITLE', 'Pregled Planova Kontrole'); 
} 

// Ažuriramo query string za paginaciju da koristi novi, univerzalni parametar
$pagination_query_params = http_build_query([
    'search' => $search_params['query'] ?? '',
]);
?>

<h1><?php echo htmlspecialchars(PAGE_TITLE); ?></h1>

<form action="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php" method="GET" class="mb-4">
    <input type="hidden" name="page" value="pregled_planova">
    <div class="input-group">
        <input type="text" 
               name="search" 
               class="form-control" 
               placeholder="Pretraži planove (broj, ident, kat. oznaka, naziv)..."
               value="<?php echo htmlspecialchars($search_params['query'] ?? ''); ?>">
        <button type="submit" class="btn btn-primary px-4"><i class="fa-solid fa-search"></i></button>
        <a href="?page=pregled_planova" class="btn btn-outline-secondary" title="Poništi filtere"><i class="fa-solid fa-xmark"></i></a>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-striped table-bordered table-hover">
        <thead class="table-dark">
            <tr>
                <th>Broj plana</th>
                <th>Ident</th>
                <th>Naziv proizvoda</th>
                <th>Kreirao</th>
                <th>Datum kreiranja</th>
                <th>Akcije</th>
            </tr>
        </thead>
        <tbody>
            <?php if (isset($planovi) && !empty($planovi)): ?>
                <?php foreach ($planovi as $plan): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($plan['broj_plana_kontrole']); ?></td>
                        <td><?php echo htmlspecialchars($plan['ident_proizvoda']); ?></td>
                        <td><?php echo htmlspecialchars($plan['naziv_proizvoda']); ?></td>
                        <td><?php echo htmlspecialchars($plan['kreator_puno_ime']); ?></td>
                        <td><?php echo htmlspecialchars(date('d.m.Y', strtotime($plan['kreiran_datuma']))); ?></td>
                        <td>
                            <a href="?page=pregled_plana_detalji&id=<?php echo $plan['id']; ?>" class="btn btn-info btn-sm" title="Pregledaj">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" class="text-center">Nema rezultata za zadate kriterijume pretrage.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if (isset($total_pages) && $total_pages > 1): ?>
<nav aria-label="Navigacija kroz stranice">
    <ul class="pagination justify-content-center">
        <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
            <a class="page-link" href="?page=pregled_planova&p=<?php echo $current_page - 1; ?>&<?php echo $pagination_query_params; ?>">Prethodna</a>
        </li>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                <a class="page-link" href="?page=pregled_planova&p=<?php echo $i; ?>&<?php echo $pagination_query_params; ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
            <a class="page-link" href="?page=pregled_planova&p=<?php echo $current_page + 1; ?>&<?php echo $pagination_query_params; ?>">Sledeća</a>
        </li>
    </ul>
</nav>
<?php endif; ?>