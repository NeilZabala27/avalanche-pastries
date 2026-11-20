<?php
class CustomerRepository extends Repository
{
    public function findAll(): array
    {
        return $this->search('');
    }

    // Customers whose name or contact number contains $term, with how many orders each has.
    public function search(string $term): array
    {
        $like = '%' . $term . '%';
        $statement = $this->pdo->prepare(
            "SELECT c.customer_id, c.customer_name, c.address, c.contact_number,
                    COUNT(o.order_id) AS order_count
             FROM customer c
             LEFT JOIN customer_order o ON o.customer_id = c.customer_id
             WHERE c.customer_name LIKE ? OR c.contact_number LIKE ?
             GROUP BY c.customer_id, c.customer_name, c.address, c.contact_number
             ORDER BY c.customer_name");
        $statement->execute([$like, $like]);
        return array_map([Customer::class, 'fromRow'], $statement->fetchAll(PDO::FETCH_ASSOC));
    }

    public function findById(int $id): ?Customer
    {
        $statement = $this->pdo->prepare(
            "SELECT customer_id, customer_name, address, contact_number FROM customer WHERE customer_id = ?");
        $statement->execute([$id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row ? Customer::fromRow($row) : null;
    }

    // Inserts a new customer (and gives it its new id) or updates an existing one.
    public function save(Customer $customer): void
    {
        if ($customer->getId() !== null) {
            $statement = $this->pdo->prepare(
                "UPDATE customer SET customer_name = ?, address = ?, contact_number = ? WHERE customer_id = ?");
            $statement->execute([$customer->getName(), $customer->getAddress(),
                                 $customer->getContactNumber(), $customer->getId()]);
            return;
        }
        $statement = $this->pdo->prepare(
            "INSERT INTO customer (customer_name, address, contact_number) VALUES (?, ?, ?)");
        $statement->execute([$customer->getName(), $customer->getAddress(), $customer->getContactNumber()]);
        $customer->setId((int)$this->pdo->lastInsertId());
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare("SELECT COUNT(*) FROM customer_order WHERE customer_id = ?");
        $statement->execute([$id]);
        if ($statement->fetchColumn() > 0) {
            throw new DomainException('This customer has orders so it cannot be deleted.');
        }
        $this->pdo->prepare("DELETE FROM customer WHERE customer_id = ?")->execute([$id]);
    }
}
