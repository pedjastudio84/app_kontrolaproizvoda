<?php

class User {
    private $db; // Za PDO konekciju

    /**
     * Konstruktor koji prima PDO konekciju.
     * @param PDO $db PDO objekat konekcije sa bazom.
     */
    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Pronalazi korisnika po korisničkom imenu (za login).
     * @param string $username Korisničko ime.
     * @return array|false Niz sa podacima o korisniku ako je pronađen i aktivan, inače false.
     */
    public function findByUsername($username) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM korisnici WHERE korisnicko_ime = :username AND aktivan = TRUE");
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Greška u User::findByUsername: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Dohvata sve korisnike iz baze podataka.
     * @return array Niz sa svim korisnicima ili prazan niz ako nema korisnika/dođe do greške.
     */
    public function getAllUsers() {
        try {
            $stmt = $this->db->query("SELECT id, korisnicko_ime, ime, prezime, email, uloga, aktivan, kreiran_datuma FROM korisnici ORDER BY id ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Greška u User::getAllUsers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Pronalazi korisnika po ID-u.
     * @param int $id ID korisnika.
     * @return array|false Niz sa podacima o korisniku ili false.
     */
    public function getUserById($id) {
        $stmt = $this->db->prepare("SELECT * FROM korisnici WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Proverava da li korisničko ime već postoji u bazi, opciono ignorišući dati ID.
     * @param string $username Korisničko ime za proveru.
     * @param int|null $excludeId ID korisnika koji se ignoriše (koristi se kod izmene).
     * @return bool True ako postoji, false ako ne postoji.
     */
    public function isUsernameTaken($username, $excludeId = null) {
        $sql = "SELECT id FROM korisnici WHERE korisnicko_ime = :username";
        if ($excludeId !== null) {
            $sql .= " AND id != :id";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':username', $username);
        if ($excludeId !== null) {
            $stmt->bindParam(':id', $excludeId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetch() !== false;
    }

    /**
     * Proverava da li email već postoji u bazi, opciono ignorišući dati ID.
     * @param string $email Email za proveru.
     * @param int|null $excludeId ID korisnika koji se ignoriše.
     * @return bool True ako postoji, false ako ne postoji.
     */
    public function isEmailTaken($email, $excludeId = null) {
        $sql = "SELECT id FROM korisnici WHERE email = :email";
        if ($excludeId !== null) {
            $sql .= " AND id != :id";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':email', $email);
        if ($excludeId !== null) {
            $stmt->bindParam(':id', $excludeId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetch() !== false;
    }

    /**
     * Kreira novog korisnika u bazi podataka.
     * @param array $data Asocijativni niz sa podacima o korisniku.
     * @return bool True ako je korisnik uspešno kreiran, inače false.
     */
    public function createUser($data) {
        $sql = "INSERT INTO korisnici (korisnicko_ime, email, ime, prezime, lozinka_hash, uloga, aktivan)
                VALUES (:korisnicko_ime, :email, :ime, :prezime, :lozinka_hash, :uloga, :aktivan)";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':korisnicko_ime', $data['korisnicko_ime']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':ime', $data['ime']);
            $stmt->bindParam(':prezime', $data['prezime']);
            $stmt->bindParam(':lozinka_hash', $data['lozinka_hash']);
            $stmt->bindParam(':uloga', $data['uloga']);
            $stmt->bindParam(':aktivan', $data['aktivan'], PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Greška u User::createUser: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ažurira postojećeg korisnika u bazi podataka.
     * @param int $id ID korisnika.
     * @param array $data Podaci za ažuriranje.
     * @return bool True u slučaju uspeha.
     */
    public function updateUser($id, $data) {
        $passwordSql = !empty($data['lozinka_hash']) ? ", lozinka_hash = :lozinka_hash" : "";

        $sql = "UPDATE korisnici SET 
                    korisnicko_ime = :korisnicko_ime, 
                    email = :email, 
                    ime = :ime, 
                    prezime = :prezime, 
                    uloga = :uloga, 
                    aktivan = :aktivan 
                    $passwordSql
                WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':korisnicko_ime', $data['korisnicko_ime']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':ime', $data['ime']);
            $stmt->bindParam(':prezime', $data['prezime']);
            $stmt->bindParam(':uloga', $data['uloga']);
            $stmt->bindParam(':aktivan', $data['aktivan'], PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            if (!empty($data['lozinka_hash'])) {
                $stmt->bindParam(':lozinka_hash', $data['lozinka_hash']);
            }
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Greška u User::updateUser: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Briše korisnika iz baze podataka.
     * @param int $id ID korisnika.
     * @return bool True u slučaju uspeha.
     */
    public function deleteUser($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM korisnici WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Greška u User::deleteUser: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ažurira lozinku za datog korisnika.
     * @param int $id ID korisnika.
     * @param string $newPasswordHash Novi heširani password.
     * @return bool True u slučaju uspeha, false u slučaju neuspeha.
     */
    public function updatePassword($id, $newPasswordHash) {
        $sql = "UPDATE korisnici SET lozinka_hash = :lozinka_hash WHERE id = :id";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':lozinka_hash', $newPasswordHash);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Greška u User::updatePassword: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Dohvata ukupan broj svih korisnika.
     * @return int
     */
    public function getTotalCount() {
        try {
            return (int) $this->db->query("SELECT COUNT(id) FROM korisnici")->fetchColumn();
        } catch (PDOException $e) {
            error_log("Greška u User::getTotalCount: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Dohvata sve korisnike koji imaju ulogu 'kontrolor'.
     * @return array Niz korisnika sa ulogom kontrolor.
     */
    public function getControllers() {
        $sql = "SELECT id, ime, prezime FROM korisnici WHERE uloga = 'kontrolor' ORDER BY prezime, ime";
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Greška u User::getControllers: " . $e->getMessage());
            return [];
        }
    }
}
?>