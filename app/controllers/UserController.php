<?php

require_once __DIR__ . '/../models/User.php';

class UserController {
    private $db;
    private $userModel;

    public function __construct(PDO $dbConnection) {
        $this->db = $dbConnection;
        $this->userModel = new User($this->db);
    }

    private function checkAdmin() {
        if (session_status() == PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_uloga'] !== 'administrator') {
            $_SESSION['error_message'] = 'Nemate dozvolu za pristup ovoj stranici.';
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=login');
            exit;
        }
    }

    public function index() {
        $this->checkAdmin();
        if (!defined('PAGE_TITLE')) { define('PAGE_TITLE', 'Upravljanje korisnicima'); }
        $korisnici = $this->userModel->getAllUsers();
        return ['korisnici' => $korisnici];
    }

    public function create() {
        $this->checkAdmin();
        return [];
    }

    public function store() {
        $this->checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { /* ... rukovanje greškom ... */ exit; }

        $data = [
            'korisnicko_ime' => trim($_POST['korisnicko_ime'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'ime' => trim($_POST['ime'] ?? ''),
            'prezime' => trim($_POST['prezime'] ?? ''),
            'lozinka' => $_POST['lozinka'] ?? '',
            'potvrda_lozinke' => $_POST['potvrda_lozinke'] ?? '',
            'uloga' => $_POST['uloga'] ?? 'ostali',
            'aktivan' => $_POST['aktivan'] ?? 0,
        ];
        
        $errors = [];
        if (empty($data['korisnicko_ime'])) { $errors[] = 'Korisničko ime je obavezno.'; }
        if ($this->userModel->isUsernameTaken($data['korisnicko_ime'])) { $errors[] = 'Korisničko ime je već zauzeto.'; }
        if (empty($data['email'])) { $errors[] = 'Email je obavezan.'; }
        elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) { $errors[] = 'Email adresa nije validna.'; }
        elseif ($this->userModel->isEmailTaken($data['email'])) { $errors[] = 'Email adresa je već u upotrebi.'; }
        if (empty($data['lozinka'])) { $errors[] = 'Lozinka je obavezna.'; }
        elseif (strlen($data['lozinka']) < 6) { $errors[] = 'Lozinka mora imati najmanje 6 karaktera.'; }
        if ($data['lozinka'] !== $data['potvrda_lozinke']) { $errors[] = 'Lozinke se ne podudaraju.'; }
        if (!in_array($data['uloga'], ['administrator', 'kontrolor', 'ostali'])) { $errors[] = 'Izabrana uloga nije validna.'; }

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $data;
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=admin_user_create');
            exit;
        }
        
        $data['lozinka_hash'] = password_hash($data['lozinka'], PASSWORD_DEFAULT);
        
        if ($this->userModel->createUser($data)) {
            $_SESSION['success_message'] = 'Korisnik "' . htmlspecialchars($data['korisnicko_ime']) . '" je uspešno kreiran.';
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=admin_users');
        } else {
            $_SESSION['form_errors'] = ['Došlo je do greške prilikom upisa u bazu.'];
            $_SESSION['form_data'] = $data;
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=admin_user_create');
        }
        exit;
    }

    public function edit($id) {
        $this->checkAdmin();
        $korisnik = $this->userModel->getUserById($id);
        if (!$korisnik) {
            $_SESSION['error_message'] = 'Korisnik nije pronađen.';
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=admin_users');
            exit;
        }
        return ['korisnik' => $korisnik];
    }
    
