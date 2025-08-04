<?php
class PlanKontrole {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function getTotalCount() {
        try {
            return (int) $this->db->query("SELECT COUNT(id) FROM planovi_kontrole WHERE status = 'aktivan'")->fetchColumn();
        } catch (PDOException $e) {
            error_log("Greška u PlanKontrole::getTotalCount: " . $e->getMessage());
            return 0;
        }
    }

    public function getAll($searchParams = [], $limit = 10, $offset = 0) {
        $sql = "SELECT pk.id, pk.broj_plana_kontrole, pk.ident_proizvoda, pk.kataloska_oznaka, pk.naziv_proizvoda, pk.kreiran_datuma, CONCAT(k.ime, ' ', k.prezime) as kreator_puno_ime FROM planovi_kontrole pk LEFT JOIN korisnici k ON pk.kreirao_korisnik_id = k.id WHERE pk.status = 'aktivan'";
        $whereClauses = [];
        $params = [];
        if (!empty($searchParams['broj_plana'])) { $whereClauses[] = "pk.broj_plana_kontrole LIKE :broj_plana"; $params[':broj_plana'] = '%' . $searchParams['broj_plana'] . '%'; }
        if (!empty($searchParams['ident'])) { $whereClauses[] = "pk.ident_proizvoda LIKE :ident"; $params[':ident'] = '%' . $searchParams['ident'] . '%'; }
        if (!empty($searchParams['kataloska'])) { $whereClauses[] = "pk.kataloska_oznaka LIKE :kataloska"; $params[':kataloska'] = '%' . $searchParams['kataloska'] . '%'; }
        if (!empty($searchParams['naziv'])) { $whereClauses[] = "pk.naziv_proizvoda LIKE :naziv"; $params[':naziv'] = '%' . $searchParams['naziv'] . '%'; }
        if (!empty($whereClauses)) { $sql .= " AND " . implode(" AND ", $whereClauses); }
        $sql .= " ORDER BY pk.id DESC LIMIT :limit OFFSET :offset";
        try {
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => &$val) { $stmt->bindParam($key, $val, PDO::PARAM_STR); }
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Greška u PlanKontrole::getAll: " . $e->getMessage());
            return [];
        }
    }

    public function getPlanById($id) {
        $stmt = $this->db->prepare("SELECT * FROM planovi_kontrole WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getPlanByIdentWithDetails($ident) {
        $stmtPlan = $this->db->prepare("SELECT * FROM planovi_kontrole WHERE ident_proizvoda = :ident AND status = 'aktivan' LIMIT 1");
        $stmtPlan->bindParam(':ident', $ident, PDO::PARAM_STR);
        $stmtPlan->execute();
        $plan = $stmtPlan->fetch(PDO::FETCH_ASSOC);
        if (!$plan) { return false; }
        return $this->getPlanByIdWithDetails($plan['id']);
    }

    public function getPlanByIdWithDetails($id) {
        $plan = $this->getPlanById($id);
        if (!$plan) return false;
        
        $verzijaGrupaId = $plan['verzija_grupa_id'] ?? $plan['id'];

        if ($verzijaGrupaId) {
            $stmtVerzije = $this->db->prepare("
                SELECT p.id, p.verzija_broj, p.status, p.azuriran_datuma, p.verzija_napomena, CONCAT(k.ime, ' ', k.prezime) as modifikovao_korisnik
                FROM planovi_kontrole p
                LEFT JOIN korisnici k ON p.modifikovao_korisnik_id = k.id
                WHERE p.verzija_grupa_id = :verzija_grupa_id
                ORDER BY p.verzija_broj DESC
            ");
            $stmtVerzije->execute([':verzija_grupa_id' => $verzijaGrupaId]);
            $plan['verzije'] = $stmtVerzije->fetchAll(PDO::FETCH_ASSOC);
        }

        $stmtGrupe = $this->db->prepare("SELECT * FROM grupe_karakteristika_plana WHERE plan_kontrole_id = :plan_id ORDER BY redosled_prikaza ASC");
        $stmtGrupe->bindParam(':plan_id', $id, PDO::PARAM_INT);
        $stmtGrupe->execute();
        $grupe = $stmtGrupe->fetchAll(PDO::FETCH_ASSOC);
        foreach ($grupe as $g_index => $grupa) {
            $stmtKarakteristike = $this->db->prepare("SELECT * FROM karakteristike_plana WHERE grupa_karakteristika_id = :grupa_id ORDER BY pozicija ASC");
            $stmtKarakteristike->bindParam(':grupa_id', $grupa['id'], PDO::PARAM_INT);
            $stmtKarakteristike->execute();
            $grupe[$g_index]['karakteristike'] = $stmtKarakteristike->fetchAll(PDO::FETCH_ASSOC);
        }
        $plan['grupe'] = $grupe;
        return $plan;
    }

    public function planNumberExists($brojPlana, $excludeId = null) {
        $sql = "SELECT id FROM planovi_kontrole WHERE broj_plana_kontrole = :broj_plana";
        if ($excludeId) { $sql .= " AND id != :id"; }
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':broj_plana', $brojPlana);
        if ($excludeId) { $stmt->bindParam(':id', $excludeId, PDO::PARAM_INT); }
        $stmt->execute();
        return $stmt->fetch() !== false;
    }

    private function savePlanDetails($planId, $grupeData, $filesData) {
        if (!empty($grupeData)) {
            $grupeData = array_values($grupeData);

            foreach ($grupeData as $g_index => $grupa) {
                $redosled = isset($grupa['redosled_prikaza']) ? (int)$grupa['redosled_prikaza'] : $g_index;
                $sqlGrupa = "INSERT INTO grupe_karakteristika_plana (plan_kontrole_id, naziv_grupe, redosled_prikaza) VALUES (:plan_id, :naziv_grupe, :redosled)";
                $stmtGrupa = $this->db->prepare($sqlGrupa);
                $stmtGrupa->execute([':plan_id' => $planId, ':naziv_grupe' => $grupa['naziv_grupe'], ':redosled' => $redosled]);
                $grupaId = $this->db->lastInsertId();

                if (!empty($grupa['karakteristike'])) {
                    $karakteristikeData = array_values($grupa['karakteristike']);

                    foreach ($karakteristikeData as $k_index => $karakteristika) {
                        $putanjaFajla = $karakteristika['postojeca_fotografija'] ?? null;
                        
                        if (isset($filesData['name'][$g_index]['karakteristike'][$k_index]['fotografija']) && $filesData['error'][$g_index]['karakteristike'][$k_index]['fotografija'] == 0) {
                            $subDir = "karakteristike_planova/"; $uploadDir = UPLOADS_PATH . '/' . $subDir;
                            if (!is_dir($uploadDir)) { mkdir($uploadDir, 0775, true); }
                            $fajlIme = time() . '_' . basename($filesData['name'][$g_index]['karakteristike'][$k_index]['fotografija']);
                            $uploadFajl = $uploadDir . $fajlIme;
                            if (move_uploaded_file($filesData['tmp_name'][$g_index]['karakteristike'][$k_index]['fotografija'], $uploadFajl)) { $putanjaFajla = $subDir . $fajlIme; }
                        }
                        $sqlKarakteristika = "INSERT INTO karakteristike_plana (grupa_karakteristika_id, redni_broj_karakteristike, opis_karakteristike, putanja_fotografije_opis, vrsta_karakteristike, kontrolni_alat_nacin, velicina_uzorka, pozicija) VALUES (:grupa_id, :redni_broj, :opis, :putanja_foto, :vrsta, :alat, :uzorak, :pozicija)";
                        $stmtKarakteristika = $this->db->prepare($sqlKarakteristika);
                        $pozicija = isset($karakteristika['pozicija']) ? (int)$karakteristika['pozicija'] : $k_index;
                        $stmtKarakteristika->execute([
                            ':grupa_id' => $grupaId, 
                            ':redni_broj' => $karakteristika['redni_broj_karakteristike'], 
                            ':opis' => $karakteristika['opis_karakteristike'], 
                            ':putanja_foto' => $putanjaFajla, 
                            ':vrsta' => $karakteristika['vrsta_karakteristike'], 
                            ':alat' => $karakteristika['kontrolni_alat_nacin'] ?? null, 
                            ':uzorak' => $karakteristika['velicina_uzorka'] ?? null, 
                            ':pozicija' => $pozicija
                        ]);
                    }
                }
            }
        }
    }

    public function createPlan($planData, $grupeData, $filesData) {
        try {
            $this->db->beginTransaction();
            
            $sqlPlan = "INSERT INTO planovi_kontrole (broj_plana_kontrole, ident_proizvoda, kataloska_oznaka, naziv_proizvoda, broj_operacije, kreirao_korisnik_id, status, verzija_broj) VALUES (:broj_plana_kontrole, :ident_proizvoda, :kataloska_oznaka, :naziv_proizvoda, :broj_operacije, :kreirao_korisnik_id, :status, :verzija_broj)";
            $stmtPlan = $this->db->prepare($sqlPlan);
            
            $paramsToExecute = [
                ':broj_plana_kontrole' => $planData['broj_plana_kontrole'],
                ':ident_proizvoda' => $planData['ident_proizvoda'],
                ':kataloska_oznaka' => $planData['kataloska_oznaka'],
                ':naziv_proizvoda' => $planData['naziv_proizvoda'],
                ':broj_operacije' => $planData['broj_operacije'],
                ':kreirao_korisnik_id' => $planData['kreirao_korisnik_id'],
                ':status' => 'aktivan',
                ':verzija_broj' => 1
            ];
            
            $stmtPlan->execute($paramsToExecute);
            
            $planId = $this->db->lastInsertId();
            
            $stmtUpdateVersionGroup = $this->db->prepare("UPDATE planovi_kontrole SET verzija_grupa_id = :id WHERE id = :id");
            $stmtUpdateVersionGroup->execute([':id' => $planId]);
            
            $this->savePlanDetails($planId, $grupeData, $filesData);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Greška u PlanKontrole::createPlan: " . $e->getMessage());
            return false;
        }
    }

    public function updatePlan($id, $planData, $grupeData, $filesData, $userId) {
        try {
            $this->db->beginTransaction();
            $sqlPlan = "UPDATE planovi_kontrole SET broj_plana_kontrole = :broj_plana_kontrole, ident_proizvoda = :ident_proizvoda, kataloska_oznaka = :kataloska_oznaka, naziv_proizvoda = :naziv_proizvoda, broj_operacije = :broj_operacije, modifikovao_korisnik_id = :modifikovao_korisnik_id WHERE id = :id";
            $stmtPlan = $this->db->prepare($sqlPlan);
            
            $paramsForUpdate = [
                ':broj_plana_kontrole' => $planData['broj_plana_kontrole'],
                ':ident_proizvoda' => $planData['ident_proizvoda'],
                ':kataloska_oznaka' => $planData['kataloska_oznaka'],
                ':naziv_proizvoda' => $planData['naziv_proizvoda'],
                ':broj_operacije' => $planData['broj_operacije'],
                ':modifikovao_korisnik_id' => $userId,
                ':id' => $id
            ];
            $stmtPlan->execute($paramsForUpdate);

            $stmtDelete = $this->db->prepare("DELETE FROM grupe_karakteristika_plana WHERE plan_kontrole_id = :plan_id");
            $stmtDelete->execute([':plan_id' => $id]);
            
            $this->savePlanDetails($id, $grupeData, $filesData);
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Greška u PlanKontrole::updatePlan: " . $e->getMessage());
            return false;
        }
    }

    public function createNewVersion($oldPlanId, $planData, $grupeData, $filesData, $userId, $napomena) {
        try {
            $this->db->beginTransaction();
            $stariPlan = $this->getPlanById($oldPlanId);
            if (!$stariPlan) { throw new Exception("Stari plan nije pronađen."); }
            $verzijaGrupaId = $stariPlan['verzija_grupa_id'] ?? $stariPlan['id'];
            $novaVerzijaBroj = (int)$stariPlan['verzija_broj'] + 1;
            
            $sqlArchive = "UPDATE planovi_kontrole SET status = 'arhiviran', modifikovao_korisnik_id = :user_id, verzija_grupa_id = :verzija_grupa_id WHERE id = :id";
            $stmtArchive = $this->db->prepare($sqlArchive);
            $stmtArchive->execute([':user_id' => $userId, ':verzija_grupa_id' => $verzijaGrupaId, ':id' => $oldPlanId]);
            
            $sqlNewVersion = "INSERT INTO planovi_kontrole (broj_plana_kontrole, ident_proizvoda, kataloska_oznaka, naziv_proizvoda, broj_operacije, kreirao_korisnik_id, status, verzija_grupa_id, verzija_broj, verzija_napomena) VALUES (:broj_plana_kontrole, :ident_proizvoda, :kataloska_oznaka, :naziv_proizvoda, :broj_operacije, :kreirao_korisnik_id, 'aktivan', :verzija_grupa_id, :verzija_broj, :verzija_napomena)";
            $stmtNewVersion = $this->db->prepare($sqlNewVersion);
            
            $paramsForNewVersion = [
                ':broj_plana_kontrole' => $planData['broj_plana_kontrole'],
                ':ident_proizvoda' => $planData['ident_proizvoda'],
                ':kataloska_oznaka' => $planData['kataloska_oznaka'],
                ':naziv_proizvoda' => $planData['naziv_proizvoda'],
                ':broj_operacije' => $planData['broj_operacije'],
                ':kreirao_korisnik_id' => $userId,
                ':verzija_grupa_id' => $verzijaGrupaId,
                ':verzija_broj' => $novaVerzijaBroj,
                ':verzija_napomena' => $napomena
            ];
            $stmtNewVersion->execute($paramsForNewVersion);
            
            $noviPlanId = $this->db->lastInsertId();
            $this->savePlanDetails($noviPlanId, $grupeData, $filesData);
            $this->db->commit();
            return $noviPlanId;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Greška u PlanKontrole::createNewVersion: " . $e->getMessage());
            return false;
        }
    }

    public function deletePlan($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM planovi_kontrole WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Greška u PlanKontrole::deletePlan: " . $e->getMessage());
            return false;
        }
    }

    public function getLatestPlans($limit = 5) {
        $sql = "SELECT pk.id, pk.broj_plana_kontrole, pk.naziv_proizvoda, pk.kataloska_oznaka, pk.kreiran_datuma, CONCAT(k.ime, ' ', k.prezime) as kreator_puno_ime FROM planovi_kontrole pk LEFT JOIN korisnici k ON pk.kreirao_korisnik_id = k.id ORDER BY pk.id DESC LIMIT :limit";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Greška u PlanKontrole::getLatestPlans: " . $e->getMessage());
            return [];
        }
    }
}
?>