<?php
require_once __DIR__ . '/../models/PlanKontrole.php';

class PlanKontroleController {
    private $db;
    private $planKontroleModel;

    public function __construct(PDO $dbConnection) {
        $this->db = $dbConnection;
        $this->planKontroleModel = new PlanKontrole($this->db);
    }

    /**
     * Pomoćna funkcija za proveru da li je korisnik ulogovan administrator.
     */
    private function checkAdmin() {
        if (session_status() == PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || $_SESSION['user_uloga'] !== 'administrator') {
            $_SESSION['error_message'] = 'Nemate dozvolu za pristup ovoj stranici.';
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=login');
            exit;
        }
    }

    /**
     * Pomoćna funkcija za validaciju podataka plana.
     */
    private function validatePlanData($planData, $grupeData, $isUpdate = false, $id = null) {
        $errors = [];
        if (empty($planData['broj_plana_kontrole'])) { $errors[] = 'Broj plana kontrole je obavezan.'; }
        if ($this->planKontroleModel->planNumberExists($planData['broj_plana_kontrole'], $id)) { $errors[] = 'Plan sa ovim brojem već postoji.'; }
        if (empty($planData['ident_proizvoda'])) { $errors[] = 'Ident proizvoda je obavezan.'; }
        if (empty($planData['naziv_proizvoda'])) { $errors[] = 'Naziv proizvoda je obavezan.'; }
        
        if (empty($grupeData)) {
            $errors[] = 'Plan kontrole mora imati najmanje jednu grupu karakteristika.';
        } else {
            foreach ($grupeData as $g_index => $grupa) {
                if (empty($grupa['naziv_grupe'])) { $errors[] = 'Naziv grupe ' . ((int)$g_index + 1) . ' je obavezan.'; }
                if (empty($grupa['karakteristike'])) {
                    $errors[] = 'Grupa "' . htmlspecialchars($grupa['naziv_grupe']) . '" mora imati najmanje jednu karakteristiku.';
                } else {
                    foreach ($grupa['karakteristike'] as $k_index => $karakteristika) {
                        if (empty($karakteristika['opis_karakteristike'])) {
                            $errors[] = 'Opis karakteristike br. ' . ((int)$k_index + 1) . ' u grupi "' . htmlspecialchars($grupa['naziv_grupe']) . '" je obavezan.';
                        }
                    }
                }
            }
        }
        return $errors;
    }

    /**
     * Prikazuje listu svih planova kontrole, sa pretragom i paginacijom.
     */
    public function index() {
        // Samo proveravamo da li je korisnik ulogovan
        if (session_status() == PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['logged_in'])) {
             header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=login'); exit;
        }

        $searchParams = [
            'broj_plana' => $_GET['search_broj_plana'] ?? null,
            'ident' => $_GET['search_ident'] ?? null,
            'kataloska' => $_GET['search_kataloska'] ?? null,
            'naziv' => $_GET['search_naziv'] ?? null,
        ];

        $items_per_page = 10;
        $current_page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
        if ($current_page < 1) { $current_page = 1; }
        $offset = ($current_page - 1) * $items_per_page;
        
        $planovi = $this->planKontroleModel->getAll($searchParams, $items_per_page, $offset);
        $total_items = $this->planKontroleModel->getTotalCount();
        $total_pages = ceil($total_items / $items_per_page);

