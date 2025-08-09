<?php
// Logika za određivanje moda forme: da li je izmena ili kreiranje novog zapisa
$isEdit = isset($evidencija) && $evidencija;
$hasFormData = isset($formData) && !empty($formData);

// Određivanje vrste kontrole i naslova stranice
if ($isEdit) {
    $vrsta_kontrole = $evidencija['vrsta_kontrole'];
    $pageTitle = 'Izmena Zapisa #' . $evidencija['id'];
    $formDataSource = $evidencija;
    $plan = $evidencija['plan'] ?? null;
} else {
    $vrsta_kontrole = $_GET['vrsta'] ?? ($formData['vrsta_kontrole'] ?? 'nepoznata');
    $vrsta_kontrole_tekst = ($vrsta_kontrole === 'redovna_kontrola') ? 'Redovna kontrola' : 'Kontrola pre isporuke';
    $pageTitle = 'Novi Zapis - ' . $vrsta_kontrole_tekst;
    $formDataSource = $formData;
}

if (!defined('PAGE_TITLE')) {
    define('PAGE_TITLE', $pageTitle);
}
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$rezultati = $isEdit ? $evidencija['rezultati'] : ($formDataSource['rezultati'] ?? []);
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?page=kontrolor_dashboard">Kontrolna tabla</a></li>
            <?php if (!$isEdit): ?>
            <li class="breadcrumb-item"><a href="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?page=kontrolor_biraj_vrstu">Odabir vrste</a></li>
            <?php else: ?>
            <li class="breadcrumb-item"><a href="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?page=kontrolor_moji_zapisi">Moji zapisi</a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active" aria-current="page"><?php echo $isEdit ? 'Izmena zapisa #' . $evidencija['id'] : 'Novi zapis'; ?></li>
        </ol>
    </nav>
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>

    <?php
    if (isset($_SESSION['error_message'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['error_message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        unset($_SESSION['error_message']);
    }
    ?>
    <hr>
    
    <form id="forma-za-evidenciju" class="form-with-unsaved-check" method="POST" action="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?action=<?php echo $isEdit ? 'evidencija_update&id='.$evidencija['id'] : 'evidencija_store'; ?>" enctype="multipart/form-data">
        <input type="hidden" name="vrsta_kontrole" value="<?php echo htmlspecialchars($vrsta_kontrole); ?>">
        <input type="hidden" name="plan_kontrole_id" value="<?php echo htmlspecialchars($formDataSource['plan_kontrole_id'] ?? ($plan['id'] ?? '')); ?>">
    
        <div class="row">
            <div class="col-lg-5 mb-4" <?php if ($isEdit || $hasFormData) echo 'style="display:none;"'; ?>>
                <div class="card h-100">
                    <div class="card-header"><h4>1. Skeniranje QR Koda</h4></div>
                    <div class="card-body text-center d-flex flex-column justify-content-center">
                        <div id="scanner-container" style="display: none;"><video id="video" playsinline></video><div id="scan-box"></div></div>
                        <div id="loadingMessage" class="alert alert-info mt-3">📸 Kamera nije aktivna.</div>
                        <canvas id="canvas" hidden></canvas>
                        <div id="output" class="mt-3">
                            <div id="outputMessage" class="alert alert-warning" hidden>Skenirajte QR kod...</div>
                            <div id="outputDataContainer" class="alert alert-success" hidden><strong>Kod uspešno očitan!</strong><br><span id="outputData"></span></div>
                        </div>
                        <div class="mt-2">
                            <button type="button" id="startScanBtn" class="btn btn-success"><i class="fa-solid fa-camera me-1"></i>Pokreni kameru</button>
                            <button type="button" id="stopScanBtn" class="btn btn-danger" style="display: none;">Zaustavi Kameru</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="<?php echo ($isEdit || $hasFormData) ? 'col-lg-12' : 'col-lg-7'; ?> mb-4">
                <div class="card h-100">
                    <div class="card-header"><h4><?php echo ($isEdit || $hasFormData) ? 'Podaci o Proizvodu' : '2. Podaci o Proizvodu'; ?></h4></div>
                    <div class="card-body">
                        <p class="text-muted" <?php if ($isEdit || $hasFormData) echo 'style="display:none;"'; ?>>Skenirajte QR kod ili unesite podatke ručno.</p>
                        
                        <?php if ($isEdit && $plan): ?>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Broj Plana Kontrole</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($plan['broj_plana_kontrole']); ?>" readonly>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ident</label>
                                <input type="text" class="form-control" id="ident" name="ident" value="<?php echo htmlspecialchars($formDataSource['product_ident_sken'] ?? ($formDataSource['ident'] ?? '')); ?>" <?php if (!$isEdit) echo 'readonly'; ?> required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kataloška oznaka</label>
                                <input type="text" class="form-control" id="kataloska_oznaka" name="kataloska_oznaka" value="<?php echo htmlspecialchars($formDataSource['product_kataloska_oznaka_sken'] ?? ($formDataSource['kataloska_oznaka'] ?? '')); ?>" <?php if (!$isEdit) echo 'readonly'; ?>>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Naziv</label>
                                <input type="text" class="form-control" id="naziv" name="naziv" value="<?php echo htmlspecialchars($formDataSource['product_naziv_sken'] ?? ($formDataSource['naziv'] ?? '')); ?>" <?php if (!$isEdit) echo 'readonly'; ?>>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Serijski broj</label>
                                <input type="text" class="form-control" id="serijski_broj" name="serijski_broj" value="<?php echo htmlspecialchars($formDataSource['product_serijski_broj_sken'] ?? ($formDataSource['serijski_broj'] ?? '')); ?>" <?php if (!$isEdit) echo 'readonly'; ?> required>
                            </div>
                        </div>
                        
                        <button type="button" id="unlockFieldsBtn" class="btn btn-outline-secondary mt-2" <?php if ($isEdit || $hasFormData) echo 'style="display:none;"'; ?>><i class="fa-solid fa-unlock me-1"></i>Otključaj za ručni unos</button>
                    </div>
                </div>
            </div>
        </div>
        
        <hr>
        <div id="checklist-kontejner">
            <h4>3. Ček Lista <?php if ($isEdit && $plan) { echo "(Plan: " . htmlspecialchars($plan['broj_plana_kontrole']) . " | Verzija: " . htmlspecialchars($plan['verzija_broj']) . ")"; } ?></h4>

            <?php if (($isEdit || $hasFormData) && isset($plan['grupe'])): ?>
                <?php foreach($plan['grupe'] as $grupa): ?>
                    <div class="card mb-3"><div class="card-header bg-light"><strong>Grupa: <?php echo htmlspecialchars($grupa['naziv_grupe']); ?></strong></div><div class="card-body">
                        <?php foreach($grupa['karakteristike'] as $kar): 
                            $sacuvan_rezultat = '';
                            $sacuvana_napomena = '';
                            if ($isEdit) {
                                foreach($rezultati as $rez) { 
                                    if (isset($rez['karakteristika_plana_id']) && $rez['karakteristika_plana_id'] == $kar['id']) { 
                                        $sacuvan_rezultat = $rez['rezultat_ok_nok'] ?? $rez['rezultat_tekst']; 
                                        $sacuvana_napomena = $rez['napomena'] ?? '';
                                        break; 
                                    }
                                }
                            } else {
                                $sacuvan_rezultat = $rezultati[$kar['id']]['vrednost'] ?? '';
                                $sacuvana_napomena = $rezultati[$kar['id']]['napomena'] ?? '';
                            }
                        ?>
                            <div class="mb-3 p-2 border-bottom">
                                <label class="form-label d-block"><strong><?php echo $kar['redni_broj_karakteristike']; ?>. <?php echo htmlspecialchars($kar['opis_karakteristike']); ?></strong></label>
                                <?php if (!empty($kar['kontrolni_alat_nacin'])): ?>
                                    <span class="d-block text-muted small mt-1"><i class="fa-solid fa-wrench me-1"></i><strong>Alat/Način:</strong> <?php echo htmlspecialchars($kar['kontrolni_alat_nacin']); ?></span>
                                <?php endif; ?>
                                <input type="hidden" name="rezultati[<?php echo $kar['id']; ?>][opis_snapshot]" value="<?php echo htmlspecialchars($kar['opis_karakteristike']); ?>">
                                <?php if ($kar['putanja_fotografije_opis']): ?>
                                    <div class="mb-2"><a href="#" class="view-image-link" data-bs-toggle="modal" data-bs-target="#imageModal" data-image-url="<?php echo rtrim(APP_URL, '/'); ?>/public/uploads/<?php echo htmlspecialchars($kar['putanja_fotografije_opis']); ?>"><img src="<?php echo rtrim(APP_URL, '/'); ?>/public/uploads/<?php echo htmlspecialchars($kar['putanja_fotografije_opis']); ?>" alt="Referentna slika" class="img-thumbnail" style="max-height: 150px; cursor: pointer;"></a></div>
                                <?php endif; ?>
                                
                                <div class="row gx-2 align-items-center mt-2">
                                    <div class="col-md-5">
                                        <?php if ($kar['vrsta_karakteristike'] === 'OK/NOK'): ?>
                                            <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="rezultati[<?php echo $kar['id']; ?>][vrednost]" id="ok_<?php echo $kar['id']; ?>" value="OK" <?php if($sacuvan_rezultat == 'OK') echo 'checked'; ?> required><label class="form-check-label" for="ok_<?php echo $kar['id']; ?>">OK</label></div>
                                            <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="rezultati[<?php echo $kar['id']; ?>][vrednost]" id="nok_<?php echo $kar['id']; ?>" value="NOK" <?php if($sacuvan_rezultat == 'NOK') echo 'checked'; ?>><label class="form-check-label" for="nok_<?php echo $kar['id']; ?>">NOK</label></div>
                                        <?php else: ?>
                                            <textarea class="form-control" name="rezultati[<?php echo $kar['id']; ?>][vrednost]" rows="2" placeholder="Rezultati ispitivanja..." required><?php echo htmlspecialchars($sacuvan_rezultat); ?></textarea>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" name="rezultati[<?php echo $kar['id']; ?>][napomena]" placeholder="Dodaj napomenu (opciono)..." value="<?php echo htmlspecialchars($sacuvana_napomena); ?>">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div></div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted" id="checklist-placeholder">Nakon unosa identa proizvoda, ovde će se učitati ček lista...</p>
            <?php endif; ?>
        </div>
        
        <hr>
        <div id="masina-foto-kontejner">
             <h4>4. Fotografije Mašine</h4>
            <?php if ($isEdit && !empty($evidencija['fotografije_masine'])): ?>
                <p>Postojeće fotografije (kliknite na 'X' da obrišete):</p>
                <div class="row mb-3" id="existing-photos-container">
                    <?php foreach($evidencija['fotografije_masine'] as $foto): ?>
                        <div class="col-auto existing-photo-wrapper" id="photo-wrapper-<?php echo $foto['id']; ?>">
                            <div class="position-relative"><a href="<?php echo rtrim(APP_URL, '/'); ?>/public/uploads/<?php echo htmlspecialchars($foto['putanja_fotografije']); ?>" target="_blank"><img src="<?php echo rtrim(APP_URL, '/'); ?>/public/uploads/<?php echo htmlspecialchars($foto['putanja_fotografije']); ?>" class="img-thumbnail" style="height: 100px;"></a><button type="button" class="btn btn-danger btn-sm position-absolute top-0 start-100 translate-middle rounded-circle delete-photo-btn" data-photo-id="<?php echo $foto['id']; ?>">X</button></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <hr>
                <p>Dodaj nove fotografije:</p>
            <?php endif; ?>
            <div id="photo-inputs-container">
                <div class="mb-3">
                    <input type="file" class="form-control w-100" name="masina_foto[]" accept="image/*">
                    <div class="compression-feedback text-muted small mt-1"></div>
                </div>
            </div>
           <button type="button" id="add-photo-btn" class="btn btn-secondary mt-2"><i class="fa-solid fa-plus me-1"></i>Dodaj još jednu sliku</button>
            <p class="form-text text-muted">Maksimalno 5 fotografija. Fotografije će biti automatski kompresovane pre slanja.</p>
        
        <hr>
        <?php if ($vrsta_kontrole === 'kontrola_pre_isporuke'): ?>
            <div id="ime-kupca-kontejner">
                <h4>5. Podaci o Kupcu</h4>
                <div class="mb-3">
                    <label for="ime_kupca" class="form-label">Ime Kupca (opciono)</label>
                    <input type="text" class="form-control" id="ime_kupca" name="ime_kupca" value="<?php echo htmlspecialchars($formDataSource['ime_kupca'] ?? ''); ?>">
                </div>
            </div>
            <hr>
        <?php endif; ?>
        <div id="ostale-napomene-kontejner">
            <h4><?php echo ($vrsta_kontrole === 'kontrola_pre_isporuke') ? '6.' : '5.'; ?> Ostale napomene</h4>
            <textarea class="form-control" name="ostale_napomene" rows="4" placeholder="Unesite ostale napomene ukoliko ih ima..."><?php echo htmlspecialchars($formDataSource['ostale_napomene'] ?? ''); ?></textarea>
        </div>

        <div class="mt-4">
            <a href="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?page=kontrolor_moji_zapisi" class="btn btn-danger cancel-link"><i class="fa-solid fa-xmark me-1"></i>Odustani</a>
            <button type="submit" id="submit-btn" class="btn btn-success"> <i class="fa-solid fa-floppy-disk me-1"></i><?php echo $isEdit ? 'Sačuvaj izmene' : 'Sačuvaj evidenciju'; ?></button>
        </div>
    </form>
    
    <?php if (isset($_SESSION['form_data'])) { unset($_SESSION['form_data']); } ?>

    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="imageModalLabel">Prikaz Slike</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body text-center"><img src="" class="img-fluid" id="modalImage" alt="Slika karakteristike"></div>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo rtrim(APP_URL, '/'); ?>/public/js/jsQR.js"></script>
<script src="https://cdn.jsdelivr.net/npm/browser-image-compression@2.0.1/dist/browser-image-compression.js"></script>
<audio id="beepSound" src="<?php echo rtrim(APP_URL, '/'); ?>/public/sounds/beep.mp3"></audio>

<script>
    const pageConfig = {
        isEdit: <?php echo $isEdit ? 'true' : 'false'; ?>,
        hasFormData: <?php echo $hasFormData ? 'true' : 'false'; ?>,
        appUrl: "<?php echo rtrim(APP_URL, '/'); ?>"
    };
</script>

<script src="<?php echo rtrim(APP_URL, '/'); ?>/public/js/kontrolor-form.js"></script>