    public function update($id) {
        $this->checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { /* ... rukovanje greškom ... */ exit; }

        $data = [
            'korisnicko_ime' => trim($_POST['korisnicko_ime'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'ime' => trim($_POST['ime'] ?? ''),
            'prezime' => trim($_POST['prezime'] ?? ''),
            'lozinka' => $_POST['lozinka'] ?? '',
            'potvrda_lozinke' => $_POST['potvrda_lozinke'] ?? '',
            'uloga' => $_POST['uloga'] ?? 'ostali',
            'aktivan' => $_POST['aktivan'] ?? 0,
        ];
        
        $errors = [];
        if (empty($data['korisnicko_ime'])) { $errors[] = 'Korisničko ime je obavezno.'; }
        if ($this->userModel->isUsernameTaken($data['korisnicko_ime'], $id)) { $errors[] = 'Korisničko ime je već zauzeto.'; }
        if (empty($data['email'])) { $errors[] = 'Email je obavezan.'; }
        elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) { $errors[] = 'Email adresa nije validna.'; }
        elseif ($this->userModel->isEmailTaken($data['email'], $id)) { $errors[] = 'Email adresa je već u upotrebi.'; }
        if (!empty($data['lozinka']) && strlen($data['lozinka']) < 6) { $errors[] = 'Ako unosite novu lozinku, mora imati najmanje 6 karaktera.'; }
        if ($data['lozinka'] !== $data['potvrda_lozinke']) { $errors[] = 'Lozinke se ne podudaraju.'; }
        if (!in_array($data['uloga'], ['administrator', 'kontrolor', 'ostali'])) { $errors[] = 'Izabrana uloga nije validna.'; }
        
        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $data;
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=admin_user_edit&id=' . $id);
            exit;
        }
        
        $data['lozinka_hash'] = !empty($data['lozinka']) ? password_hash($data['lozinka'], PASSWORD_DEFAULT) : null;
        
        if ($this->userModel->updateUser($id, $data)) {
            $_SESSION['success_message'] = 'Korisnik "' . htmlspecialchars($data['korisnicko_ime']) . '" je uspešno ažuriran.';
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=admin_users');
        } else {
            $_SESSION['form_errors'] = ['Došlo je do greške prilikom ažuriranja baze.'];
            $_SESSION['form_data'] = $data;
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=admin_user_edit&id=' . $id);
        }
        exit;
    }

    public function delete($id) {
        $this->checkAdmin();
        // Ne dozvoliti brisanje sopstvenog naloga
        if ($id == $_SESSION['user_id']) {
            $_SESSION['error_message'] = 'Nije moguće obrisati sopstveni nalog.';
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=admin_users');
            exit;
        }
        
        if ($this->userModel->deleteUser($id)) {
            $_SESSION['success_message'] = 'Korisnik je uspešno obrisan.';
        } else {
            $_SESSION['error_message'] = 'Došlo je do greške prilikom brisanja korisnika. Moguće je da je korisnik povezan sa drugim podacima u sistemu.';
        }
        header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=admin_users');
        exit;
    }
    /**
     * Prikazuje formu za promenu lozinke.
     */
    public function showChangePasswordForm() {
        if (session_status() == PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['logged_in'])) {
             header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=login'); exit;
        }
        if (!defined('PAGE_TITLE')) { define('PAGE_TITLE', 'Promena Lozinke'); }
        return [];
    }

    /**
     * Obrađuje zahtev za promenu lozinke.
     */
    public function processChangePassword() {
        if (session_status() == PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['logged_in'])) { exit('Neovlašćen pristup.'); }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { exit('Neispravan zahtev.'); }

        $userId = $_SESSION['user_id'];
        $staraLozinka = $_POST['stara_lozinka'] ?? '';
        $novaLozinka = $_POST['nova_lozinka'] ?? '';
        $potvrdaNoveLozinke = $_POST['potvrda_nove_lozinke'] ?? '';
        
        $errors = [];
        $user = $this->userModel->getUserById($userId);

        if (!$user || !password_verify($staraLozinka, $user['lozinka_hash'])) {
            $errors[] = 'Stara lozinka nije ispravna.';
        }
        if (empty($novaLozinka) || strlen($novaLozinka) < 6) {
            $errors[] = 'Nova lozinka mora imati najmanje 6 karaktera.';
        }
        if ($novaLozinka !== $potvrdaNoveLozinke) {
            $errors[] = 'Nove lozinke se ne podudaraju.';
        }

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=promena_sifre');
            exit;
        }

        $newPasswordHash = password_hash($novaLozinka, PASSWORD_DEFAULT);

        if ($this->userModel->updatePassword($userId, $newPasswordHash)) {
            $_SESSION['success_message'] = 'Lozinka je uspešno promenjena.';
            // Preusmeri na odgovarajući dashboard
            $user_role = $_SESSION['user_uloga'] ?? 'ostali';
            $dashboard_page = ($user_role === 'administrator') ? 'admin_dashboard' : (($user_role === 'kontrolor') ? 'kontrolor_dashboard' : 'ostali_dashboard');
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=' . $dashboard_page);
        } else {
            $_SESSION['form_errors'] = ['Došlo je do greške prilikom ažuriranja lozinke.'];
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=promena_sifre');
        }
        exit;
    }
}
?>