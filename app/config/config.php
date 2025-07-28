<?php
require_once '../app/helpers/view_helper.php';
// --- POČETAK IZMENE: Dinamičko definisanje URL-a aplikacije ---

// Određujemo da li je protokol http ili https
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";

// Uzimamo host (npr. localhost ili 172.20.10.32)
$host = $_SERVER['HTTP_HOST'];

// Dobijamo putanju do 'public' foldera
$script_path = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));

// Uklanjamo '/public' sa kraja da bismo dobili osnovnu putanju projekta
$base_path = preg_replace('/\/public$/', '', $script_path);

// Sastavljamo pun URL. Ako je base_path koren ('/'), pretvaramo ga u prazan string.
$app_base_url = $protocol . $host . ($base_path == '/' ? '' : $base_path);

// Definišemo konstantu sa dinamički kreiranim URL-om
define('APP_URL', $app_base_url);

// --- KRAJ IZMENE ---

// Naziv sajta/aplikacije
define('SITE_NAME', 'Evidencija Kontrole Proizvoda');

// Definišimo osnovne putanje radi lakšeg snalaženja
define('ROOT_PATH', dirname(__DIR__, 2)); // Vraća nas dva nivoa gore od config foldera (do app_kontrolaproizvoda)
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('CONFIG_PATH', APP_PATH . '/config');
define('CONTROLLERS_PATH', APP_PATH . '/controllers');
define('MODELS_PATH', APP_PATH . '/models');
define('VIEWS_PATH_FROM_CONFIG', APP_PATH . '/views'); // Već smo definisali VIEWS_PATH u index.php, ovo je samo primer
define('HELPERS_PATH', APP_PATH . '/helpers');
define('UPLOADS_PATH', PUBLIC_PATH . '/uploads');


// Podešavanja za prikazivanje grešaka (za razvoj)
// U produkciji, ovo bi trebalo da bude isključeno (0) ili podešeno da loguje greške.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

?>