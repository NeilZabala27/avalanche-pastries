<?php
class OrderCountReport extends Report
{
    protected function getTitle(): string { return "Number of Orders per Month"; }
    protected function getHeaders(): array { return ["Month", "Number of Orders"]; }

    protected function getRows(): array
    {
        $data = $this->pdo->query("SELECT DATE_FORMAT(order_date, '%Y-%m') AS month,
                COUNT(*) AS num_orders
            FROM customer_order
            GROUP BY DATE_FORMAT(order_date, '%Y-%m')
            ORDER BY month DESC")->fetchAll();

        $rows = [];
        foreach ($data as $d) {
            $rows[] = [$d['month'], $d['num_orders']];
        }
        return $rows;
    }
}