        return [
            'planovi' => $planovi,
            'total_pages' => $total_pages,
            'current_page' => $current_page,
            'search_params' => $searchParams,
        ];
    }

    /**
     * Prikazuje formu za kreiranje novog plana.
     */
    public function create() {
        $this->checkAdmin();
        if (!defined('PAGE_TITLE')) {
            define('PAGE_TITLE', 'Dodavanje Novog Plana Kontrole');
        }
        return [];
    }
    
    /**
     * Prikazuje detalje jednog plana kontrole.
     */
    public function show($id) {
       // Samo proveravamo da li je korisnik ulogovan
        if (session_status() == PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['logged_in'])) {
             header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=login'); exit;
        }
        $plan = $this->planKontroleModel->getPlanByIdWithDetails($id);
        if (!$plan) {
            $_SESSION['error_message'] = 'Plan kontrole nije pronađen.';
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=admin_plans');
            exit;
        }
        if (!defined('PAGE_TITLE')) {
            define('PAGE_TITLE', 'Pregled Plana: ' . htmlspecialchars($plan['broj_plana_kontrole']));
        }
        return ['plan' => $plan];
    }
    
    /**
     * Prikazuje formu za izmenu postojećeg plana.
     */
    public function edit($id) {
        $this->checkAdmin();
        $plan = $this->planKontroleModel->getPlanByIdWithDetails($id);
        if (!$plan) {
            $_SESSION['error_message'] = 'Plan kontrole nije pronađen.';
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=admin_plans');
            exit;
        }
        if (!defined('PAGE_TITLE')) { define('PAGE_TITLE', 'Izmena Plana Kontrole: ' . htmlspecialchars($plan['broj_plana_kontrole'])); }
        return ['plan' => $plan];
    }

    /**
     * Priprema podatke za kopiranje postojećeg plana.
     */
    public function copy($id) {
        $this->checkAdmin();
        $plan = $this->planKontroleModel->getPlanByIdWithDetails($id);
        if (!$plan) {
            $_SESSION['error_message'] = 'Plan kontrole za kopiranje nije pronađen.';
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=admin_plans');
            exit;
        }
        $plan['broj_plana_kontrole'] .= '_COPY';
        unset($plan['id']);
        if (!defined('PAGE_TITLE')) { define('PAGE_TITLE', 'Kopiranje Plana Kontrole'); }
        return ['plan' => $plan, 'isCopy' => true];
    }
    
    /**
     * Čuva novi plan kontrole u bazi.
     */
    public function store() {
        $this->checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { exit('Neovlašćen pristup.'); }
        $planData = [
            'broj_plana_kontrole' => trim($_POST['broj_plana_kontrole'] ?? ''),
            'ident_proizvoda' => trim($_POST['ident_proizvoda'] ?? ''),
            'kataloska_oznaka' => trim($_POST['kataloska_oznaka'] ?? NULL),
            'naziv_proizvoda' => trim($_POST['naziv_proizvoda'] ?? ''),
            'broj_operacije' => trim($_POST['broj_operacije'] ?? NULL),
            'kreirao_korisnik_id' => $_SESSION['user_id']
        ];
        $grupeData = $_POST['grupe'] ?? [];
        if (is_array($grupeData)) { $grupeData = array_filter($grupeData, 'is_array'); }
        $errors = $this->validatePlanData($planData, $grupeData);
        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=admin_plan_create');
            exit;
        }
        if ($this->planKontroleModel->createPlan($planData, $grupeData, $_FILES)) {
            $_SESSION['success_message'] = 'Plan kontrole je uspešno kreiran.';
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=admin_plans');
        } else {
            $_SESSION['form_errors'] = ['Došlo je do greške prilikom upisa u bazu.'];
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=admin_plan_create');
        }
        exit;
    }
    
    /**
     * Ažurira postojeći plan u bazi.
     */
    public function update($id) {
        $this->checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { exit('Neovlašćen pristup.'); }
        $planData = [
            'broj_plana_kontrole' => trim($_POST['broj_plana_kontrole'] ?? ''),
            'ident_proizvoda' => trim($_POST['ident_proizvoda'] ?? ''),
            'kataloska_oznaka' => trim($_POST['kataloska_oznaka'] ?? NULL),
            'naziv_proizvoda' => trim($_POST['naziv_proizvoda'] ?? ''),
            'broj_operacije' => trim($_POST['broj_operacije'] ?? NULL),
        ];
        $grupeData = $_POST['grupe'] ?? [];
        if (is_array($grupeData)) { $grupeData = array_filter($grupeData, 'is_array'); }
        $errors = $this->validatePlanData($planData, $grupeData, true, $id);
        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=admin_plan_edit&id=' . $id);
            exit;
        }
        if ($this->planKontroleModel->updatePlan($id, $planData, $grupeData, $_FILES)) {
            $_SESSION['success_message'] = 'Plan kontrole je uspešno ažuriran.';
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=admin_plans');
        } else {
            $_SESSION['form_errors'] = ['Došlo je do greške prilikom ažuriranja baze.'];
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=admin_plan_edit&id=' . $id);
        }
        exit;
    }

    /**
     * Briše plan kontrole.
     */
    public function delete($id) {
        $this->checkAdmin();
        if ($this->planKontroleModel->deletePlan($id)) {
            $_SESSION['success_message'] = 'Plan kontrole je uspešno obrisan.';
        } else {
            $_SESSION['error_message'] = 'Došlo je do greške prilikom brisanja plana.';
        }
        header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=admin_plans');
        exit;
    }

    /**
     * API endpoint za dohvatanje detalja plana putem AJAX-a.
     */
    public function getPlanForAjax() {
        if (session_status() == PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['logged_in'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Pristup odbijen']);
            exit;
        }
        $ident = $_GET['ident'] ?? null;
        if (!$ident) {
            http_response_code(400);
            echo json_encode(['error' => 'Ident proizvoda nije prosleđen.']);
            exit;
        }
        $plan = $this->planKontroleModel->getPlanByIdentWithDetails($ident);
        header('Content-Type: application/json; charset=utf-8');
        if (!$plan) {
            http_response_code(404);
            echo json_encode(['error' => 'Plan kontrole za dati ident nije pronađen.']);
        } else {
            echo json_encode($plan);
        }
        exit;
    }
}
?>