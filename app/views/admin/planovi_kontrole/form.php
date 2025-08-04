<?php
// --- Logika za određivanje moda forme i podataka ---
$isEdit = isset($plan) && isset($plan['id']);
$isCopy = isset($isCopy) && $isCopy;
$hasFormData = isset($_SESSION['form_data']);

if ($isCopy) {
    $isEdit = false; // Kopiranje je kao kreiranje novog, ne ažuriranje postojećeg
}

// Određivanje naslova stranice
$pageTitle = 'Dodavanje Novog Plana Kontrole';
if ($isEdit) {
    $pageTitle = 'Izmena Plana Kontrole: ' . htmlspecialchars($plan['broj_plana_kontrole']);
} elseif ($isCopy) {
    $pageTitle = 'Kopiranje Plana: ' . htmlspecialchars($plan['broj_plana_kontrole']);
} elseif ($hasFormData) {
    $pageTitle = 'Ispravka Unosa Plana Kontrole';
}

if (!defined('PAGE_TITLE')) {
    define('PAGE_TITLE', $pageTitle);
}
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Određivanje izvora podataka za popunjavanje forme.
$formData = $_SESSION['form_data'] ?? ($plan ?? []);
$grupe = $formData['grupe'] ?? [];
?>

<template id="grupa-template">
    <div class="accordion-item grupa-kartica">
        <input type="hidden" class="redosled-grupe" data-name="grupe[__G_INDEX__][redosled_prikaza]" value="__G_INDEX__">
        <h2 class="accordion-header" id="heading-__G_INDEX__" style="cursor: grab;">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-__G_INDEX__" aria-expanded="false" aria-controls="collapse-__G_INDEX__">
                Nova Grupa
            </button>
        </h2>
        <div id="collapse-__G_INDEX__" class="accordion-collapse collapse" aria-labelledby="heading-__G_INDEX__" data-bs-parent="#grupeAccordion">
            <div class="accordion-body">
                <div class="d-flex justify-content-end mb-3">
                    <button type="button" class="btn btn-danger btn-sm ukloni-grupu">Ukloni Ovu Grupu</button>
                </div>
                <div class="mb-3">
                    <label class="form-label">Naziv Grupe <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" data-name="grupe[__G_INDEX__][naziv_grupe]" required onkeyup="updateAccordionHeader(this)">
                </div>
                <hr>
                <h6>Karakteristike unutar grupe:</h6>
                <div class="karakteristike-kontejner karakteristike-sortable-kontejner"></div>
                <button type="button" class="btn btn-primary btn-sm mt-2 dodaj-karakteristiku" data-grupa-index="__G_INDEX__">Dodaj Karakteristiku</button>
            </div>
        </div>
    </div>
</template>

<template id="karakteristika-template">
    <div class="row gx-2 mb-2 p-2 border rounded align-items-center karakteristika-red bg-light">
        <div class="col-md-auto text-center" style="cursor: grab;" title="Promeni redosled">
            <i class="fa-solid fa-grip-vertical text-muted"></i>
            <input type="hidden" class="redosled-karakteristike" data-name="[karakteristike][__K_INDEX__][pozicija]" value="__K_INDEX__">
        </div>
        <div class="col-md-1">
            <label class="form-label small">R.br.</label>
            <input type="number" class="form-control form-control-sm redni-broj-karakteristike" data-name="[karakteristike][__K_INDEX__][redni_broj_karakteristike]" readonly>
        </div>
        <div class="col-md-3">
            <label class="form-label small">Opis <span class="text-danger">*</span></label>
            <textarea class="form-control form-control-sm" data-name="[karakteristike][__K_INDEX__][opis_karakteristike]" rows="2" required></textarea>
            <label class="form-label small mt-1">Fotografija (opciono)</label>
            <input type="file" class="form-control form-control-sm" data-name="[karakteristike][__K_INDEX__][fotografija]">
        </div>
        <div class="col-md-2">
            <label class="form-label small">Vrsta <span class="text-danger">*</span></label>
            <select class="form-select form-select-sm" data-name="[karakteristike][__K_INDEX__][vrsta_karakteristike]" required>
                <option value="OK/NOK">OK/NOK</option>
                <option value="TEKSTUALNI_OPIS">Tekstualni opis</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Kontrolni alat</label>
            <input type="text" class="form-control form-control-sm" data-name="[karakteristike][__K_INDEX__][kontrolni_alat_nacin]">
        </div>
        <div class="col-md-1">
            <label class="form-label small">Veličina uzorka</label>
            <input type="text" class="form-control form-control-sm" data-name="[karakteristike][__K_INDEX__][velicina_uzorka]">
        </div>
        <div class="col-md-1 d-flex align-items-end justify-content-center">
            <button type="button" class="btn btn-outline-danger btn-sm ukloni-karakteristiku">Ukloni</button>
        </div>
    </div>
