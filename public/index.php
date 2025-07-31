<?php

// --- POČETAK IZMENE: Forsiranje HTTPS protokola ---
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    $location = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $location);
    exit;
}
// --- KRAJ IZMENE ---

// Forsiranje prikazivanja SVIH grešaka (korisno za razvoj)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Uključivanje osnovnih konfiguracionih fajlova i kontrolera
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/UserController.php';
require_once __DIR__ . '/../app/controllers/PlanKontroleController.php';
require_once __DIR__ . '/../app/controllers/EvidencijaController.php';
require_once __DIR__ . '/../app/controllers/ReportController.php';
require_once __DIR__ . '/../app/controllers/AdminController.php';
require_once __DIR__ . '/../app/controllers/KontrolorController.php';
require_once __DIR__ . '/../app/controllers/OstaliController.php';

// Pokretanje sesije
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Definicija putanje do view-ova
define('VIEWS_PATH', __DIR__ . '/../app/views');

// --- Instanciranje kontrolera ---
$dbConnection = getDbConnection();
$authController = new AuthController($dbConnection);
$userController = new UserController($dbConnection);
$planKontroleController = new PlanKontroleController($dbConnection);
$evidencijaController = new EvidencijaController($dbConnection);
$reportController = new ReportController($dbConnection);
$adminController = new AdminController($dbConnection);
$kontrolorController = new KontrolorController($dbConnection);
$ostaliController = new OstaliController($dbConnection);


// --- Rutiranje ---
$action = $_POST['action'] ?? ($_GET['action'] ?? null);
$page = $_GET['page'] ?? null;
$id = $_GET['id'] ?? ($_POST['id'] ?? null);

// 1. Obrada POST akcija
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'login': $authController->handleLogin(); exit;
        case 'promena_sifre': $userController->processChangePassword(); exit;
        case 'admin_user_store': $userController->store(); exit;
        case 'admin_user_update': $userController->update($id); exit;
        case 'admin_plan_store': $planKontroleController->store(); exit;
        case 'admin_plan_update': $planKontroleController->update($id); exit;
        case 'evidencija_store': $evidencijaController->store(); exit;
        case 'evidencija_update': $evidencijaController->update($id); exit;
        case 'generate_report': $reportController->generateReport(); exit;
    }
}

// 2. Obrada GET akcija
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    switch ($action) {
        case 'logout': $authController->handleLogout(); exit;
        case 'admin_user_delete': $userController->delete($id); exit;
        case 'admin_plan_delete': $planKontroleController->delete($id); exit;
        case 'get_plan_details': $planKontroleController->getPlanForAjax(); exit;
        case 'evidencija_delete': $evidencijaController->delete($id); exit;
        case 'generate_single_report': $reportController->generateSingleReport($id); exit;
        case 'generate_plan_pdf': $reportController->generatePlanReport($id); exit;
    }
}

// 3. Određivanje stranice za prikaz ako nije zadata
if ($page === null) {
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        $user_role = $_SESSION['user_uloga'] ?? 'ostali';
        if ($user_role === 'administrator') $page = 'admin_dashboard';
        elseif ($user_role === 'kontrolor') $page = 'kontrolor_dashboard';
        else $page = 'ostali_dashboard';
    } else {
        $page = 'login';
    }
}

// Inicijalizacija i autorizacija
$view_file_path = '';
$data_for_view = [];
$redirect_url_on_auth_fail = rtrim(APP_URL, '/') . '/public/index.php?page=login';
function isAdminPage($pageName) { return strpos((string)$pageName, 'admin_') === 0; }
if (isAdminPage($page)) {
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || $_SESSION['user_uloga'] !== 'administrator') {
        $_SESSION['error_message'] = 'Nemate dozvolu za pristup ovoj stranici.';
        header('Location: ' . $redirect_url_on_auth_fail);
        exit;
    }
}

