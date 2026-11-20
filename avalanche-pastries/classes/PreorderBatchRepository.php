<?php
class PreorderBatchRepository extends Repository
{
    // Newest date first, each with how many orders belong to it.
    public function findAll(): array
    {
        $rows = $this->pdo->query(
            "SELECT p.preorder_id, p.preorder_date, COUNT(o.order_id) AS order_count
             FROM preorder p
             LEFT JOIN customer_order o ON o.preorder_id = p.preorder_id
             GROUP BY p.preorder_id, p.preorder_date
             ORDER BY p.preorder_date DESC")->fetchAll(PDO::FETCH_ASSOC);
        return array_map([PreorderBatch::class, 'fromRow'], $rows);
    }

    public function findById(int $id): ?PreorderBatch
    {
        $statement = $this->pdo->prepare("SELECT preorder_id, preorder_date FROM preorder WHERE preorder_id = ?");
        $statement->execute([$id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row ? PreorderBatch::fromRow($row) : null;
    }

    public function save(PreorderBatch $batch): void
    {
        $check = $this->pdo->prepare("SELECT COUNT(*) FROM preorder WHERE preorder_date = ? AND preorder_id <> ?");
        $check->execute([$batch->getDate(), $batch->getId() ?? 0]);
        if ((int)$check->fetchColumn() > 0) {
            throw new DomainException('That pre-order date already exists.');
        }

        if ($batch->getId() !== null) {
            $this->pdo->prepare("UPDATE preorder SET preorder_date = ? WHERE preorder_id = ?")
                      ->execute([$batch->getDate(), $batch->getId()]);
            return;
        }
        $this->pdo->prepare("INSERT INTO preorder (preorder_date) VALUES (?)")->execute([$batch->getDate()]);
        $batch->setId((int)$this->pdo->lastInsertId());
    }

    // A pre-order date that has orders cannot be deleted.
    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare("SELECT COUNT(*) FROM customer_order WHERE preorder_id = ?");
        $statement->execute([$id]);
        if ((int)$statement->fetchColumn() > 0) {
            throw new DomainException('This pre-order date has orders, so it cannot be deleted.');
        }
        $this->pdo->prepare("DELETE FROM preorder WHERE preorder_id = ?")->execute([$id]);
    }
}
