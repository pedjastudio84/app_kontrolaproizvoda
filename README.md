# Aplikacija za kontrolu proizvoda

Jednostavna PHP aplikacija za evidenciju i kontrolu proizvoda.

## Kako pokrenuti projekat

1.  **Klonirajte repozitorijum:**
    ```sh
    git clone [https://github.com/pedjastudio84/app_kontrolaproizvoda.git](https://github.com/pedjastudio84/app_kontrolaproizvoda.git)
    ```

2.  **Kreirajte bazu podataka:**
    * Na vašem web serveru (npr. XAMPP, WAMP) kreirajte novu bazu podataka pod nazivom `kontrolaproizvoda`.
    * Importujte fajl `kontrolaproizvoda.sql` u tu bazu.

3.  **Podesite konekciju:**
    * Napravite kopiju fajla `conn.example.php` i nazovite je `conn.php`.
    * U novom `conn.php` fajlu, unesite vaše lokalne podatke za pristup bazi podataka (username i password).

4.  **Pokrenite aplikaciju:**
    * Otvorite projekat u vašem pretraživaču preko lokalnog servera (npr. `http://localhost/app_kontrolaproizvoda`).

## Korišćene tehnologije
* PHP
* MySQL
* JavaScript (jQuery, Bootstrap)
* CSS
