<?php
class Evidencija {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Kreira kompletnu evidenciju kontrole unutar transakcije.
     */
    public function create($data, $files, $kontrolorId) {
        try {
            $this->db->beginTransaction();
            $sqlEvidencija = "INSERT INTO evidencije_kontrole (kontrolor_id, plan_kontrole_id, vrsta_kontrole, product_ident_sken, product_kataloska_oznaka_sken, product_naziv_sken, product_serijski_broj_sken, ostale_napomene, ime_kupca, status_kontrole) VALUES (:kontrolor_id, :plan_kontrole_id, :vrsta_kontrole, :product_ident_sken, :product_kataloska_oznaka_sken, :product_naziv_sken, :product_serijski_broj_sken, :ostale_napomene, :ime_kupca, 'završena')";
            $stmtEvidencija = $this->db->prepare($sqlEvidencija);
            $stmtEvidencija->execute([
                ':kontrolor_id' => $kontrolorId,
                ':plan_kontrole_id' => $data['plan_kontrole_id'] ?? null,
                ':vrsta_kontrole' => $data['vrsta_kontrole'],
                ':product_ident_sken' => $data['ident'],
                ':product_kataloska_oznaka_sken' => $data['kataloska_oznaka'],
                ':product_naziv_sken' => $data['naziv'],
                ':product_serijski_broj_sken' => $data['serijski_broj'],
                ':ostale_napomene' => $data['ostale_napomene'] ?? null,
                ':ime_kupca' => $data['ime_kupca'] ?? null // DODATO
            ]);
            $evidencijaId = $this->db->lastInsertId();

            // 2. Upis rezultata iz ček-liste
            if (!empty($data['rezultati']) && is_array($data['rezultati'])) {
                $sqlRezultat = "INSERT INTO rezultati_karakteristika_evidencije (evidencija_kontrole_id, karakteristika_plana_id, opis_karakteristike_snapshot, rezultat_ok_nok, rezultat_tekst) VALUES (:evidencija_id, :karakteristika_id, :opis_snapshot, :rezultat_ok_nok, :rezultat_tekst)";
                $stmtRezultat = $this->db->prepare($sqlRezultat);
                foreach ($data['rezultati'] as $karakteristikaId => $rezultatData) {
                    $vrednost = $rezultatData['vrednost'] ?? null;
                    $opis_snapshot = $rezultatData['opis_snapshot'] ?? 'Nepoznat opis';
                    $stmtRezultat->execute([':evidencija_id' => $evidencijaId, ':karakteristika_id' => $karakteristikaId, ':opis_snapshot' => $opis_snapshot, ':rezultat_ok_nok' => in_array($vrednost, ['OK', 'NOK']) ? $vrednost : null, ':rezultat_tekst' => !in_array($vrednost, ['OK', 'NOK']) ? $vrednost : null,]);
                }
            }

            // 3. Obrada i upis fotografija mašine
            if (isset($files['masina_foto']) && !empty($files['masina_foto']['name'][0])) {
                $sqlFoto = "INSERT INTO fotografije_masine_evidencije (evidencija_kontrole_id, putanja_fotografije) VALUES (:evidencija_id, :putanja)";
                $stmtFoto = $this->db->prepare($sqlFoto);
                $year = date('Y');
                $month = date('m');
                $subDir = "masine_evidencije/{$year}/{$month}/";
                $uploadDir = UPLOADS_PATH . '/' . $subDir;
                if (!is_dir($uploadDir)) { mkdir($uploadDir, 0775, true); }

                foreach ($files['masina_foto']['name'] as $key => $name) {
                    if ($files['masina_foto']['error'][$key] === 0) {
                         $datum = date('YmdHis');
                        $ident = preg_replace('/[^a-zA-Z0-9-]/', '', $data['ident']);
                        $kataloska = preg_replace('/[^a-zA-Z0-9-]/', '', $data['kataloska_oznaka']);
                        $serijski = preg_replace('/[^a-zA-Z0-9-]/', '', $data['serijski_broj']);
                        $fileExtension = pathinfo(basename($name), PATHINFO_EXTENSION);
                        
                        $noviNazivFajla = "{$datum}_{$ident}_{$kataloska}_{$serijski}_{$key}.{$fileExtension}";

                        $uploadFajl = $uploadDir . $noviNazivFajla;
                        if (move_uploaded_file($files['masina_foto']['tmp_name'][$key], $uploadFajl)) {
                            $putanjaZaBazu = $subDir . $noviNazivFajla;
                            $stmtFoto->execute([':evidencija_id' => $evidencijaId, ':putanja' => $putanjaZaBazu]);
                        }
                    }
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Greška u Evidencija::create: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ažurira postojeću evidenciju u bazi.
     */
    public function update($id, $data, $files) {
        try {
            $this->db->beginTransaction();

            $sqlEvidencija = "UPDATE evidencije_kontrole SET product_ident_sken = :ident, product_kataloska_oznaka_sken = :kataloska_oznaka, product_naziv_sken = :naziv, product_serijski_broj_sken = :serijski_broj, ostale_napomene = :ostale_napomene WHERE id = :id";
            $stmtEvidencija = $this->db->prepare($sqlEvidencija);
            $stmtEvidencija->execute([
                ':ident' => $data['ident'],
                ':kataloska_oznaka' => $data['kataloska_oznaka'],
                ':naziv' => $data['naziv'],
                ':serijski_broj' => $data['serijski_broj'],
                ':ostale_napomene' => $data['ostale_napomene'] ?? null,
                ':id' => $id
            ]);

            $stmtDeleteRezultati = $this->db->prepare("DELETE FROM rezultati_karakteristika_evidencije WHERE evidencija_kontrole_id = :id");
            $stmtDeleteRezultati->execute([':id' => $id]);
            if (!empty($data['rezultati']) && is_array($data['rezultati'])) {
                $sqlRezultat = "INSERT INTO rezultati_karakteristika_evidencije (evidencija_kontrole_id, karakteristika_plana_id, opis_karakteristike_snapshot, rezultat_ok_nok, rezultat_tekst) VALUES (:evidencija_id, :karakteristika_id, :opis_snapshot, :rezultat_ok_nok, :rezultat_tekst)";
                $stmtRezultat = $this->db->prepare($sqlRezultat);
                foreach ($data['rezultati'] as $karakteristikaId => $rezultatData) {
                    $vrednost = $rezultatData['vrednost'] ?? null;
                    $opis_snapshot = $rezultatData['opis_snapshot'] ?? 'Nepoznat opis';
                    $stmtRezultat->execute([':evidencija_id' => $id, ':karakteristika_id' => $karakteristikaId, ':opis_snapshot' => $opis_snapshot, ':rezultat_ok_nok' => in_array($vrednost, ['OK', 'NOK']) ? $vrednost : null, ':rezultat_tekst' => !in_array($vrednost, ['OK', 'NOK']) ? $vrednost : null]);
                }
            }

            if (!empty($data['delete_photos']) && is_array($data['delete_photos'])) {
                $placeholders = implode(',', array_fill(0, count($data['delete_photos']), '?'));
                $sqlSelectPhotos = "SELECT putanja_fotografije FROM fotografije_masine_evidencije WHERE id IN (" . $placeholders . ")";
                $stmtSelect = $this->db->prepare($sqlSelectPhotos);
                $stmtSelect->execute($data['delete_photos']);
                $files_to_delete = $stmtSelect->fetchAll(PDO::FETCH_COLUMN);
                foreach($files_to_delete as $file_path) {
                    if ($file_path && file_exists(UPLOADS_PATH . '/' . $file_path)) {
                        unlink(UPLOADS_PATH . '/' . $file_path);
                    }
                }
                $sqlDeletePhotos = "DELETE FROM fotografije_masine_evidencije WHERE id IN (" . $placeholders . ")";
                $stmtDelete = $this->db->prepare($sqlDeletePhotos);
                $stmtDelete->execute($data['delete_photos']);
            }
            
            if (isset($files['masina_foto']) && !empty($files['masina_foto']['name'][0])) {
                $sqlFoto = "INSERT INTO fotografije_masine_evidencije (evidencija_kontrole_id, putanja_fotografije) VALUES (:evidencija_id, :putanja)";
                $stmtFoto = $this->db->prepare($sqlFoto);
                $year = date('Y');
                $month = date('m');
                $subDir = "masine_evidencije/{$year}/{$month}/";
                $uploadDir = UPLOADS_PATH . '/' . $subDir;
                if (!is_dir($uploadDir)) { mkdir($uploadDir, 0775, true); }
                
                foreach ($files['masina_foto']['name'] as $key => $name) {
                    if ($files['masina_foto']['error'][$key] === 0) {
                        $datum = date('YmdHis');
                        $ident = preg_replace('/[^a-zA-Z0-9-]/', '', $data['ident']);
                        $kataloska = preg_replace('/[^a-zA-Z0-9-]/', '', $data['kataloska_oznaka']);
                        $serijski = preg_replace('/[^a-zA-Z0-9-]/', '', $data['serijski_broj']);
                        $fileExtension = pathinfo(basename($name), PATHINFO_EXTENSION);
                        
                        $noviNazivFajla = "{$datum}_{$ident}_{$kataloska}_{$serijski}_{$key}_" . time() . ".{$fileExtension}";

                        $uploadFajl = $uploadDir . $noviNazivFajla;
                        if (move_uploaded_file($files['masina_foto']['tmp_name'][$key], $uploadFajl)) {
                            $putanjaZaBazu = $subDir . $noviNazivFajla;
                            $stmtFoto->execute([':evidencija_id' => $id, ':putanja' => $putanjaZaBazu]);
                        }
                    }
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Greška u Evidencija::update: " . $e->getMessage());
            return false;
        }
    }

    public function deleteById($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM evidencije_kontrole WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Greška u Evidencija::deleteById: " . $e->getMessage());
            return false;
        }
    }

    public function getByIdWithDetails($id) {
        $sql = "SELECT e.*, CONCAT(u.ime, ' ', u.prezime) as kontrolor_puno_ime, p.broj_plana_kontrole FROM evidencije_kontrole e JOIN korisnici u ON e.kontrolor_id = u.id LEFT JOIN planovi_kontrole p ON e.plan_kontrole_id = p.id WHERE e.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $evidencija = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$evidencija) {
            return false;
        }

        $sqlRezultati = "SELECT rke.*, kp.redni_broj_karakteristike, gkp.naziv_grupe FROM rezultati_karakteristika_evidencije rke LEFT JOIN karakteristike_plana kp ON rke.karakteristika_plana_id = kp.id LEFT JOIN grupe_karakteristika_plana gkp ON kp.grupa_karakteristika_id = gkp.id WHERE rke.evidencija_kontrole_id = :id ORDER BY gkp.redosled_prikaza ASC, kp.redni_broj_karakteristike ASC";
        
        $stmtRezultati = $this->db->prepare($sqlRezultati);
        $stmtRezultati->bindParam(':id', $id, PDO::PARAM_INT);
        $stmtRezultati->execute();
        $evidencija['rezultati'] = $stmtRezultati->fetchAll(PDO::FETCH_ASSOC);

        $sqlFotografije = "SELECT * FROM fotografije_masine_evidencije WHERE evidencija_kontrole_id = :id ORDER BY id ASC";
        $stmtFotografije = $this->db->prepare($sqlFotografije);
        $stmtFotografije->bindParam(':id', $id, PDO::PARAM_INT);
        $stmtFotografije->execute();
        $evidencija['fotografije_masine'] = $stmtFotografije->fetchAll(PDO::FETCH_ASSOC);

        return $evidencija;
    }

    public function getTotalCountForUser($kontrolorId, $searchParams = []) {
        $sql = "SELECT COUNT(id) FROM evidencije_kontrole WHERE kontrolor_id = :kontrolor_id";
        $whereClauses = [];
        $params = [':kontrolor_id' => $kontrolorId];
        if (!empty($searchParams['ident'])) { $whereClauses[] = "product_ident_sken LIKE :ident"; $params[':ident'] = '%' . $searchParams['ident'] . '%'; }
        if (!empty($searchParams['kataloska'])) { $whereClauses[] = "product_kataloska_oznaka_sken LIKE :kataloska"; $params[':kataloska'] = '%' . $searchParams['kataloska'] . '%'; }
        if (!empty($searchParams['serijski'])) { $whereClauses[] = "product_serijski_broj_sken LIKE :serijski"; $params[':serijski'] = '%' . $searchParams['serijski'] . '%'; }
        if (!empty($whereClauses)) {
            $sql .= " AND " . implode(" AND ", $whereClauses);
        }
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Greška u Evidencija::getTotalCountForUser: " . $e->getMessage());
            return 0;
        }
    }

    public function getAllForUser($kontrolorId, $searchParams = [], $limit = 15, $offset = 0) {
        $sql = "SELECT * FROM evidencije_kontrole WHERE kontrolor_id = :kontrolor_id";
        $whereClauses = [];
        $params = [':kontrolor_id' => $kontrolorId];
        if (!empty($searchParams['ident'])) { $whereClauses[] = "product_ident_sken LIKE :ident"; $params[':ident'] = '%' . $searchParams['ident'] . '%'; }
        if (!empty($searchParams['kataloska'])) { $whereClauses[] = "product_kataloska_oznaka_sken LIKE :kataloska"; $params[':kataloska'] = '%' . $searchParams['kataloska'] . '%'; }
        if (!empty($searchParams['serijski'])) { $whereClauses[] = "product_serijski_broj_sken LIKE :serijski"; $params[':serijski'] = '%' . $searchParams['serijski'] . '%'; }
        if (!empty($whereClauses)) {
            $sql .= " AND " . implode(" AND ", $whereClauses);
        }
        $sql .= " ORDER BY datum_vreme_ispitivanja DESC LIMIT :limit OFFSET :offset";
        try {
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => &$val) { $stmt->bindParam($key, $val, PDO::PARAM_STR); }
            $stmt->bindParam(':kontrolor_id', $kontrolorId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Greška u Evidencija::getAllForUser: " . $e->getMessage());
            return [];
        }
    }

    public function getTotalRecordCount($searchParams = []) {
        $sql = "SELECT COUNT(e.id) FROM evidencije_kontrole e LEFT JOIN korisnici u ON e.kontrolor_id = u.id";
        $whereClauses = [];
        $params = [];
        if (!empty($searchParams['ident'])) { $whereClauses[] = "e.product_ident_sken LIKE :ident"; $params[':ident'] = '%' . $searchParams['ident'] . '%'; }
        if (!empty($searchParams['kataloska'])) { $whereClauses[] = "e.product_kataloska_oznaka_sken LIKE :kataloska"; $params[':kataloska'] = '%' . $searchParams['kataloska'] . '%'; }
        if (!empty($searchParams['serijski'])) { $whereClauses[] = "e.product_serijski_broj_sken LIKE :serijski"; $params[':serijski'] = '%' . $searchParams['serijski'] . '%'; }
        if (!empty($searchParams['kontrolor'])) { $whereClauses[] = "CONCAT(u.ime, ' ', u.prezime) LIKE :kontrolor"; $params[':kontrolor'] = '%' . $searchParams['kontrolor'] . '%'; }
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Greška u Evidencija::getTotalRecordCount: " . $e->getMessage());
            return 0;
        }
    }

    public function getAllRecords($searchParams = [], $limit = 15, $offset = 0) {
        $sql = "SELECT e.*, CONCAT(u.ime, ' ', u.prezime) as kontrolor_puno_ime FROM evidencije_kontrole e LEFT JOIN korisnici u ON e.kontrolor_id = u.id";
        $whereClauses = [];
        $params = [];
        if (!empty($searchParams['ident'])) { $whereClauses[] = "e.product_ident_sken LIKE :ident"; $params[':ident'] = '%' . $searchParams['ident'] . '%'; }
        if (!empty($searchParams['kataloska'])) { $whereClauses[] = "e.product_kataloska_oznaka_sken LIKE :kataloska"; $params[':kataloska'] = '%' . $searchParams['kataloska'] . '%'; }
        if (!empty($searchParams['serijski'])) { $whereClauses[] = "e.product_serijski_broj_sken LIKE :serijski"; $params[':serijski'] = '%' . $searchParams['serijski'] . '%'; }
        if (!empty($searchParams['kontrolor'])) { $whereClauses[] = "CONCAT(u.ime, ' ', u.prezime) LIKE :kontrolor"; $params[':kontrolor'] = '%' . $searchParams['kontrolor'] . '%'; }
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }
        $sql .= " ORDER BY e.datum_vreme_ispitivanja DESC LIMIT :limit OFFSET :offset";
        try {
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => &$val) { $stmt->bindParam($key, $val, PDO::PARAM_STR); }
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Greška u Evidencija::getAllRecords: " . $e->getMessage());
            return [];
        }
    }