// 4. Glavni switch za prikazivanje stranica
switch ($page) {
    case 'login':
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            $user_role = $_SESSION['user_uloga'] ?? 'ostali';
            $dashboard_page = ($user_role === 'administrator') ? 'admin_dashboard' : (($user_role === 'kontrolor') ? 'kontrolor_dashboard' : 'ostali_dashboard');
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=' . $dashboard_page);
            exit;
        }
        $view_file_path = VIEWS_PATH . '/auth/login.php';
        if (!defined('PAGE_TITLE')) { define('PAGE_TITLE', 'Prijava korisnika'); }
        if (isset($_GET['status']) && $_GET['status'] === 'logged_out' && !isset($_SESSION['success_message'])) { $_SESSION['success_message'] = 'Uspešno ste se odjavili.'; }
        break;
    case 'promena_sifre':
        if (!isset($_SESSION['logged_in'])) { header('Location: ' . $redirect_url_on_auth_fail); exit; }
        $data_for_view = $userController->showChangePasswordForm();
        $view_file_path = VIEWS_PATH . '/auth/promena_sifre.php';
        break;
        
    case 'admin_dashboard':
        $data_for_view = $adminController->dashboard();
        $view_file_path = VIEWS_PATH . '/admin/dashboard.php';
        break;
    case 'kontrolor_dashboard':
        if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['user_uloga'], ['kontrolor', 'administrator'])) { $_SESSION['error_message'] = 'Nemate dozvolu.'; header('Location: ' . $redirect_url_on_auth_fail); exit; }
        if ($_SESSION['user_uloga'] === 'administrator') { header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=admin_dashboard'); exit; }
        $data_for_view = $kontrolorController->dashboard();
        $view_file_path = VIEWS_PATH . '/kontrolor/dashboard.php';
        break;
    case 'ostali_dashboard':
        if (!isset($_SESSION['logged_in'])) { $_SESSION['error_message'] = 'Morate biti prijavljeni.'; header('Location: ' . $redirect_url_on_auth_fail); exit; }
        if ($_SESSION['user_uloga'] === 'administrator') { header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=admin_dashboard'); exit; }
        if ($_SESSION['user_uloga'] === 'kontrolor') { header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=kontrolor_dashboard'); exit; }
        $data_for_view = $ostaliController->dashboard();
        $view_file_path = VIEWS_PATH . '/ostali/dashboard.php';
        break;
    
    // Rute...
    case 'admin_users': $data_for_view = $userController->index(); $view_file_path = VIEWS_PATH . '/admin/korisnici/list.php'; break;
    case 'admin_user_create': $data_for_view = $userController->create(); $view_file_path = VIEWS_PATH . '/admin/korisnici/form.php'; break;
    case 'admin_user_edit': $data_for_view = $userController->edit($id); $view_file_path = VIEWS_PATH . '/admin/korisnici/form.php'; break;
    case 'admin_plans': $data_for_view = $planKontroleController->index(); $view_file_path = VIEWS_PATH . '/admin/planovi_kontrole/list.php'; break;
    case 'admin_plan_create': $data_for_view = $planKontroleController->create(); $view_file_path = VIEWS_PATH . '/admin/planovi_kontrole/form.php'; break;
    case 'admin_plan_edit': $data_for_view = $planKontroleController->edit($id); $view_file_path = VIEWS_PATH . '/admin/planovi_kontrole/form.php'; break;
    case 'admin_plan_copy': $data_for_view = $planKontroleController->copy($id); $view_file_path = VIEWS_PATH . '/admin/planovi_kontrole/form.php'; break;
    case 'admin_plan_show': $data_for_view = $planKontroleController->show($id); $view_file_path = VIEWS_PATH . '/admin/planovi_kontrole/show.php'; break;
    case 'admin_evidencije': $data_for_view = $evidencijaController->listAll(); $view_file_path = VIEWS_PATH . '/admin/evidencije/list.php'; break;
    case 'admin_reports': $data_for_view = $reportController->showReportForm(); $view_file_path = VIEWS_PATH . '/admin/izvestaji/form.php'; break;
    case 'kontrolor_biraj_vrstu': if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['user_uloga'], ['kontrolor', 'administrator'])) { $_SESSION['error_message'] = 'Nemate dozvolu.'; header('Location: ' . $redirect_url_on_auth_fail); exit; } $view_file_path = VIEWS_PATH . '/kontrolor/biraj_vrstu.php'; break;
    case 'kontrolor_novi_zapis': if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['user_uloga'], ['kontrolor', 'administrator'])) { $_SESSION['error_message'] = 'Nemate dozvolu.'; header('Location: ' . $redirect_url_on_auth_fail); exit; } if (!isset($_GET['vrsta']) || !in_array($_GET['vrsta'], ['redovna_kontrola', 'kontrola_pre_isporuke'])) { $_SESSION['error_message'] = 'Nije definisana validna vrsta kontrole.'; header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=kontrolor_biraj_vrstu'); exit; } $data_for_view = $evidencijaController->create(); $view_file_path = VIEWS_PATH . '/kontrolor/evidencija/form.php'; break;
    case 'kontrolor_moji_zapisi': if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['user_uloga'], ['kontrolor', 'administrator'])) { $_SESSION['error_message'] = 'Nemate dozvolu.'; header('Location: ' . $redirect_url_on_auth_fail); exit; } $data_for_view = $evidencijaController->index(); $view_file_path = VIEWS_PATH . '/kontrolor/evidencija/list.php'; break;
    case 'kontrolor_zapis_show': if (!isset($_SESSION['logged_in'])) { $_SESSION['error_message'] = 'Morate biti prijavljeni.'; header('Location: ' . $redirect_url_on_auth_fail); exit; } $data_for_view = $evidencijaController->show($id); $view_file_path = VIEWS_PATH . '/kontrolor/evidencija/show.php'; break;
    case 'kontrolor_zapis_edit': if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['user_uloga'], ['kontrolor', 'administrator'])) { $_SESSION['error_message'] = 'Nemate dozvolu.'; header('Location: ' . $redirect_url_on_auth_fail); exit; } $data_for_view = $evidencijaController->edit($id); $view_file_path = VIEWS_PATH . '/kontrolor/evidencija/form.php'; break;
    case 'pregled_svih_zapisa':
        if (!isset($_SESSION['logged_in'])) { 
            $_SESSION['error_message'] = 'Morate biti prijavljeni.'; 
            header('Location: ' . $redirect_url_on_auth_fail); 
            exit; 
        }
        $data_for_view = $evidencijaController->listAll();
        $view_file_path = VIEWS_PATH . '/ostali/pregled_ispitivanja/list.php';
        break;

        case 'pregled_planova':
        if (!isset($_SESSION['logged_in'])) { /* ... greška ... */ }
        $data_for_view = $planKontroleController->index();
        $view_file_path = VIEWS_PATH . '/ostali/planovi/list.php';
        break;
    
    case 'pregled_plana_detalji':
        if (!isset($_SESSION['logged_in'])) { /* ... greška ... */ }
        $data_for_view = $planKontroleController->show($id);
        $view_file_path = VIEWS_PATH . '/admin/planovi_kontrole/show.php'; // Koristimo isti view
        break;

    default:
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            $user_role = $_SESSION['user_uloga'] ?? 'ostali';
            $dashboard_page = ($user_role === 'administrator') ? 'admin_dashboard' : (($user_role === 'kontrolor') ? 'kontrolor_dashboard' : 'ostali_dashboard');
            $_SESSION['error_message'] = 'Tražena stranica (<code>' . htmlspecialchars($page ?? 'nepoznata') . '</code>) nije pronađena.';
            header('Location: ' . rtrim(APP_URL, '/') . '/public/index.php?page=' . $dashboard_page);
            exit;
        }
        $_SESSION['error_message'] = 'Tražena stranica nije pronađena ili zahteva prijavu.';
        $view_file_path = VIEWS_PATH . '/auth/login.php';
        if (!defined('PAGE_TITLE')) { define('PAGE_TITLE', 'Prijava korisnika'); }
        break;
}

// 6. Ekstrahovanje podataka i učitavanje finalnog prikaza
if (!empty($data_for_view) && is_array($data_for_view)) {
    extract($data_for_view);
}
if (!empty($view_file_path) && file_exists($view_file_path) && is_file($view_file_path)) {
    require_once VIEWS_PATH . '/layouts/main.php';
} else {
    if (!defined('PAGE_TITLE')) { define('PAGE_TITLE', 'Greška Sistema'); }
    echo "<!DOCTYPE html><html><head><title>Greška</title></head><body><h1>Greška Sistema</h1><p>Interna greška: Prikaz fajl nije pronađen.</p><p>Debug info: Putanja <code>" . htmlspecialchars($view_file_path ?? 'Nije definisana') . "</code>; Stranica: <code>" . htmlspecialchars($page ?? 'Nije definisan') . "</code></p></body></html>";
}
?>