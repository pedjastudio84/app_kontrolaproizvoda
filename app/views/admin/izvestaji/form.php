<?php
if (!defined('PAGE_TITLE')) { define('PAGE_TITLE', 'Generisanje Izveštaja'); }
?>
<h1><?php echo htmlspecialchars(PAGE_TITLE); ?></h1>
<p class="text-muted">Izaberite filtere na osnovu kojih želite da generišete PDF izveštaj.</p>

<div class="card">
    <div class="card-body">
        <form action="<?php echo rtrim(APP_URL, '/'); ?>/public/index.php?action=generate_report" method="POST" target="_blank">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="datum_od" class="form-label">Datum od:</label>
                    <input type="date" class="form-control" name="datum_od" id="datum_od">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="datum_do" class="form-label">Datum do:</label>
                    <input type="date" class="form-control" name="datum_do" id="datum_do">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="ident" class="form-label">Ident proizvoda:</label>
                    <input type="text" class="form-control" name="ident" id="ident" placeholder="Ostavite prazno za sve">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="kataloska_oznaka" class="form-label">Kataloška oznaka:</label>
                    <input type="text" class="form-control" name="kataloska_oznaka" id="kataloska_oznaka" placeholder="Ostavite prazno za sve">
                </div>
            </div>
            <div class="mb-3">
                <label for="kontrolor_id" class="form-label">Kontrolor:</label>
                <select class="form-select" name="kontrolor_id" id="kontrolor_id">
                    <option value="">Svi kontrolori</option>
                    <?php if (isset($kontrolori) && !empty($kontrolori)): ?>
                        <?php foreach($kontrolori as $kontrolor): ?>
                            <option value="<?php echo $kontrolor['id']; ?>"><?php echo htmlspecialchars($kontrolor['prezime'] . ' ' . $kontrolor['ime']); ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <hr>
            <button type="submit" class="btn btn-primary">Generiši PDF</button>
        </form>
    </div>
</div>