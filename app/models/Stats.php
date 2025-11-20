<?php
require_once 'src/model.php';

class Stats extends Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getPublicMetrics(): array
    {
        $metrics = [
            'menu_items' => $this->fetchScalar('SELECT COUNT(*) FROM menu_items'),
            'orders_completed' => $this->fetchScalar("SELECT COUNT(*) FROM orders WHERE status IN ('served','completed')"),
            'restaurants_active' => $this->fetchScalar('SELECT COUNT(*) FROM restaurants WHERE is_active = 1'),
            'tables_online' => $this->fetchScalar('SELECT COUNT(*) FROM restaurant_tables'),
            'today_orders' => $this->fetchScalar('SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()'),
            'waiter_calls_today' => $this->fetchScalar('SELECT COUNT(*) FROM waiter_calls WHERE DATE(created_at) = CURDATE()')
        ];

        $metrics['avg_order_value'] = $this->fetchScalar("SELECT COALESCE(AVG(total_amount),0) FROM orders WHERE status IN ('served','completed')");

        return $metrics;
    }

    private function fetchScalar(string $query)
    {
        try {
            $stmt = $this->db->query($query);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('Stats query failed: ' . $e->getMessage());
            return 0;
        }
    }
}

