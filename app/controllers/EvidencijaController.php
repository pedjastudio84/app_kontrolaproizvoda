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
        // Ako je admin slučajno došao na ovu rutu, preusmeri ga na njegovu listu
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
        
        // Pretraga
        $searchParams = [
            'ident' => $_GET['search_ident'] ?? null,
            'kataloska' => $_GET['search_kataloska'] ?? null,
            'serijski' => $_GET['search_serijski'] ?? null,
        ];
        
        // Dohvatanje podataka
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
     * Prikazuje listu SVIH evidencija za ADMINISTRATORA.
     */
    public function listAll() {
       // === ISPRAVLJENA PROVERA ===
        // Proveravamo samo da li je korisnik ulogovan, jer i admin i ostali
        // korisnici koriste ovu metodu za dobijanje podataka.
        if (session_status() == PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $_SESSION['error_message'] = 'Morate biti prijavljeni da biste pristupili ovoj stranici.';
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=login');
            exit;
        }
        // === KRAJ ISPRAVKE ===
        if (!defined('PAGE_TITLE')) {
            define('PAGE_TITLE', 'Pregled Svih Evidencija');
        }

        $items_per_page = 15;
        $current_page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
        if ($current_page < 1) { $current_page = 1; }
        $offset = ($current_page - 1) * $items_per_page;

        $searchParams = [
            'ident' => $_GET['search_ident'] ?? null,
            'kataloska' => $_GET['search_kataloska'] ?? null,
            'serijski' => $_GET['search_serijski'] ?? null,
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
    
    /**
     * Prikazuje formu za kreiranje nove evidencije.
     */
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
        
        // Ako smo se vratili na formu nakon neuspešne validacije,
        // i imamo plan_kontrole_id, dohvatamo detalje plana da bismo ponovo iscrtali ček-listu.
        if (isset($formData['plan_kontrole_id'])) {
            $plan = $this->planKontroleModel->getPlanByIdWithDetails($formData['plan_kontrole_id']);
        }
        
        return ['formData' => $formData, 'plan' => $plan];
    }

    /**
     * Prikazuje detalje jedne evidencije.
     */
    public function show($id) {
        if (session_status() == PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['logged_in'])) {
            $_SESSION['error_message'] = 'Morate biti prijavljeni da biste videli detalje.';
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=login');
            exit;
        }
        
        $evidencija = $this->evidencijaModel->getByIdWithDetails($id);

        $redirectPage = ($_SESSION['user_uloga'] === 'administrator') ? 'admin_evidencije' : 'kontrolor_moji_zapisi';

        // Provera da li evidencija postoji i da li pripada ulogovanom korisniku (osim ako je admin ili ostali)
        if (!$evidencija || ($_SESSION['user_uloga'] === 'kontrolor' && $evidencija['kontrolor_id'] != $_SESSION['user_id'])) {
            $_SESSION['error_message'] = 'Traženi zapis nije pronađen ili nemate dozvolu za pregled.';
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=' . $redirectPage);
            exit;
        }
        if (!defined('PAGE_TITLE')) {
            define('PAGE_TITLE', 'Pregled Zapisa #' . $evidencija['id']);
        }
        return ['evidencija' => $evidencija];
    }

    /**
     * Prikazuje formu za izmenu postojeće evidencije.
     */
    public function edit($id) {
        $this->checkAuth();
        $evidencija = $this->evidencijaModel->getByIdWithDetails($id);
        $redirectPage = ($_SESSION['user_uloga'] === 'administrator') ? 'admin_evidencije' : 'kontrolor_moji_zapisi';
        if (!$evidencija || ($_SESSION['user_uloga'] === 'kontrolor' && $evidencija['kontrolor_id'] != $_SESSION['user_id'])) {
            $_SESSION['error_message'] = 'Traženi zapis nije pronađen ili nemate dozvolu za izmenu.';
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=' . $redirectPage);
            exit;
        }
        if (!defined('PAGE_TITLE')) {
            define('PAGE_TITLE', 'Izmena Zapisa #' . $evidencija['id']);
        }
        $plan = null;
        if (isset($evidencija['plan_kontrole_id'])) {
            $plan = $this->planKontroleModel->getPlanByIdWithDetails($evidencija['plan_kontrole_id']);
        }
        return ['evidencija' => $evidencija, 'plan' => $plan];
    }

    /**
     * Čuva novu evidenciju.
     */
    public function store() {
        $this->checkAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { exit('Neovlašćen pristup.'); }
        
        $data = $_POST;
        $files = $_FILES;

        // === POČETAK IZMENE: Detaljnija Server-Side Validacija ===
        $errors = [];
        if (empty(trim($data['ident']))) {
            $errors[] = 'Polje "Ident" je obavezno.';
        }
        if (empty(trim($data['serijski_broj']))) {
            $errors[] = 'Polje "Serijski broj" je obavezno.';
        }
        if (empty($data['plan_kontrole_id'])) {
            $errors[] = 'Plan kontrole nije učitan. Molimo skenirajte QR kod ili unesite Ident ponovo.';
        }
        // Provera da li su sva polja u ček listi popunjena
        if (!empty($data['rezultati'])) {
            foreach($data['rezultati'] as $key => $rezultat) {
                if (empty($rezultat['vrednost'])) {
                    $errors[] = 'Sva polja u ček-listi moraju biti popunjena.';
                    break; // Dovoljno je da nađemo prvu grešku
                }
            }
        } else {
            $errors[] = 'Ček-lista je prazna.';
        }

        if (!empty($errors)) {
            $_SESSION['error_message'] = implode('<br>', $errors); // Prikaži sve greške
            $_SESSION['form_data'] = $data; // Sačuvaj unete podatke
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=kontrolor_novi_zapis&vrsta=' . urlencode($data['vrsta_kontrole']));
            exit;
        }
        // === KRAJ IZMENE ===
        
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
    
    /**
     * Ažurira postojeću evidenciju.
     */
    public function update($id) {
        $this->checkAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { exit('Neovlašćen pristup.'); }
        
        $redirectPage = ($_SESSION['user_uloga'] === 'administrator') ? 'admin_evidencije' : 'kontrolor_moji_zapisi';

        $evidencija = $this->evidencijaModel->getByIdWithDetails($id);
        if (!$evidencija || ($_SESSION['user_uloga'] === 'kontrolor' && $evidencija['kontrolor_id'] != $_SESSION['user_id'])) {
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

    /**
     * Briše evidenciju o kontroli.
     */
    public function delete($id) {
        $this->checkAuth();
        
        $redirectPage = ($_SESSION['user_uloga'] === 'administrator') ? 'admin_evidencije' : 'kontrolor_moji_zapisi';

        $evidencija = $this->evidencijaModel->getByIdWithDetails($id);
        if (!$evidencija) {
            $_SESSION['error_message'] = 'Zapis nije pronađen.';
        } elseif ($_SESSION['user_uloga'] === 'kontrolor' && $evidencija['kontrolor_id'] != $_SESSION['user_id']) {
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
}
?>