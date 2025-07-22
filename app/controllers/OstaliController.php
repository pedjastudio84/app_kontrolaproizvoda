<?php
require_once __DIR__ . '/../models/Evidencija.php';

class OstaliController {
    private $db;
    private $evidencijaModel;

    public function __construct(PDO $dbConnection) {
        $this->db = $dbConnection;
        $this->evidencijaModel = new Evidencija($this->db);
    }

    public function dashboard() {
        if (!defined('PAGE_TITLE')) {
            define('PAGE_TITLE', 'Korisnička tabla');
        }

        // Prikupljanje statistike
        $stats = [
            'records_today' => $this->evidencijaModel->countTodayRecords(),
            'records_this_month' => $this->evidencijaModel->countThisMonthRecords()
        ];

        return ['stats' => $stats];
    }
}
?>