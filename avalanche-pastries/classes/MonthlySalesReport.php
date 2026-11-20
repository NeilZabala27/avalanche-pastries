<?php
class MonthlySalesReport extends Report
{
    protected function getTitle(): string { return "Monthly Sales"; }
    protected function getHeaders(): array { return ["Month", "Total Sales", "Amount Collected"]; }

    protected function getRows(): array
    {
        // Collected = the whole total if the payment is Paid, otherwise the down payment only if it was paid.
        $data = $this->pdo->query("SELECT DATE_FORMAT(o.order_date, '%Y-%m') AS month,
                COALESCE(SUM(p.total_amount), 0) AS total_sales,
                COALESCE(SUM(CASE WHEN p.payment_status = 'Paid' THEN p.total_amount
                                  WHEN p.down_payment_status = 'Paid' THEN COALESCE(p.down_payment, 0)
                                  ELSE 0 END), 0) AS collected
            FROM customer_order o
            LEFT JOIN payment p ON p.order_id = o.order_id
            GROUP BY DATE_FORMAT(o.order_date, '%Y-%m')
            ORDER BY month DESC")->fetchAll();

        $rows = [];
        foreach ($data as $d) {
            $rows[] = [$d['month'], '₱' . number_format($d['total_sales'], 2),
                       '₱' . number_format($d['collected'], 2)];
        }
        return $rows;
    }
}
