<?php

class DashboardController extends BaseController {

    public function index(): void {
        $this->requireAuth();
        $db = Database::getInstance();
        $stats = $db->fetch('SELECT * FROM v_dashboard_stats') ?: [];
        $activity = Logger::getRecent(15);
        $this->ok([
            'stats' => $stats,
            'recent_activity' => $activity,
        ]);
    }
}
