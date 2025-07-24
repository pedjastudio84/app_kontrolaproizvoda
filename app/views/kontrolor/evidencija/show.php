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
?>
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
                    <a href="#"class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal" data-delete-url="index.php?action=delete&id=<?php echo $plan['id']; ?>"><i class="fa-solid fa-trash me-1"></i>Obriši</a>
                <?php endif; ?>

                <a href="?action=generate_single_report&id=<?php echo $evidencija['id']; ?>" class="btn btn-success" target="_blank" title="Generiši PDF">
            <i class="fa-solid fa-file-pdf me-1"></i>PDF</a>
                </div>
              <div class="btn-group" role="group">
                <a href="javascript:history.back()" class="btn btn-secondary"><i class="fa-solid fa-chevron-left me-1"></i>Nazad</a>
            </div>
        </div>
    </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Osnovni Podaci</div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-4">ID Zapisa:</dt><dd class="col-sm-8"><?php echo $evidencija['id']; ?></dd>
                <dt class="col-sm-4">Vrsta kontrole:</dt><dd class="col-sm-8"><?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($evidencija['vrsta_kontrole']))); ?></dd>
                <dt class="col-sm-4">Datum i vreme:</dt><dd class="col-sm-8"><?php echo date('d.m.Y H:i:s', strtotime($evidencija['datum_vreme_ispitivanja'])); ?></dd>
                <dt class="col-sm-4">Kontrolor:</dt><dd class="col-sm-8"><?php echo htmlspecialchars($evidencija['kontrolor_puno_ime']); ?></dd>
                <dt class="col-sm-4">Korišćen plan:</dt><dd class="col-sm-8"><?php echo htmlspecialchars($evidencija['broj_plana_kontrole'] ?? 'N/A'); ?></dd>

            </dl>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">Podaci o Proizvodu</div>
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

    <div class="card mt-4">
        <div class="card-header">Rezultati Ček-Liste</div>
        <div class="card-body">
            <?php if (!empty($evidencija['rezultati'])): ?>
                <?php
                $trenutnaGrupa = null;
                foreach($evidencija['rezultati'] as $rezultat):
                    if ($trenutnaGrupa !== $rezultat['naziv_grupe']) {
                        if ($trenutnaGrupa !== null) { echo '</div>'; } // Zatvori prethodni card-body
                        $trenutnaGrupa = $rezultat['naziv_grupe'];
                        echo '<h5 class="mt-3"><strong>Grupa: ' . htmlspecialchars($trenutnaGrupa) . '</strong></h5><div class="list-group list-group-flush">';
                    }
                ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                        <div class="me-auto">
                            <span><?php echo htmlspecialchars($rezultat['opis_karakteristike_snapshot'] ?? 'Karakteristika ' . $rezultat['karakteristika_plana_id']); ?></span>
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
                <?php 
                endforeach; 
                if ($trenutnaGrupa !== null) { echo '</div>'; } // Zatvori poslednji card-body
                ?>
            <?php else: ?>
                <p class="text-muted">Nema sačuvanih rezultata za ček-listu.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (!empty($evidencija['fotografije_masine'])): ?>
    <div class="card mt-4">
        <div class="card-header">Fotografije Mašine</div>
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
        <div class="card-header">Ostale Napomene</div>
        <div class="card-body">
            <p><?php echo nl2br(htmlspecialchars($evidencija['ostale_napomene'])); ?></p>
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