<?php
require_once __DIR__ . '/../models/Evidencija.php';

class KontrolorController {
    private $db;
    private $evidencijaModel;

    public function __construct(PDO $dbConnection) {
        $this->db = $dbConnection;
        $this->evidencijaModel = new Evidencija($this->db);
    }

    public function dashboard() {
        if (!defined('PAGE_TITLE')) {
            define('PAGE_TITLE', 'Kontrolor - Kontrolna tabla');
        }

        // Prikupljanje statistike
        $stats = [
            'my_total_records' => $this->evidencijaModel->getTotalCountForUser($_SESSION['user_id'])
        ];
        
        return ['stats' => $stats];
    }
}
?>