    public function getRecordsForReport($filters = []) {
        $sql = "SELECT e.*, CONCAT(u.ime, ' ', u.prezime) as kontrolor_puno_ime, p.broj_plana_kontrole FROM evidencije_kontrole e JOIN korisnici u ON e.kontrolor_id = u.id LEFT JOIN planovi_kontrole p ON e.plan_kontrole_id = p.id";
        $whereClauses = [];
        $params = [];
        if (!empty($filters['datum_od'])) { $whereClauses[] = "e.datum_vreme_ispitivanja >= :datum_od"; $params[':datum_od'] = $filters['datum_od'] . ' 00:00:00'; }
        if (!empty($filters['datum_do'])) { $whereClauses[] = "e.datum_vreme_ispitivanja <= :datum_do"; $params[':datum_do'] = $filters['datum_do'] . ' 23:59:59'; }
        if (!empty($filters['ident'])) { $whereClauses[] = "e.product_ident_sken LIKE :ident"; $params[':ident'] = '%' . $filters['ident'] . '%'; }
        if (!empty($filters['kataloska_oznaka'])) { $whereClauses[] = "e.product_kataloska_oznaka_sken LIKE :kataloska"; $params[':kataloska'] = '%' . $filters['kataloska_oznaka'] . '%'; }
        if (!empty($filters['kontrolor_id'])) { $whereClauses[] = "e.kontrolor_id = :kontrolor_id"; $params[':kontrolor_id'] = $filters['kontrolor_id']; }
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }
        $sql .= " ORDER BY e.datum_vreme_ispitivanja DESC";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $evidencije = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmtRezultati = $this->db->prepare("SELECT * FROM rezultati_karakteristika_evidencije WHERE evidencija_kontrole_id = :id");
            foreach($evidencije as $key => $evidencija) {
                $stmtRezultati->execute([':id' => $evidencija['id']]);
                $evidencije[$key]['rezultati'] = $stmtRezultati->fetchAll(PDO::FETCH_ASSOC);
            }
            return $evidencije;
        } catch (PDOException $e) {
            error_log("Greška u Evidencija::getRecordsForReport: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Broji sve evidencije unete danas.
     */
    public function countTodayRecords() {
        date_default_timezone_set('Europe/Belgrade');
        $danasnji_datum = date('Y-m-d');

        $sql = "SELECT COUNT(id) FROM evidencije_kontrole WHERE DATE(datum_vreme_ispitivanja) = :danas";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':danas', $danasnji_datum);
            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Greška u Evidencija::countTodayRecords: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Broji sve evidencije unete u tekućem mesecu.
     */
    public function countThisMonthRecords() {
        date_default_timezone_set('Europe/Belgrade');
        $godina = date('Y');
        $mesec = date('m');

        $sql = "SELECT COUNT(id) FROM evidencije_kontrole WHERE YEAR(datum_vreme_ispitivanja) = :godina AND MONTH(datum_vreme_ispitivanja) = :mesec";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':godina', $godina);
            $stmt->bindParam(':mesec', $mesec);
            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Greška u Evidencija::countThisMonthRecords: " . $e->getMessage());
            return 0;
        }
    }

    public function getLatestRecords($limit = 5) {
        $sql = "SELECT e.id, e.product_naziv_sken, e.product_kataloska_oznaka_sken, e.datum_vreme_ispitivanja, CONCAT(u.ime, ' ', u.prezime) as kontrolor_puno_ime
                FROM evidencije_kontrole e
                LEFT JOIN korisnici u ON e.kontrolor_id = u.id
                ORDER BY e.id DESC
                LIMIT :limit";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Greška u Evidencija::getLatestRecords: " . $e->getMessage());
            return [];
        }
    }
}
?>