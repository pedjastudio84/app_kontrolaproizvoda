<div class="container">
    <h1>Kontrolna tabla</h1>
    <p>Dobrodošli, <strong><?php echo htmlspecialchars($_SESSION['user_ime'] ?? $_SESSION['user_korisnicko_ime']); ?></strong>!</p>

    <div class="row mb-4 g-3">
        <div class="col-lg col-md-6 col-sm-6">
            <div class="card text-center text-white bg-success h-100">
                <div class="card-body d-flex flex-column justify-content-center p-3">
                    <h5 class="card-title">Evidencija (Danas)</h5>
                    <p class="card-text fs-1 fw-bold mb-0"><?php echo $stats['records_today'] ?? 0; ?></p>
                </div>
            </div>
        </div>
        <div class="col-lg col-md-6 col-sm-6">
            <div class="card text-center text-white bg-warning h-100">
                <div class="card-body d-flex flex-column justify-content-center p-3">
                    <h5 class="card-title">Evidencija (Mesec)</h5>
                    <p class="card-text fs-1 fw-bold mb-0"><?php echo $stats['records_this_month'] ?? 0; ?></p>
                </div>
            </div>
        </div>
        <div class="col-lg col-md-6 col-sm-6">
            <div class="card text-center text-bg-info h-100">
                <div class="card-body d-flex flex-column justify-content-center p-3">
                    <h5 class="card-title">Ukupno Evidencija</h5>
                    <p class="card-text fs-1 fw-bold mb-0"><?php echo $stats['total_records'] ?? 0; ?></p>
                </div>
            </div>
        </div>
        <div class="col-lg col-md-6 col-sm-6">
            <div class="card text-center text-bg-secondary h-100">
                <div class="card-body d-flex flex-column justify-content-center p-3">
                    <h5 class="card-title">Planovi Kontrole</h5>
                    <p class="card-text fs-1 fw-bold mb-0"><?php echo $stats['total_plans'] ?? 0; ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fa-solid fa-table-list me-1"></i> Poslednjih 5 Evidencija</span>
                    <a href="?page=pregled_svih_zapisa" class="btn btn-sm btn-outline-secondary">Sve evidencije <i class="fa-solid fa-arrow-right fa-xs"></i></a>
                </div>
                <div class="list-group list-group-flush">
                    <?php if (isset($latest_records) && !empty($latest_records)): ?>
                        <?php foreach ($latest_records as $record): ?>
                            <a href="?page=kontrolor_zapis_show&id=<?php echo $record['id']; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($record['product_naziv_sken']); ?></h6>
                                    <small>#<?php echo htmlspecialchars($record['id']); ?></small>
                                </div>
                                <p class="mb-1">Kat. oznaka: <strong><?php echo htmlspecialchars($record['product_kataloska_oznaka_sken'] ?? '-'); ?></strong></p>
                                <small class="text-muted">Kontrolor: <?php echo htmlspecialchars($record['kontrolor_puno_ime']); ?> | <?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($record['datum_vreme_ispitivanja']))); ?></small>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="list-group-item">Nema unetih evidencija.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fa-solid fa-clipboard-list me-1"></i> Poslednjih 5 Planova</span>
                    <a href="?page=pregled_planova" class="btn btn-sm btn-outline-secondary">Svi planovi <i class="fa-solid fa-arrow-right fa-xs"></i></a>
                </div>
                <div class="list-group list-group-flush">
                    <?php if (isset($latest_plans) && !empty($latest_plans)): ?>
                        <?php foreach ($latest_plans as $plan): ?>
                            <a href="?page=pregled_plana_detalji&id=<?php echo $plan['id']; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($plan['naziv_proizvoda']); ?></h6>
                                    <small>Plan: <?php echo htmlspecialchars($plan['broj_plana_kontrole']); ?></small>
                                </div>
                                <p class="mb-1">Kat. oznaka: <strong><?php echo htmlspecialchars($plan['kataloska_oznaka'] ?? '-'); ?></strong></p>
                                <small class="text-muted">Kreirao: <?php echo htmlspecialchars($plan['kreator_puno_ime']); ?> | <?php echo htmlspecialchars(date('d.m.Y', strtotime($plan['kreiran_datuma']))); ?></small>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="list-group-item">Nema kreiranih planova kontrole.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>