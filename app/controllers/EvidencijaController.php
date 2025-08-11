<?php
require_once __DIR__ . '/../models/Evidencija.php';
require_once __DIR__ . '/../models/PlanKontrole.php'; // Potreban nam je i ovaj model

class EvidencijaController {
    private $db;
    private $evidencijaModel;
    private $planKontroleModel; // Dodajemo property

    public function __construct(PDO $dbConnection) {
        $this->db = $dbConnection;
        $this->evidencijaModel = new Evidencija($this->db);
        $this->planKontroleModel = new PlanKontrole($this->db); // Instanciramo model
    }

    /**
     * Proverava da li je korisnik ulogovan i da li je kontrolor ili admin.
     */
    private function checkAuth() {
        if (session_status() == PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['user_uloga'], ['kontrolor', 'administrator'])) {
            $_SESSION['error_message'] = 'Nemate dozvolu za pristup ovoj stranici.';
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=login');
            exit;
        }
    }

    /**
     * Prikazuje listu svih evidencija za trenutno ulogovanog KONTROLORA.
     */
    public function index() {
        $this->checkAuth();
        if ($_SESSION['user_uloga'] === 'administrator') {
             header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=admin_evidencije'); exit;
        }

        if (!defined('PAGE_TITLE')) {
            define('PAGE_TITLE', 'Moji Zapisi o Kontroli');
        }
        
        $items_per_page = 15;
        $current_page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
        if ($current_page < 1) { $current_page = 1; }
        $offset = ($current_page - 1) * $items_per_page;
        
        $searchParams = [
            'query' => $_GET['search'] ?? null,
        ];
        
        $evidencije = $this->evidencijaModel->getAllForUser($_SESSION['user_id'], $searchParams, $items_per_page, $offset);
        $total_items = $this->evidencijaModel->getTotalCountForUser($_SESSION['user_id'], $searchParams);
        $total_pages = ceil($total_items / $items_per_page);

        return [
            'evidencije' => $evidencije,
            'total_pages' => $total_pages,
            'current_page' => $current_page,
            'search_params' => $searchParams,
        ];
    }
    
    /**
     * Prikazuje listu SVIH evidencija za ADMINISTRATORA i OSTALE.
     */
    public function listAll() {
        if (session_status() == PHP_SESSION_NONE) { session_start(); }
        
        // ===== ISPRAVKA DOZVOLA =====
        // Sada proveravamo da li korisnik ima JEDNU OD DOZVOLJENIH uloga
        if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['user_uloga'], ['administrator', 'ostali'])) {
            $_SESSION['error_message'] = 'Nemate dozvolu za pristup ovoj stranici.';
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=login');
            exit;
        }
        // ===== KRAJ ISPRAVKE =====

        if (!defined('PAGE_TITLE')) {
            define('PAGE_TITLE', 'Pregled Svih Evidencija');
        }

        $items_per_page = 15;
        $current_page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
        if ($current_page < 1) { $current_page = 1; }
        $offset = ($current_page - 1) * $items_per_page;

        $searchParams = [
            'query' => $_GET['search'] ?? null,
            'kontrolor' => $_GET['search_kontrolor'] ?? null,
        ];

        $evidencije = $this->evidencijaModel->getAllRecords($searchParams, $items_per_page, $offset);
        $total_items = $this->evidencijaModel->getTotalRecordCount($searchParams);
        $total_pages = ceil($total_items / $items_per_page);

        return [
            'evidencije' => $evidencije,
            'total_pages' => $total_pages,
            'current_page' => $current_page,
            'search_params' => $searchParams,
        ];
    }
    
    // ... ostatak fajla (create, show, edit, itd.) ostaje nepromenjen ...
    public function create() {
        $this->checkAuth();
        
        $vrsta_kontrole = $_GET['vrsta'] ?? 'nepoznata';
        $vrsta_kontrole_tekst = ($vrsta_kontrole === 'redovna_kontrola') ? 'Redovna kontrola' : 'Kontrola pre isporuke';
        $pageTitle = 'Novi Zapis - ' . $vrsta_kontrole_tekst;

        if (!defined('PAGE_TITLE')) {
            define('PAGE_TITLE', $pageTitle);
        }

        $formData = $_SESSION['form_data'] ?? [];
        $plan = null;
        
        if (isset($formData['plan_kontrole_id'])) {
            $plan = $this->planKontroleModel->getPlanByIdWithDetails($formData['plan_kontrole_id']);
        }
        
        return ['formData' => $formData, 'plan' => $plan];
    }

    public function show($id) {
        if (session_status() == PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['logged_in'])) {
            $_SESSION['error_message'] = 'Morate biti prijavljeni da biste videli detalje.';
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=login');
            exit;
        }
        
        $evidencija = $this->evidencijaModel->getByIdWithDetails($id);
        $redirectPage = ($_SESSION['user_uloga'] === 'administrator') ? 'admin_evidencije' : 'kontrolor_moji_zapisi';

        if (!$evidencija || ($_SESSION['user_uloga'] === 'kontrolor' && (int)$evidencija['kontrolor_id'] != (int)$_SESSION['user_id'])) {
            $_SESSION['error_message'] = 'Traženi zapis nije pronađen ili nemate dozvolu za pregled.';
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=' . $redirectPage);
            exit;
        }
        if (!defined('PAGE_TITLE')) {
            define('PAGE_TITLE', 'Pregled Zapisa #' . $evidencija['id']);
        }

        $istorija = $this->evidencijaModel->getHistoryForProduct(
            $evidencija['product_ident_sken'],
            $evidencija['product_serijski_broj_sken'],
            $id
        );

        return [
            'evidencija' => $evidencija,
            'istorija' => $istorija
        ];
    }

    public function edit($id) {
        $this->checkAuth();
        $evidencija = $this->evidencijaModel->getByIdWithDetails($id);
        $redirectPage = ($_SESSION['user_uloga'] === 'administrator') ? 'admin_evidencije' : 'kontrolor_moji_zapisi';
        
        if (!$evidencija || ($_SESSION['user_uloga'] === 'kontrolor' && (int)$evidencija['kontrolor_id'] != (int)$_SESSION['user_id'])) {
            $_SESSION['error_message'] = 'Traženi zapis nije pronađen ili nemate dozvolu za izmenu.';
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=' . $redirectPage);
            exit;
        }
        if (!defined('PAGE_TITLE')) {
            define('PAGE_TITLE', 'Izmena Zapisa #' . $evidencija['id']);
        }
        return ['evidencija' => $evidencija];
    }

    public function store() {
        $this->checkAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { exit('Neovlašćen pristup.'); }
        
        $data = $_POST;
        $files = $_FILES;
        
        $errors = [];
        if (empty(trim($data['ident']))) { $errors[] = 'Polje "Ident" je obavezno.'; }
        if (empty(trim($data['serijski_broj']))) { $errors[] = 'Polje "Serijski broj" je obavezno.'; }
        if (empty($data['plan_kontrole_id'])) { $errors[] = 'Plan kontrole nije učitan. Molimo skenirajte QR kod ili unesite Ident ponovo.'; }
        if (!empty($data['rezultati'])) {
            foreach($data['rezultati'] as $key => $rezultat) {
                if (empty($rezultat['vrednost'])) {
                    $errors[] = 'Sva polja u ček-listi moraju biti popunjena.';
                    break;
                }
            }
        } else {
            $errors[] = 'Ček-lista je prazna.';
        }

        if (!empty($errors)) {
            $_SESSION['error_message'] = implode('<br>', $errors);
            $_SESSION['form_data'] = $data;
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=kontrolor_novi_zapis&vrsta=' . urlencode($data['vrsta_kontrole']));
            exit;
        }
        
        $result = $this->evidencijaModel->create($data, $files, $_SESSION['user_id']);

        if ($result) {
            unset($_SESSION['form_data']);
            $_SESSION['success_message'] = 'Evidencija kontrole je uspešno sačuvana.';
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=kontrolor_moji_zapisi');
        } else {
            $_SESSION['error_message'] = 'Došlo je do greške prilikom čuvanja evidencije.';
            $_SESSION['form_data'] = $data;
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=kontrolor_novi_zapis&vrsta=' . urlencode($data['vrsta_kontrole']));
        }
        exit;
    }
    
    public function update($id) {
        $this->checkAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { exit('Neovlašćen pristup.'); }
        
        $redirectPage = ($_SESSION['user_uloga'] === 'administrator') ? 'admin_evidencije' : 'kontrolor_moji_zapisi';

        $evidencija = $this->evidencijaModel->getByIdWithDetails($id);
        
        if (!$evidencija || ($_SESSION['user_uloga'] === 'kontrolor' && (int)$evidencija['kontrolor_id'] != (int)$_SESSION['user_id'])) {
            $_SESSION['error_message'] = 'Nemate dozvolu za izmenu ovog zapisa.';
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=' . $redirectPage);
            exit;
        }

        $data = $_POST;
        $files = $_FILES;
        
        $result = $this->evidencijaModel->update($id, $data, $files);

        if ($result) {
            $_SESSION['success_message'] = 'Zapis #' . $id . ' je uspešno ažuriran.';
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=' . $redirectPage);
        } else {
            $_SESSION['error_message'] = 'Došlo je do greške prilikom ažuriranja zapisa.';
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=kontrolor_zapis_edit&id=' . $id);
        }
        exit;
    }

    public function delete($id) {
        $this->checkAuth();
        $redirectPage = ($_SESSION['user_uloga'] === 'administrator') ? 'admin_evidencije' : 'kontrolor_moji_zapisi';
        $evidencija = $this->evidencijaModel->getByIdWithDetails($id);

        if (!$evidencija) {
            $_SESSION['error_message'] = 'Zapis nije pronađen.';
        } 
        elseif ($_SESSION['user_uloga'] === 'kontrolor' && (int)$evidencija['kontrolor_id'] != (int)$_SESSION['user_id']) {
            $_SESSION['error_message'] = 'Nemate dozvolu da obrišete ovaj zapis.';
        } else {
            if ($this->evidencijaModel->deleteById($id)) {
                $_SESSION['success_message'] = 'Zapis #' . $id . ' je uspešno obrisan.';
            } else {
                $_SESSION['error_message'] = 'Došlo je do greške prilikom brisanja zapisa.';
            }
        }
        
        header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=' . $redirectPage);
        exit;
    }

    public function checkExistingRecord() {
        if (session_status() == PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['logged_in'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Pristup odbijen']);
            exit;
        }

        $ident = $_GET['ident'] ?? null;
        $serijski = $_GET['serijski'] ?? null;

        if (!$ident || !$serijski) {
            http_response_code(400);
            echo json_encode(['error' => 'Ident i serijski broj su obavezni.']);
            exit;
        }

        $record = $this->evidencijaModel->findByProductDetails($ident, $serijski);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['postoji' => ($record !== false), 'data' => $record]);
        exit;
    }
}
?>