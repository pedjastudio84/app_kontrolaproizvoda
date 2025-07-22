<?php

// Uključujemo User model kako bismo mogli da ga koristimo
require_once __DIR__ . '/../models/User.php';

class AuthController {
    private $db; // Za PDO konekciju
    private $userModel; // Za User model

    /**
     * Konstruktor koji prima PDO konekciju i inicijalizuje User model.
     * @param PDO $dbConnection PDO objekat konekcije sa bazom.
     */
    public function __construct(PDO $dbConnection) {
        $this->db = $dbConnection;
        $this->userModel = new User($this->db); // Kreiramo instancu User modela
    }

    /**
     * Prikazuje login formu.
     * U trenutnoj postavci, index.php direktno bira view za '?page=login'.
     * Ova metoda bi se koristila u naprednijem ruteru.
     */
    public function showLoginForm() {
        if (!defined('PAGE_TITLE')) {
            define('PAGE_TITLE', 'Prijava korisnika');
        }
        // U trenutnoj arhitekturi, index.php je odgovoran za učitavanje
        // $view_file_path = VIEWS_PATH . '/auth/login.php';
        // i zatim glavnog layout-a. Kontroler samo priprema podatke.
    }

    /**
     * Obrađuje podatke poslate sa login forme.
     * Vrši validaciju, proveru korisnika u bazi, postavlja sesiju i preusmerava.
     */
    public function handleLogin() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            // Ako nije POST, preusmeri na login (mera predostrožnosti)
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=login');
            exit;
        }

        $korisnicko_ime = $_POST['korisnicko_ime'] ?? '';
        $lozinka = $_POST['lozinka'] ?? '';

        // Osnovna validacija
        if (empty($korisnicko_ime) || empty($lozinka)) {
            $_SESSION['error_message'] = 'Korisničko ime i lozinka su obavezni.';
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=login');
            exit;
        }

        // Pronađi korisnika
        $user = $this->userModel->findByUsername($korisnicko_ime);

        // Proveri korisnika i lozinku
        if ($user && password_verify($lozinka, $user['lozinka_hash'])) {
            // Uspešna prijava: postavi sesiju
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_ime'] = $user['ime'];
            $_SESSION['user_prezime'] = $user['prezime'];
            $_SESSION['user_korisnicko_ime'] = $user['korisnicko_ime'];
            $_SESSION['user_uloga'] = $user['uloga'];
            $_SESSION['logged_in'] = true;

            $_SESSION['success_message'] = 'Uspešno ste se prijavili, ' . htmlspecialchars($user['ime']) . '!';

            // Preusmeravanje na odgovarajući dashboard
            $redirect_page = 'login'; // Fallback
            switch ($user['uloga']) {
                case 'administrator':
                    $redirect_page = 'admin_dashboard';
                    break;
                case 'kontrolor':
                    $redirect_page = 'kontrolor_dashboard';
                    break;
                case 'ostali':
                    $redirect_page = 'ostali_dashboard';
                    break;
                default:
                    $redirect_page = 'ostali_dashboard'; // Ili login ako je uloga nepoznata
                    break;
            }
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=' . $redirect_page);
            exit;

        } else {
            // Neuspešna prijava
            $_SESSION['error_message'] = 'Pogrešno korisničko ime ili lozinka.';
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=login');
            exit;
        }
    }

    /**
     * Obrađuje odjavu korisnika.
     * Uništava sesiju i preusmerava na login stranicu.
     */
    public function handleLogout() {
        // Pokreni sesiju ako već nije (važno za session_destroy)
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Obriši sve promenljive iz sesije
        $_SESSION = array();

        // Ako se koriste kolačići za sesiju, obriši i njih
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Na kraju, uništi sesiju
        session_destroy();

        // Preusmeri na login stranicu sa statusom da bi index.php mogao da postavi poruku
        header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=login&status=logged_out');
        exit;
    }
} // Kraj klase AuthController

?>