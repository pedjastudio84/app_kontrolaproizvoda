<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/PlanKontrole.php';
require_once __DIR__ . '/../models/Evidencija.php';

class AdminController {
    private $db;
    private $userModel;
    private $planKontroleModel;
    private $evidencijaModel;

    public function __construct(PDO $dbConnection) {
        $this->db = $dbConnection;
        $this->userModel = new User($this->db);
        $this->planKontroleModel = new PlanKontrole($this->db);
        $this->evidencijaModel = new Evidencija($this->db);
    }

    public function dashboard() {
        if (!defined('PAGE_TITLE')) {
            define('PAGE_TITLE', 'Admin - Kontrolna tabla');
        }

        // Prikupljanje statistike i najnovijih unosa
        $stats = [
            'total_users' => $this->userModel->getTotalCount(),
            'total_plans' => $this->planKontroleModel->getTotalCount(),
            'total_records' => $this->evidencijaModel->getTotalRecordCount(),
            'records_today' => $this->evidencijaModel->countTodayRecords(),
            'records_this_month' => $this->evidencijaModel->countThisMonthRecords()
        ];
        
        $latest_records = $this->evidencijaModel->getLatestRecords(5);
        $latest_plans = $this->planKontroleModel->getLatestPlans(5);

        return [
            'stats' => $stats,
            'latest_records' => $latest_records,
            'latest_plans' => $latest_plans
        ];
    }
}
?>