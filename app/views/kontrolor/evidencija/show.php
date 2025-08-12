<?php
if (!isset($evidencija) || !$evidencija) {
    if (session_status() == PHP_SESSION_NONE) { session_start(); }
    $_SESSION['error_message'] = 'Traženi zapis nije pronađen.';
    header('Location: ' . rtrim(APP_URL, '/').'/public/index.php?page=kontrolor_moji_zapisi');
    exit;
}
if (!defined('PAGE_TITLE')) {
    define('PAGE_TITLE', 'Pregled Zapisa #' . $evidencija['id']);
}
if (session_status() == PHP_SESSION_NONE) { session_start(); }

// Helper funkcija za prevođenje vrste kontrole
function formatirajVrstuKontrole($vrsta) {
    switch ($vrsta) {
        case 'redovna_kontrola':
            return 'Redovna';
        case 'kontrola_pre_isporuke':
            return 'Pre isporuke';
        case 'vanredna_kontrola':
            return 'Vanredna';
        default:
            return ucfirst(str_replace('_', ' ', $vrsta));
    }
}
?>

<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h1 class="mb-0"><?php echo htmlspecialchars(PAGE_TITLE); ?></h1>
        
        <div class="btn-toolbar" role="toolbar">
            <div class="btn-group me-2" role="group">
                <?php
                $mozeDaMenja = (isset($_SESSION['user_uloga']) && $_SESSION['user_uloga'] === 'administrator') || 
                                (isset($_SESSION['user_uloga']) && $_SESSION['user_uloga'] === 'kontrolor' && isset($evidencija['kontrolor_id']) && $_SESSION['user_id'] == $evidencija['kontrolor_id']);
                
                if ($mozeDaMenja):
                ?>
                    <a href="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?page=kontrolor_zapis_edit&id=<?php echo $evidencija['id']; ?>" class="btn btn-primary" title="Izmeni"><i class="fa-solid fa-pen-to-square me-1"></i>Izmeni</a>
                    <a href="#"class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal" data-delete-url="index.php?action=evidencija_delete&id=<?php echo $evidencija['id']; ?>"><i class="fa-solid fa-trash me-1"></i>Obriši</a>
                <?php endif; ?>

                <a href="?action=generate_single_report&id=<?php echo $evidencija['id']; ?>" class="btn btn-success" target="_blank" title="Generiši PDF">
            <i class="fa-solid fa-file-pdf me-1"></i>PDF</a>
                </div>
              <div class="btn-group" role="group">
                <a href="javascript:history.back()" class="btn btn-secondary"><i class="fa-solid fa-chevron-left me-1"></i>Nazad</a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header"><i class="fa-solid fa-file-invoice me-2"></i>Osnovni Podaci</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">ID Zapisa:</dt><dd class="col-sm-8">#<?php echo $evidencija['id']; ?></dd>
                        <dt class="col-sm-4">Vrsta kontrole:</dt><dd class="col-sm-8"><?php echo htmlspecialchars(formatirajVrstuKontrole($evidencija['vrsta_kontrole'])); ?></dd>
                        <dt class="col-sm-4">Datum i vreme:</dt><dd class="col-sm-8"><?php echo date('d.m.Y H:i:s', strtotime($evidencija['datum_vreme_ispitivanja'])); ?></dd>
                        <dt class="col-sm-4">Kontrolor:</dt><dd class="col-sm-8"><?php echo htmlspecialchars($evidencija['kontrolor_puno_ime']); ?></dd>
                        <dt class="col-sm-4">Korišćen plan:</dt><dd class="col-sm-8"><?php echo htmlspecialchars($evidencija['plan']['broj_plana_kontrole'] ?? 'N/A'); ?> (Verzija: <?php echo htmlspecialchars($evidencija['plan']['verzija_broj'] ?? 'N/A'); ?>)</dd>
                    </dl>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header"><i class="fa-solid fa-box-archive me-2"></i>Podaci o Proizvodu</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Ident:</dt><dd class="col-sm-8"><?php echo htmlspecialchars($evidencija['product_ident_sken']); ?></dd>
                        <dt class="col-sm-4">Naziv:</dt><dd class="col-sm-8"><?php echo htmlspecialchars($evidencija['product_naziv_sken']); ?></dd>
                        <dt class="col-sm-4">Kataloška oznaka:</dt><dd class="col-sm-8"><?php echo htmlspecialchars($evidencija['product_kataloska_oznaka_sken']); ?></dd>
                        <dt class="col-sm-4">Serijski broj:</dt><dd class="col-sm-8"><?php echo htmlspecialchars($evidencija['product_serijski_broj_sken']); ?></dd>
                        <?php if (!empty($evidencija['ime_kupca'])): ?>
                            <dt class="col-sm-4">Ime Kupca:</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($evidencija['ime_kupca']); ?></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-tasks me-2"></i>Rezultati Ček-Liste</div>
        <div class="card-body">
            <?php if (!empty($evidencija['rezultati'])): ?>
                <?php
                $trenutnaGrupa = null;
                foreach($evidencija['rezultati'] as $rezultat):
                    if ($trenutnaGrupa !== $rezultat['naziv_grupe']) {
                        if ($trenutnaGrupa !== null) { echo '</div>'; } 
                        $trenutnaGrupa = $rezultat['naziv_grupe'];
                        // ===== IZMENJENA LINIJA (UKLONJENA REČ "GRUPA" I PLAVA BOJA) =====
                        echo '<h5 class="mt-3"><i class="fa-solid fa-layer-group me-2"></i><strong>' . htmlspecialchars($trenutnaGrupa) . '</strong></h5><div class="list-group list-group-flush">';
                    }
                ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <div class="me-auto">
                                <span><strong><?php echo htmlspecialchars($rezultat['redni_broj_karakteristike'] ?? ''); ?>.</strong> <?php echo htmlspecialchars($rezultat['opis_karakteristike_snapshot'] ?? 'Karakteristika ' . $rezultat['karakteristika_plana_id']); ?></span>
                                <?php if (!empty($rezultat['kontrolni_alat_nacin'])): ?>
                                    <span class="d-block text-muted small mt-1">
                                        <i class="fa-solid fa-wrench me-1"></i><strong>Alat/Način:</strong> <?php echo htmlspecialchars($rezultat['kontrolni_alat_nacin']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="ms-3">
                                <?php
                                    $rezultat_prikaz = $rezultat['rezultat_ok_nok'] ?? $rezultat['rezultat_tekst'];
                                    $badge_class = 'bg-secondary';
                                    if ($rezultat_prikaz === 'OK') $badge_class = 'bg-success';
                                    if ($rezultat_prikaz === 'NOK') $badge_class = 'bg-danger';
                                ?>
                                <span class="badge <?php echo $badge_class; ?> fs-6"><?php echo htmlspecialchars($rezultat_prikaz); ?></span>
                            </div>
                        </div>
                        
                        <?php if (!empty($rezultat['napomena'])): ?>
                            <div class="mt-2 ps-3 border-start border-3" style="border-color: #6c757d !important;">
                                <p class="mb-0 text-muted fst-italic">
                                    <i class="fa-solid fa-comment-dots me-1"></i>
                                    <?php echo htmlspecialchars($rezultat['napomena']); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                        </div>
                <?php 
                endforeach; 
                if ($trenutnaGrupa !== null) { echo '</div>'; }
                ?>
            <?php else: ?>
                <p class="text-muted">Nema sačuvanih rezultata za ček-listu.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (!empty($evidencija['fotografije_masine'])): ?>
    <div class="card mt-4">
        <div class="card-header"><i class="fa-solid fa-images me-2"></i>Fotografije Mašine</div>
        <div class="card-body">
            <div class="row">
            <?php foreach($evidencija['fotografije_masine'] as $foto): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                    <?php 
                        $image_url = rtrim(APP_URL, '/') . '/public/uploads/' . htmlspecialchars($foto['putanja_fotografije']);
                    ?>
                    <a href="#" class="view-image-link" 
                       data-bs-toggle="modal" 
                       data-bs-target="#imageModal" 
                       data-image-url="<?php echo $image_url; ?>">
                        <img src="<?php echo $image_url; ?>" class="img-thumbnail" alt="Slika mašine" style="cursor: pointer; width: 100%; height: 150px; object-fit: cover;">
                    </a>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($evidencija['ostale_napomene'])): ?>
    <div class="card mt-4">
        <div class="card-header"><i class="fa-solid fa-pen-alt me-2"></i>Ostale Napomene</div>
        <div class="card-body">
            <p><?php echo nl2br(htmlspecialchars($evidencija['ostale_napomene'])); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($istorija) && !empty($istorija)): ?>
    <div class="card mt-4">
        <div class="card-header">
            <h4><i class="fa-solid fa-history me-2"></i>Istorija kontrola za ovaj proizvod</h4>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Datum i vreme</th>
                            <th>Vrsta kontrole</th>
                            <th>Kontrolor</th>
                            <th class="text-end">Akcije</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($istorija as $stari_zapis): ?>
                            <tr>
                                <td>#<?php echo $stari_zapis['id']; ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($stari_zapis['datum_vreme_ispitivanja'])); ?></td>
                                <td><?php echo formatirajVrstuKontrole($stari_zapis['vrsta_kontrole']); ?></td>
                                <td><?php echo htmlspecialchars($stari_zapis['kontrolor_puno_ime'] ?? 'N/A'); ?></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?page=kontrolor_zapis_show&id=<?php echo $stari_zapis['id']; ?>" class="btn btn-outline-primary" title="Pregledaj"><i class="fa-solid fa-eye"></i></a>
                                        <a href="?action=generate_single_report&id=<?php echo $stari_zapis['id']; ?>" class="btn btn-outline-success" target="_blank" title="Generiši PDF"><i class="fa-solid fa-file-pdf"></i></a>
                                        <?php if (isset($_SESSION['user_uloga']) && $_SESSION['user_uloga'] === 'administrator'): ?>
                                            <a href="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?page=kontrolor_zapis_edit&id=<?php echo $stari_zapis['id']; ?>" class="btn btn-outline-secondary" title="Izmeni"><i class="fa-solid fa-pen"></i></a>
                                            <a href="#" class="btn btn-outline-danger" title="Obriši" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal" data-delete-url="index.php?action=evidencija_delete&id=<?php echo $stari_zapis['id']; ?>"><i class="fa-solid fa-trash"></i></a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="imageModalLabel">Prikaz Slike</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img src="" class="img-fluid" id="modalImage" alt="Slika mašine">
      </div>
    </div>
  </div>
</div>