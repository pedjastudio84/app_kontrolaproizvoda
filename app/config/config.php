<?php
require_once '../app/helpers/view_helper.php';
// Osnovna podešavanja aplikacije

// URL vaše aplikacije (prilagodite ako je potrebno)
// Ako pristupate preko http://localhost/projects/app_kontrolaproizvoda/public/
// onda je APP_URL http://localhost/projects/app_kontrolaproizvoda
// Suffix /public/ će se obično rešavati kroz web server konfiguraciju (kasnije)
// ili će se URL-ovi generisati tako da uključuju /public/ ako je neophodno.
// Za sada, neka bude osnova do public foldera.
define('APP_URL', 'https://192.168.0.2/projects/app_kontrolaproizvoda'); // Prilagodite vašoj putanji

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