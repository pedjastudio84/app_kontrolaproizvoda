<?php

// Definišite konstante za parametre konekcije sa bazom podataka
// Prilagodite ove vrednosti vašem lokalnom MySQL okruženju
define('DB_HOST', 'localhost');         // Ili IP adresa vašeg MySQL servera, npr. 127.0.0.1
define('DB_USER', 'root');             // Vaše MySQL korisničko ime
define('DB_PASS', '');                 // Vaša MySQL lozinka (ako je imate, npr. 'your_password')
define('DB_NAME', 'kontrolaproizvoda_db'); // Naziv baze podataka koju ste kreirali

// Opcije za PDO konekciju
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Prikazuj greške kao izuzetke
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Vraćaj redove kao asocijativne nizove
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Koristi prave prepared statements
];

/**
 * Funkcija za uspostavljanje konekcije sa bazom podataka.
 *
 * @return PDO|null PDO objekat konekcije ili null u slučaju neuspeha.
 */
function getDbConnection() {
    global $options; // Učinimo $options dostupnim unutar funkcije

    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // U realnoj aplikaciji, ovde biste možda logovali grešku umesto da je direktno prikazujete.
        // Za sada, radi jednostavnosti, prikazujemo je.
        // Možete privremeno isključiti prikazivanje greške u produkciji.
        error_log("Greska pri konekciji sa bazom: " . $e->getMessage()); // Loguj grešku
        // die("Greška pri konekciji sa bazom: " . $e->getMessage()); // Prekida izvršavanje i prikazuje grešku
        // U produkciji biste želeli da prikažete generičku poruku korisniku
        die("Došlo je do greške u sistemu. Molimo pokušajte kasnije.");
        return null; // Nikada se neće izvršiti ako je die() aktivan
    }
}

// Primer kako možete testirati konekciju (opciono, obrišite ili zakomentarišite posle testiranja)
/*
$db = getDbConnection();
if ($db) {
    echo "Uspešno uspostavljena konekcija sa bazom podataka!";
} else {
    echo "Neuspešna konekcija sa bazom podataka.";
}
*/

?>