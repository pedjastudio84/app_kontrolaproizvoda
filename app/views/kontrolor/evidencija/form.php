<?php
// Logika za određivanje moda forme: da li je izmena ili kreiranje novog zapisa
$isEdit = isset($evidencija) && $evidencija;
$hasFormData = isset($formData) && !empty($formData);

// Određivanje vrste kontrole i naslova stranice
if ($isEdit) {
    $vrsta_kontrole = $evidencija['vrsta_kontrole'];
    $pageTitle = 'Izmena Zapisa #' . $evidencija['id'];
    $formDataSource = $evidencija;
} else {
    // Ako je kreiranje (ili neuspela validacija), koristimo podatke iz sesije ili prazne vrednosti
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

// Ako je neuspela validacija, rezultati su u $formDataSource['rezultati']
// Ako je izmena, rezultati su u $evidencija['rezultati']
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
                        <div class="mb-3"><label class="form-label">Ident</label><input type="text" class="form-control" id="ident" name="ident" value="<?php echo htmlspecialchars($formDataSource['product_ident_sken'] ?? ($formDataSource['ident'] ?? '')); ?>" <?php if (!$isEdit) echo 'readonly'; ?> required></div>
                        <div class="mb-3"><label class="form-label">Kataloška oznaka</label><input type="text" class="form-control" id="kataloska_oznaka" name="kataloska_oznaka" value="<?php echo htmlspecialchars($formDataSource['product_kataloska_oznaka_sken'] ?? ($formDataSource['kataloska_oznaka'] ?? '')); ?>" <?php if (!$isEdit) echo 'readonly'; ?>></div>
                        <div class="mb-3"><label class="form-label">Naziv</label><input type="text" class="form-control" id="naziv" name="naziv" value="<?php echo htmlspecialchars($formDataSource['product_naziv_sken'] ?? ($formDataSource['naziv'] ?? '')); ?>" <?php if (!$isEdit) echo 'readonly'; ?>></div>
                        <div class="mb-3"><label class="form-label">Serijski broj</label><input type="text" class="form-control" id="serijski_broj" name="serijski_broj" value="<?php echo htmlspecialchars($formDataSource['product_serijski_broj_sken'] ?? ($formDataSource['serijski_broj'] ?? '')); ?>" <?php if (!$isEdit) echo 'readonly'; ?> required></div>
                        <button type="button" id="unlockFieldsBtn" class="btn btn-outline-secondary"  <?php if ($isEdit || $hasFormData) echo 'style="display:none;"'; ?>><i class="fa-solid fa-unlock me-1"></i>Otključaj za ručni unos</button>
                    </div>
                </div>
            </div>
        </div>
        
        <hr>
        <div id="checklist-kontejner">
            <h4>3. Ček Lista</h4>
            <?php if (($isEdit || $hasFormData) && isset($plan['grupe'])): ?>
                <?php foreach($plan['grupe'] as $grupa): ?>
                    <div class="card mb-3"><div class="card-header bg-light"><strong>Grupa: <?php echo htmlspecialchars($grupa['naziv_grupe']); ?></strong></div><div class="card-body">
                        <?php foreach($grupa['karakteristike'] as $kar): 
                            $sacuvan_rezultat = '';
                            if ($isEdit) {
                                foreach($rezultati as $rez) { if ($rez['karakteristika_plana_id'] == $kar['id']) { $sacuvan_rezultat = $rez['rezultat_ok_nok'] ?? $rez['rezultat_tekst']; break; }}
                            } else { // hasFormData
                                $sacuvan_rezultat = $rezultati[$kar['id']]['vrednost'] ?? '';
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
                                <?php if ($kar['vrsta_karakteristike'] === 'OK/NOK'): ?>
                                    <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="rezultati[<?php echo $kar['id']; ?>][vrednost]" id="ok_<?php echo $kar['id']; ?>" value="OK" <?php if($sacuvan_rezultat == 'OK') echo 'checked'; ?> required><label class="form-check-label" for="ok_<?php echo $kar['id']; ?>">OK</label></div>
                                    <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="rezultati[<?php echo $kar['id']; ?>][vrednost]" id="nok_<?php echo $kar['id']; ?>" value="NOK" <?php if($sacuvan_rezultat == 'NOK') echo 'checked'; ?>><label class="form-check-label" for="nok_<?php echo $kar['id']; ?>">NOK</label></div>
                                <?php else: ?>
                                    <textarea class="form-control mt-2" name="rezultati[<?php echo $kar['id']; ?>][vrednost]" rows="2" placeholder="Unesite tekstualni opis..." required><?php echo htmlspecialchars($sacuvan_rezultat); ?></textarea>
                                <?php endif; ?>
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
            <h4>6. Ostale napomene</h4>
            <textarea class="form-control" name="ostale_napomene" rows="4" placeholder="Unesite ostale napomene ukoliko ih ima..."><?php echo htmlspecialchars($formDataSource['ostale_napomene'] ?? ''); ?></textarea>
        </div>

        <div class="mt-4">
            <a href="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?page=kontrolor_moji_zapisi" class="btn btn-danger cancel-link"><i class="fa-solid fa-xmark me-1"></i>Odustani</a>
            <button type="submit" id="submit-btn" class="btn btn-success"> <i class="fa-solid fa-floppy-disk me-1"></i><?php echo $isEdit ? 'Sačuvaj izmene' : 'Sačuvaj evidenciju'; ?></button>
        </div>
    </form>
</div>

<?php if (isset($_SESSION['form_data'])) { unset($_SESSION['form_data']); } ?>

<script src="<?php echo rtrim(APP_URL, '/'); ?>/public/js/jsQR.js"></script>
<audio id="beepSound" src="<?php echo rtrim(APP_URL, '/'); ?>/public/sounds/beep.mp3"></audio>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const isEditMode = <?php echo $isEdit ? 'true' : 'false'; ?>;
    const hasFormData = <?php echo $hasFormData ? 'true' : 'false'; ?>;
    const formaZaEvidenciju = document.getElementById('forma-za-evidenciju');
    const photoInputsContainer = document.getElementById('photo-inputs-container');
    let compressedFiles = []; 
    const identInput = document.getElementById('ident');
    const kataloskaOznakaInput = document.getElementById('kataloska_oznaka');
    const serijskiBrojInput = document.getElementById('serijski_broj');
    const APP_URL_BASE = "<?php echo rtrim(APP_URL, '/'); ?>";

    async function handleImageUpload(fileInput) {
        const imageFile = fileInput.files[0];
        if (!imageFile) return;
        let feedbackEl = fileInput.parentElement.querySelector('.compression-feedback');
        if (!feedbackEl) {
            feedbackEl = document.createElement('span');
            feedbackEl.className = 'compression-feedback ms-2 text-muted small';
            feedbackEl.style.minWidth = '150px';
            fileInput.parentElement.appendChild(feedbackEl);
        }
        feedbackEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Kompresija...';
        const options = { maxSizeMB: 1, maxWidthOrHeight: 1920, useWebWorker: true };
        try {
            const compressedFile = await imageCompression(imageFile, options);
            const allInputs = Array.from(photoInputsContainer.querySelectorAll('input[type="file"]'));
            const fileIndex = allInputs.indexOf(fileInput);
            
            const datum = new Date().toISOString().slice(0, 10).replace(/-/g, "");
            const vreme = new Date().toTimeString().slice(0,8).replace(/:/g, "");
            const ident = identInput.value || 'ident';
            const kataloska = kataloskaOznakaInput.value || 'kat';
            const serijski = serijskiBrojInput.value || 'sn';
            const originalExtension = imageFile.name.split('.').pop();
            const noviNazivFajla = `${datum}${vreme}_${ident}_${kataloska}_${serijski}_${fileIndex + 1}.${originalExtension}`;
            
            compressedFiles[fileIndex] = new File([compressedFile], noviNazivFajla, {type: compressedFile.type, lastModified: Date.now()});
            feedbackEl.innerHTML = `<i class="fa-solid fa-check text-success"></i> Optimizovana (~${(compressedFile.size / 1024 / 1024).toFixed(2)} MB)`;
        } catch (error) {
            feedbackEl.textContent = 'Greška pri kompresiji.';
            console.error(error);
        }
    }
    
    if (photoInputsContainer) {
        photoInputsContainer.addEventListener('change', e => { if (e.target.type === 'file') handleImageUpload(e.target); });
    }

    if (formaZaEvidenciju) {
        formaZaEvidenciju.addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(formaZaEvidenciju);
            formData.delete('masina_foto[]');
            compressedFiles.filter(f => f).forEach(file => formData.append('masina_foto[]', file, file.name));
            const submitButton = document.getElementById('submit-btn');
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Čuvanje...';
            fetch(formaZaEvidenciju.action, { method: 'POST', body: formData })
            .then(response => {
                if (response.ok && response.redirected) {
                    window.location.href = response.url;
                } else {
                    window.location.reload();
                }
            })
            .catch(error => {
                alert('Došlo je do mrežne greške.');
                submitButton.disabled = false;
                submitButton.textContent = isEditMode ? 'Sačuvaj izmene' : 'Sačuvaj Evidenciju Kontrole';
            });
        });
    }
    
    if (!isEditMode && !hasFormData) {
        const video = document.getElementById("video");
        const canvasElement = document.getElementById("canvas");
        const canvas = canvasElement.getContext("2d");
        const loadingMessage = document.getElementById("loadingMessage");
        const outputMessage = document.getElementById("outputMessage");
        const outputData = document.getElementById("outputData");
        const outputDataContainer = document.getElementById("outputDataContainer");
        const startScanBtn = document.getElementById("startScanBtn");
        const stopScanBtn = document.getElementById("stopScanBtn");
        const scannerContainer = document.getElementById("scanner-container");
        const unlockFieldsBtn = document.getElementById("unlockFieldsBtn");
        const checklistContainer = document.getElementById('checklist-kontejner');
        let stream = null;
        let animationFrameId = null;
        function stopScan() { if (animationFrameId) { cancelAnimationFrame(animationFrameId); animationFrameId = null; } if (stream) { stream.getTracks().forEach(track => track.stop()); stream = null; } video.srcObject = null; scannerContainer.style.display = 'none'; loadingMessage.textContent = "🎥 Kamera nije aktivna."; loadingMessage.style.display = 'block'; startScanBtn.style.display = 'inline-block'; stopScanBtn.style.display = 'none'; outputMessage.hidden = true; }
        async function startScan() { stopScan(); loadingMessage.textContent = "🎥 Pokrećem kameru..."; outputMessage.hidden = false; outputDataContainer.hidden = true; try { const mediaStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } }); stream = mediaStream; video.srcObject = mediaStream; video.setAttribute('playsinline', true); await video.play(); scannerContainer.style.display = 'block'; loadingMessage.style.display = 'none'; startScanBtn.style.display = 'none'; stopScanBtn.style.display = 'inline-block'; animationFrameId = requestAnimationFrame(tick); } catch (error) { console.error('Greška:', error); loadingMessage.textContent = `🚫 ${error.name}`; stopScan(); } }
        function tick() {
    if (video.readyState === video.HAVE_ENOUGH_DATA) {
    canvasElement.height = video.videoHeight;
    canvasElement.width = video.videoWidth;
    canvas.drawImage(video, 0, 0, canvasElement.width, canvasElement.height);

    const imageData = canvas.getImageData(0, 0, canvasElement.width, canvasElement.height);
    const code = jsQR(imageData.data, imageData.width, imageData.height, {
      inversionAttempts: "dontInvert",
    });

    if (code && code.data !== "") {
      outputData.innerText = code.data;
      parseQRDataAndFillForm(code.data);

      // ✅ Ovo pusti beep!
      document.getElementById('beepSound').play();

      stopScan();
    }
    }

    if (stream) {
    animationFrameId = requestAnimationFrame(tick);
    }
}

    // function parseQRDataAndFillForm(data) { const fields = data.split('|'); if (fields.length >= 5) { identInput.value = fields[1] || ''; document.getElementById('naziv').value = fields[2] || ''; document.getElementById('kataloska_oznaka').value = fields[3] || ''; let serijski = fields[4] || ''; if (serijski.length > 9) { serijski = serijski.slice(-9); } document.getElementById('serijski_broj').value = serijski; identInput.dispatchEvent(new Event('change')); } else { alert("Format QR koda nije ispravan."); } }

    function parseQRDataAndFillForm(data) {
    let identStartIndex = data.indexOf('GTP-');
    if (identStartIndex === -1) {
    identStartIndex = data.indexOf('GMM-');
    }

    if (identStartIndex === -1) {
    alert("QR kod ne sadrži validan Ident (GTP- ili GMM-).");
    return;
    }

    let trimmedData = data.substring(identStartIndex);
    const fields = trimmedData.split('|');

    if (fields.length >= 4) {
    document.getElementById('ident').value = fields[0] || '';
    document.getElementById('naziv').value = fields[1] || '';
    document.getElementById('kataloska_oznaka').value = fields[2] || '';

    // UZMI ZADNJI ELEMENT (serijski broj)
    let serijski = fields[fields.length - 1] || '';
    if (serijski.length > 9) {
      serijski = serijski.slice(-9);
    }
    document.getElementById('serijski_broj').value = serijski;

    document.getElementById('ident').dispatchEvent(new Event('change'));
    } else {
    alert("Format QR koda nije ispravan (nedovoljno polja).");
    }
}


        function unlockFields() { document.getElementById('ident').readOnly = false; document.getElementById('naziv').readOnly = false; document.getElementById('kataloska_oznaka').readOnly = false; document.getElementById('serijski_broj').readOnly = false; unlockFieldsBtn.textContent = "Polja su otključana"; unlockFieldsBtn.disabled = true; }
        startScanBtn.addEventListener('click', startScan);
        stopScanBtn.addEventListener('click', stopScan);
        unlockFieldsBtn.addEventListener('click', unlockFields);
        identInput.addEventListener('change', function() { if (this.value) { fetchChecklist(this.value); } });
        function fetchChecklist(ident) { checklistContainer.innerHTML = '<div class="alert alert-info">Učitavanje ček-liste...</div>'; const url = `<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?action=get_plan_details&ident=${encodeURIComponent(ident)}`; fetch(url).then(response => { if (!response.ok) { return response.json().then(err => { throw new Error(err.error || `Greška servera`); }); } return response.json(); }).then(data => { if (data.error) { checklistContainer.innerHTML = `<div class="alert alert-danger">${data.error}</div>`; } else { document.getElementById('kataloska_oznaka').value = data.kataloska_oznaka || ''; document.getElementById('naziv').value = data.naziv_proizvoda || ''; buildChecklist(data); } }).catch(error => { checklistContainer.innerHTML = `<div class="alert alert-danger">Greška: ${error.message}</div>`; }); }
        function buildChecklist(plan) { 
            let html = `<h4>3. Ček Lista (Plan: ${plan.broj_plana_kontrole})</h4>`; 
            html += `<input type="hidden" name="plan_kontrole_id" value="${plan.id}">`; 
            if (!plan.grupe || plan.grupe.length === 0) { 
                html += '<p class="text-muted">Ovaj plan nema definisanih grupa.</p>'; 
            } else { 
                plan.grupe.forEach((grupa) => { 
                    html += `<div class="card mb-3"><div class="card-header bg-light"><strong>Grupa: ${grupa.naziv_grupe}</strong></div><div class="card-body">`; 
                    if (!grupa.karakteristike || grupa.karakteristike.length === 0) { 
                        html += '<p class="text-muted">Ova grupa nema definisanih karakteristika.</p>'; 
                    } else { 
                        grupa.karakteristike.forEach((kar) => { 
                            html += `<div class="mb-3 p-2 border-bottom">
                                        <label class="form-label d-block"><strong>${kar.redni_broj_karakteristike}. ${kar.opis_karakteristike}</strong></label>`;
                            
                            if (kar.kontrolni_alat_nacin) {
                                html += `<span class="d-block text-muted small mt-1"><i class="fa-solid fa-wrench me-1"></i><strong>Alat/Način:</strong> ${kar.kontrolni_alat_nacin}</span>`;
                            }

                            html += `<input type="hidden" name="rezultati[${kar.id}][opis_snapshot]" value="${kar.opis_karakteristike.replace(/"/g, '&quot;')}">`; 

                            if (kar.putanja_fotografije_opis) {
                                const imageUrl = `${APP_URL_BASE}/public/uploads/${kar.putanja_fotografije_opis}`;
                                html += `<div class="mb-2"><a href="#" class="view-image-link" data-bs-toggle="modal" data-bs-target="#imageModal" data-image-url="${imageUrl}"><img src="${imageUrl}" alt="Referentna slika" class="img-thumbnail" style="max-height: 150px; cursor: pointer;"></a></div>`;
                            }
                            if (kar.vrsta_karakteristike === 'OK/NOK') { 
                                html += `<div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="rezultati[${kar.id}][vrednost]" id="ok_${kar.id}" value="OK" required><label class="form-check-label" for="ok_${kar.id}">OK</label></div>`; 
                                html += `<div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="rezultati[${kar.id}][vrednost]" id="nok_${kar.id}" value="NOK"><label class="form-check-label" for="nok_${kar.id}">NOK</label></div>`; 
                            } else if (kar.vrsta_karakteristike === 'TEKSTUALNI_OPIS') { 
                                html += `<textarea class="form-control mt-2" name="rezultati[${kar.id}][vrednost]" rows="2" placeholder="Unesite tekstualni opis..." required></textarea>`; 
                            } 
                            html += `</div>`; 
                        }); 
                    } 
                    html += `</div></div>`; 
                }); 
            } 
            document.getElementById('checklist-kontejner').innerHTML = html; 
        }
    }
    const addPhotoBtn = document.getElementById('add-photo-btn');
    if (addPhotoBtn) {
        const maxPhotos = 5;
        addPhotoBtn.addEventListener('click', function() {
            if (photoInputsContainer.querySelectorAll('input[type="file"]').length < maxPhotos) {
                const newInputGroup = document.createElement('div');
                newInputGroup.className = 'mb-2 d-flex align-items-center';
                newInputGroup.innerHTML = `
    <input type="file" class="form-control w-100" name="masina_foto[]" accept="image/*">
    <div class="compression-feedback text-muted small mt-1"></div>
    <button type="button" class="btn-close ms-2" aria-label="Ukloni"></button>
`;
                newInputGroup.querySelector('.btn-close').onclick = function() {
                    const inputToRemove = this.parentElement.querySelector('input[type="file"]');
                    const allInputs = Array.from(photoInputsContainer.querySelectorAll('input[type="file"]'));
                    const indexToRemove = allInputs.indexOf(inputToRemove);
                    if (indexToRemove > -1) { compressedFiles.splice(indexToRemove, 1); }
                    this.parentElement.remove();
                    addPhotoBtn.disabled = photoInputsContainer.querySelectorAll('input[type="file"]').length >= maxPhotos;
                };
                photoInputsContainer.appendChild(newInputGroup);
            }
            if (photoInputsContainer.querySelectorAll('input[type="file"]').length >= maxPhotos) { this.disabled = true; }
        });
    }
    const imageModalEl = document.getElementById('imageModal');
    if (imageModalEl) {
        // Kreiramo Bootstrap Modal instancu samo jednom
        const imageModalInstance = new bootstrap.Modal(imageModalEl);
        const modalImage = imageModalEl.querySelector('#modalImage');

        // Slušamo klikove na celoj stranici (event delegation)
        document.body.addEventListener('click', function(event) {
            const triggerElement = event.target.closest('.view-image-link');
            if (triggerElement) {
                event.preventDefault();
                const imageUrl = triggerElement.getAttribute('data-image-url');
                if (modalImage && imageUrl) {
                    modalImage.src = imageUrl;
                    imageModalInstance.show();
                }
            }
        });

        // DODAJEMO LISTENER KOJI SE POKREĆE NAKON ZATVARANJA MODALA
        // Ovo je vaša ideja, implementirana na siguran način.
        imageModalEl.addEventListener('hidden.bs.modal', function () {
            // Proveravamo da li je neka pozadina (backdrop) ostala greškom
            const backdrops = document.querySelectorAll('.modal-backdrop');
            if (backdrops.length > 0) {
                // Uklanjamo sve zaostale pozadine
                backdrops.forEach(backdrop => backdrop.remove());
                // Vraćamo skrolovanje na stranici
                document.body.classList.remove('modal-open');
                document.body.style.overflow = 'auto';
            }
        });
    }
    
    const existingPhotosContainer = document.getElementById('existing-photos-container');
    if (existingPhotosContainer) {
        existingPhotosContainer.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('delete-photo-btn')) {
                e.preventDefault();
                const photoWrapper = e.target.closest('.existing-photo-wrapper');
                const photoId = e.target.dataset.photoId;
                if(confirm('Da li ste sigurni da želite da obrišete ovu sliku? Brisanje je konačno nakon čuvanja izmena.')) {
                    photoWrapper.style.display = 'none';
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'delete_photos[]';
                    hiddenInput.value = photoId;
                    formaZaEvidenciju.appendChild(hiddenInput);
                }
            }
        });
    }
});
</script>
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="imageModalLabel">Prikaz Slike</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body text-center"><img src="" class="img-fluid" id="modalImage" alt="Slika karakteristike"></div>
    </div>
  </div>
</div>
</div>