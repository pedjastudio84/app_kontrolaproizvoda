<?php
if (!isset($plan) || !$plan) {
    if (session_status() == PHP_SESSION_NONE) { session_start(); }
    $_SESSION['error_message'] = 'Traženi plan nije pronađen.';
    header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=admin_plans');
    exit;
}
if (!defined('PAGE_TITLE')) {
    define('PAGE_TITLE', 'Pregled Plana: ' . htmlspecialchars($plan['broj_plana_kontrole']));
}
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?page=admin_dashboard">Admin Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?page=admin_plans">Upravljanje Planovima Kontrole</a></li>
            <li class="breadcrumb-item active" aria-current="page">Pregled Plana</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h1 class="mb-0"><?php echo htmlspecialchars(PAGE_TITLE); ?></h1>
        <div class="btn-toolbar" role="toolbar">
           <?php if (isset($_SESSION['user_uloga']) && $_SESSION['user_uloga'] === 'administrator'): ?>
            <a href="?page=admin_plan_edit&id=<?php echo $plan['id']; ?>" class="btn btn-primary me-1" title="Izmeni"><i class="fa-solid fa-pen-to-square me-1"></i>Izmeni</a>
            <a href="?page=admin_plan_copy&id=<?php echo $plan['id']; ?>" class="btn btn-warning me-1" title="Kopiraj"><i class="fa-solid fa-copy me-1"></i>Kopiraj</a>
            <a href="#" class="btn btn-danger me-1" title="Obriši"
                   data-bs-toggle="modal" 
                   data-bs-target="#confirmDeleteModal"
                   data-delete-url="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?action=admin_plan_delete&id=<?php echo $plan['id']; ?>"><i class="fa-solid fa-trash me-1"></i>Obriši</a>
        <?php endif; ?>

        <a href="?action=generate_plan_pdf&id=<?php echo $plan['id']; ?>" class="btn btn-success me-1" target="_blank" title="Generiši PDF"><i class="fa-solid fa-file-pdf me-1"></i>PDF</a>
        
        <a href="javascript:history.back()" class="btn btn-secondary me-1"><i class="fa-solid fa-chevron-left me-1"></i>Nazad</a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Osnovni podaci</div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Broj plana kontrole:</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($plan['broj_plana_kontrole']); ?></dd>
                
                <dt class="col-sm-3">Ident proizvoda:</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($plan['ident_proizvoda']); ?></dd>

                <dt class="col-sm-3">Naziv proizvoda:</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($plan['naziv_proizvoda']); ?></dd>

                <dt class="col-sm-3">Kataloška oznaka:</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($plan['kataloska_oznaka'] ?? '-'); ?></dd>
                
                <dt class="col-sm-3">Broj operacije:</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($plan['broj_operacije'] ?? '-'); ?></dd>
            </dl>
        </div>
    </div>

    <h3 class="mt-4">Karakteristike za kontrolu</h3>
    <?php if (!empty($plan['grupe'])): ?>
        <?php foreach ($plan['grupe'] as $grupa): ?>
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <strong>Grupa:</strong> <?php echo htmlspecialchars($grupa['naziv_grupe']); ?>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%;">R.br.</th>
                                <th>Opis</th>
                                <th style="width: 15%;">Fotografija</th>
                                <th style="width: 15%;">Vrsta</th>
                                <th style="width: 20%;">Kontrolni alat/način</th>
                                <th style="width: 15%;">Veličina uzorka</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($grupa['karakteristike'])): ?>
                                <?php foreach ($grupa['karakteristike'] as $karakteristika): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($karakteristika['redni_broj_karakteristike']); ?></td>
                                        <td><?php echo nl2br(htmlspecialchars($karakteristika['opis_karakteristike'])); ?></td>
                                        <td class="text-center">
                                            <?php if (!empty($karakteristika['putanja_fotografije_opis'])): ?>
                                                <a href="#" class="view-image-link" 
                                                   data-bs-toggle="modal" 
                                                   data-bs-target="#imageModal" 
                                                   data-image-url="<?php echo rtrim(APP_URL, '/'); ?>/public/uploads/<?php echo htmlspecialchars($karakteristika['putanja_fotografije_opis']); ?>">
                                                    <img src="<?php echo rtrim(APP_URL, '/'); ?>/public/uploads/<?php echo htmlspecialchars($karakteristika['putanja_fotografije_opis']); ?>" alt="Slika" style="max-width: 100px; max-height: 70px; cursor: pointer;">
                                                </a>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($karakteristika['vrsta_karakteristike']); ?></td>
                                        <td><?php echo htmlspecialchars($karakteristika['kontrolni_alat_nacin'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($karakteristika['velicina_uzorka'] ?? '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center text-muted">Ova grupa nema definisanih karakteristika.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="text-muted">Ovaj plan nema definisanih grupa i karakteristika.</p>
    <?php endif; ?>
</div>

<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="imageModalLabel">Prikaz Slike</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img src="" class="img-fluid" id="modalImage" alt="Slika karakteristike">
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const deleteForm = document.getElementById('deleteForm');

    if (confirmDeleteBtn && deleteForm) {
        confirmDeleteBtn.addEventListener('click', function () {
            deleteForm.submit(); // pokreće formu iz modala
        });
    }
});
</script>