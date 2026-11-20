<?php
class BestSellingReport extends Report
{
    protected function getTitle(): string { return "Best-Selling Products"; }
    protected function getHeaders(): array { return ["Rank", "Product", "Quantity Sold", "Revenue"]; }

    protected function getRows(): array
    {
        $data = $this->pdo->query("SELECT pr.product_name, pr.size,
                SUM(oi.quantity) AS qty_sold,
                SUM(oi.quantity * pr.price) AS revenue
            FROM order_item oi
            JOIN product pr ON pr.product_id = oi.product_id
            GROUP BY pr.product_id, pr.product_name, pr.size
            ORDER BY qty_sold DESC
            LIMIT 10")->fetchAll();

        $rows = [];
        foreach ($data as $i => $d) {
            $name = $d['product_name'] . ($d['size'] ? ' (' . $d['size'] . ')' : '');
            $rows[] = [$i + 1, $name, $d['qty_sold'], '₱' . number_format((float)$d['revenue'], 2)];
        }
        return $rows;
    }
}
