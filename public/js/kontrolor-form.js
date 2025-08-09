// Ovaj kod se izvršava nakon što se DOM učita, jer se skripta poziva na kraju body taga.

const isEditMode = pageConfig.isEdit;
const hasFormData = pageConfig.hasFormData;
const APP_URL_BASE = pageConfig.appUrl;

const formaZaEvidenciju = document.getElementById('forma-za-evidenciju');
const photoInputsContainer = document.getElementById('photo-inputs-container');
let compressedFiles = []; 
const identInput = document.getElementById('ident');
const kataloskaOznakaInput = document.getElementById('kataloska_oznaka');
const serijskiBrojInput = document.getElementById('serijski_broj');

async function handleImageUpload(fileInput) {
    const imageFile = fileInput.files[0];
    if (!imageFile) return;
    let feedbackEl = fileInput.parentElement.querySelector('.compression-feedback');
    if (!feedbackEl) {
        feedbackEl = document.createElement('div');
        feedbackEl.className = 'compression-feedback text-muted small mt-1';
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
            const code = jsQR(imageData.data, imageData.width, imageData.height, { inversionAttempts: "dontInvert" });
            if (code && code.data !== "") {
                outputData.innerText = code.data;
                parseQRDataAndFillForm(code.data);
                document.getElementById('beepSound').play();
                stopScan();
            }
        }
        if (stream) { animationFrameId = requestAnimationFrame(tick); }
    }

    function parseQRDataAndFillForm(data) {
        let identStartIndex = data.indexOf('GTP-');
        if (identStartIndex === -1) { identStartIndex = data.indexOf('GMM-'); }
        if (identStartIndex === -1) { alert("QR kod ne sadrži validan Ident (GTP- ili GMM-)."); return; }
        let trimmedData = data.substring(identStartIndex);
        const fields = trimmedData.split('|');
        if (fields.length >= 4) {
            identInput.value = fields[0] || '';
            document.getElementById('naziv').value = fields[1] || '';
            kataloskaOznakaInput.value = fields[2] || '';
            let serijski = fields[fields.length - 1] || '';
            if (serijski.length > 9) { serijski = serijski.slice(-9); }
            serijskiBrojInput.value = serijski;
            identInput.dispatchEvent(new Event('change'));
        } else {
            alert("Format QR koda nije ispravan (nedovoljno polja).");
        }
    }

    function unlockFields() { identInput.readOnly = false; document.getElementById('naziv').readOnly = false; kataloskaOznakaInput.readOnly = false; serijskiBrojInput.readOnly = false; unlockFieldsBtn.textContent = "Polja su otključana"; unlockFieldsBtn.disabled = true; }
    startScanBtn.addEventListener('click', startScan);
    stopScanBtn.addEventListener('click', stopScan);
    unlockFieldsBtn.addEventListener('click', unlockFields);
    identInput.addEventListener('change', function() { if (this.value) { fetchChecklist(this.value); } });
    
    function fetchChecklist(ident) { 
        checklistContainer.innerHTML = '<div class="alert alert-info">Učitavanje ček-liste...</div>'; 
        const url = `${APP_URL_BASE}/public/index.php?action=get_plan_details&ident=${encodeURIComponent(ident)}`; 
        fetch(url)
            .then(response => { if (!response.ok) { return response.json().then(err => { throw new Error(err.error || `Greška servera`); }); } return response.json(); })
            .then(data => { if (data.error) { checklistContainer.innerHTML = `<div class="alert alert-danger">${data.error}</div>`; } else { kataloskaOznakaInput.value = data.kataloska_oznaka || ''; document.getElementById('naziv').value = data.naziv_proizvoda || ''; buildChecklist(data); } })
            .catch(error => { checklistContainer.innerHTML = `<div class="alert alert-danger">Greška: ${error.message}</div>`; }); 
    }
    
    function buildChecklist(plan) { 
        let html = `<h4>3. Ček Lista (Plan: ${plan.broj_plana_kontrole} | Verzija: ${plan.verzija_broj})</h4>`; 
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
                <button type="button" class="btn-close ms-2" aria-label="Ukloni"></button>`;
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
    const imageModalInstance = new bootstrap.Modal(imageModalEl);
    const modalImage = imageModalEl.querySelector('#modalImage');
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
    imageModalEl.addEventListener('hidden.bs.modal', function () {
        const backdrops = document.querySelectorAll('.modal-backdrop');
        if (backdrops.length > 0) {
            backdrops.forEach(backdrop => backdrop.remove());
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