</template>

<div class="container-fluid">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?page=admin_dashboard">Admin Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?page=admin_plans">Upravljanje Planovima Kontrole</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo $isEdit ? 'Izmena' : 'Dodavanje/Kopiranje'; ?></li>
        </ol>
    </nav>
    
    <h1><?php echo $pageTitle; ?></h1>

    <?php if (isset($_SESSION['form_errors'])): ?>
        <div class="alert alert-danger">
            <strong>Greška pri unosu:</strong>
            <ul><?php foreach ($_SESSION['form_errors'] as $error) echo '<li>' . htmlspecialchars($error) . '</li>'; ?></ul>
        </div>
        <?php unset($_SESSION['form_errors']); ?>
    <?php endif; ?>

    <form id="plan-kontrole-forma" action="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?action=<?php echo $isEdit ? 'admin_plan_update&id=' . $plan['id'] : 'admin_plan_store'; ?>" method="POST" enctype="multipart/form-data">
        
        <input type="hidden" name="form_action" id="form_action" value="minor_edit">
        <input type="hidden" name="verzija_napomena" id="hidden_verzija_napomena">

        <div class="card mb-4">
            <div class="card-header bg-light">Osnovni podaci o Planu Kontrole</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3"><label for="broj_plana_kontrole" class="form-label">Broj plana kontrole <span class="text-danger">*</span></label><input type="text" class="form-control" id="broj_plana_kontrole" name="broj_plana_kontrole" value="<?php echo htmlspecialchars($formData['broj_plana_kontrole'] ?? ''); ?>" required></div>
                    <div class="col-md-6 mb-3"><label for="ident_proizvoda" class="form-label">Ident proizvoda <span class="text-danger">*</span></label><input type="text" class="form-control" id="ident_proizvoda" name="ident_proizvoda" value="<?php echo htmlspecialchars($formData['ident_proizvoda'] ?? ''); ?>" required></div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3"><label for="naziv_proizvoda" class="form-label">Naziv proizvoda <span class="text-danger">*</span></label><input type="text" class="form-control" id="naziv_proizvoda" name="naziv_proizvoda" value="<?php echo htmlspecialchars($formData['naziv_proizvoda'] ?? ''); ?>" required></div>
                    <div class="col-md-6 mb-3"><label for="kataloska_oznaka" class="form-label">Kataloška oznaka</label><input type="text" class="form-control" id="kataloska_oznaka" name="kataloska_oznaka" value="<?php echo htmlspecialchars($formData['kataloska_oznaka'] ?? ''); ?>"></div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3"><label for="broj_operacije" class="form-label">Broj operacije</label><input type="text" class="form-control" id="broj_operacije" name="broj_operacije" value="<?php echo htmlspecialchars($formData['broj_operacije'] ?? ''); ?>"></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                Karakteristike za kontrolu
                <button type="button" class="btn btn-success btn-sm" id="dodaj-grupu">Dodaj Grupu</button>
            </div>
            <div class="card-body">
                <div class="accordion" id="grupeAccordion">
                    <?php if (!empty($grupe)): ?>
                        <?php foreach ($grupe as $g_index => $grupa): ?>
                            <div class="accordion-item grupa-kartica">
                                <input type="hidden" class="redosled-grupe" name="grupe[<?php echo $g_index; ?>][redosled_prikaza]" value="<?php echo $g_index; ?>">
                                <h2 class="accordion-header" id="heading-<?php echo $g_index; ?>" style="cursor: grab;">
                                    <button class="accordion-button <?php if(!$hasFormData) echo 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $g_index; ?>" aria-expanded="<?php echo $hasFormData ? 'true' : 'false'; ?>" aria-controls="collapse-<?php echo $g_index; ?>">
                                        <?php echo htmlspecialchars($grupa['naziv_grupe'] ?? 'Nova Grupa'); ?>
                                    </button>
                                </h2>
                                <div id="collapse-<?php echo $g_index; ?>" class="accordion-collapse collapse <?php if($hasFormData) echo 'show'; ?>" aria-labelledby="heading-<?php echo $g_index; ?>" data-bs-parent="#grupeAccordion">
                                    <div class="accordion-body">
                                        <div class="d-flex justify-content-end mb-3"><button type="button" class="btn btn-danger btn-sm ukloni-grupu">Ukloni Ovu Grupu</button></div>
                                        <div class="mb-3">
                                            <label class="form-label">Naziv Grupe <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="grupe[<?php echo $g_index; ?>][naziv_grupe]" value="<?php echo htmlspecialchars($grupa['naziv_grupe'] ?? ''); ?>" required onkeyup="updateAccordionHeader(this)">
                                        </div>
                                        <hr>
                                        <h6>Karakteristike unutar grupe:</h6>
                                        <div class="karakteristike-kontejner karakteristike-sortable-kontejner">
                                            <?php if (!empty($grupa['karakteristike'])): ?>
                                                <?php foreach ($grupa['karakteristike'] as $k_index => $karakteristika): ?>
                                                    <div class="row gx-2 mb-2 p-2 border rounded align-items-center karakteristika-red bg-light">
                                                        <div class="col-md-auto text-center" style="cursor: grab;" title="Promeni redosled">
                                                            <i class="fa-solid fa-grip-vertical text-muted"></i>
                                                            <input type="hidden" class="redosled-karakteristike" name="grupe[<?php echo $g_index; ?>][karakteristike][<?php echo $k_index; ?>][pozicija]" value="<?php echo htmlspecialchars($karakteristika['pozicija'] ?? $k_index); ?>">
                                                        </div>
                                                        <div class="col-md-1"><label class="form-label small">R.br.</label><input type="number" class="form-control form-control-sm redni-broj-karakteristike" name="grupe[<?php echo $g_index; ?>][karakteristike][<?php echo $k_index; ?>][redni_broj_karakteristike]" value="<?php echo htmlspecialchars($karakteristika['redni_broj_karakteristike'] ?? ($k_index + 1)); ?>" readonly></div>
                                                        <div class="col-md-3">
                                                            <label class="form-label small">Opis <span class="text-danger">*</span></label>
                                                            <textarea class="form-control form-control-sm" name="grupe[<?php echo $g_index; ?>][karakteristike][<?php echo $k_index; ?>][opis_karakteristike]" rows="2" required><?php echo htmlspecialchars($karakteristika['opis_karakteristike'] ?? ''); ?></textarea>
                                                            <?php if(!empty($karakteristika['putanja_fotografije_opis'])): ?><div class="mt-2 small">Postojeća slika: <a href="#" class="view-image-link" data-bs-toggle="modal" data-bs-target="#imageModal" data-image-url="<?php echo rtrim(APP_URL, '/'); ?>/public/uploads/<?php echo htmlspecialchars($karakteristika['putanja_fotografije_opis']); ?>"><img src="<?php echo rtrim(APP_URL, '/'); ?>/public/uploads/<?php echo htmlspecialchars($karakteristika['putanja_fotografije_opis']); ?>" alt="Slika" style="max-width: 60px; max-height: 40px; cursor: pointer; vertical-align: middle;"></a></div><input type="hidden" name="grupe[<?php echo $g_index; ?>][karakteristike][<?php echo $k_index; ?>][postojeca_fotografija]" value="<?php echo htmlspecialchars($karakteristika['putanja_fotografije_opis']); ?>"><?php endif; ?>
                                                            <label class="form-label small mt-1">Nova fotografija (menja staru)</label><input type="file" class="form-control form-control-sm" name="grupe[<?php echo $g_index; ?>][karakteristike][<?php echo $k_index; ?>][fotografija]">
                                                        </div>
                                                        <div class="col-md-2"><label class="form-label small">Vrsta <span class="text-danger">*</span></label><select class="form-select form-select-sm" name="grupe[<?php echo $g_index; ?>][karakteristike][<?php echo $k_index; ?>][vrsta_karakteristike]" required><option value="OK/NOK" <?php if(isset($karakteristika['vrsta_karakteristike']) && $karakteristika['vrsta_karakteristike'] === 'OK/NOK') echo 'selected'; ?>>OK/NOK</option><option value="TEKSTUALNI_OPIS" <?php if(isset($karakteristika['vrsta_karakteristike']) && $karakteristika['vrsta_karakteristike'] === 'TEKSTUALNI_OPIS') echo 'selected'; ?>>Tekstualni opis</option></select></div>
                                                        <div class="col-md-2"><label class="form-label small">Kontrolni alat</label><input type="text" class="form-control form-control-sm" name="grupe[<?php echo $g_index; ?>][karakteristike][<?php echo $k_index; ?>][kontrolni_alat_nacin]" value="<?php echo htmlspecialchars($karakteristika['kontrolni_alat_nacin'] ?? ''); ?>"></div>
                                                        <div class="col-md-1"><label class="form-label small">Veličina uzorka</label><input type="text" class="form-control form-control-sm" name="grupe[<?php echo $g_index; ?>][karakteristike][<?php echo $k_index; ?>][velicina_uzorka]" value="<?php echo htmlspecialchars($karakteristika['velicina_uzorka'] ?? ''); ?>"></div>
                                                        <div class="col-md-1 d-flex align-items-end justify-content-center"><button type="button" class="btn btn-outline-danger btn-sm ukloni-karakteristiku">Ukloni</button></div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                        <button type="button" class="btn btn-primary btn-sm mt-2 dodaj-karakteristiku" data-grupa-index="<?php echo $g_index; ?>">Dodaj Karakteristiku</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <p class="text-muted" id="prazna-lista-grupa" <?php if (!empty($grupe)) { echo 'style="display:none;"'; } ?>>Nema dodatih grupa. Kliknite na "Dodaj Grupu" da biste započeli.</p>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <a href="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?page=admin_plans" class="btn btn-secondary me-2">Odustani</a>
            <?php if ($isEdit): ?>
                <button type="submit" class="btn btn-primary" name="form_action" value="minor_edit">Sačuvaj manju izmenu</button>
                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#versionModal">Sačuvaj kao Novu Verziju</button>
            <?php else: ?>
                <button type="submit" class="btn btn-primary">Kreiraj Plan Kontrole</button>
            <?php endif; ?>
        </div>

    </form>
</div>

<?php if (isset($_SESSION['form_data'])) { unset($_SESSION['form_data']); } ?>

<div class="modal fade" id="versionModal" tabindex="-1" aria-labelledby="versionModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="versionModalLabel">Kreiranje Nove Verzije Plana</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label for="modal_verzija_napomena" class="form-label">Unesite razlog izmene (napomena) <span class="text-danger">*</span></label>
            <textarea class="form-control" id="modal_verzija_napomena" rows="3" required></textarea>
            <div class="invalid-feedback">Napomena je obavezna.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Odustani</button>
        <button type="button" class="btn btn-primary" id="confirmNewVersionButton">Potvrdi i sačuvaj verziju</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="imageModalLabel">Prikaz Slike</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body text-center"><img src="" class="img-fluid" id="modalImage" alt="Slika karakteristike"></div>
    </div>
  </div>
</div>
