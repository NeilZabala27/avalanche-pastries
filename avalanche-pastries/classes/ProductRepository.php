<?php
class ProductRepository extends Repository
{
    private const COLUMNS = "product_id, product_name, flavor, size, quality_level, price";

    public function findAll(): array
    {
        return $this->search('');
    }

    // Products whose name or flavor contains $term.
    public function search(string $term): array
    {
        $like = '%' . $term . '%';
        $statement = $this->pdo->prepare(
            "SELECT " . self::COLUMNS . " FROM product
             WHERE product_name LIKE ? OR flavor LIKE ?
             ORDER BY product_name, size");
        $statement->execute([$like, $like]);
        return array_map([Product::class, 'fromRow'], $statement->fetchAll(PDO::FETCH_ASSOC));
    }

    public function findById(int $id): ?Product
    {
        $statement = $this->pdo->prepare("SELECT " . self::COLUMNS . " FROM product WHERE product_id = ?");
        $statement->execute([$id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row ? Product::fromRow($row) : null;
    }

    // Inserts a new product (and gives it its new id) or updates an existing one.
    public function save(Product $product): void
    {
        $check = $this->pdo->prepare(
            "SELECT COUNT(*) FROM product
             WHERE LOWER(product_name) = LOWER(?) AND COALESCE(flavor, '') = ? AND COALESCE(size, '') = ?
               AND product_id <> ?");
        $check->execute([$product->getName(), $product->getFlavor(), $product->getSize(), $product->getId() ?? 0]);
        if ((int)$check->fetchColumn() > 0) {
            throw new DomainException('A product with the same name, flavor and size already exists.');
        }

        $values = [
            $product->getName(),
            $product->getFlavor() !== '' ? $product->getFlavor() : null,
            $product->getSize() !== '' ? $product->getSize() : null,
            $product->getQualityLevel() !== '' ? $product->getQualityLevel() : null,
            $product->getPrice(),
        ];

        if ($product->getId() !== null) {
            $statement = $this->pdo->prepare(
                "UPDATE product SET product_name = ?, flavor = ?, size = ?, quality_level = ?, price = ?
                 WHERE product_id = ?");
            $statement->execute([...$values, $product->getId()]);
            return;
        }
        $statement = $this->pdo->prepare(
            "INSERT INTO product (product_name, flavor, size, quality_level, price) VALUES (?, ?, ?, ?, ?)");
        $statement->execute($values);
        $product->setId((int)$this->pdo->lastInsertId());
    }

    // A product that is in an order cannot be deleted.
    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare("SELECT COUNT(*) FROM order_item WHERE product_id = ?");
        $statement->execute([$id]);
        if ((int)$statement->fetchColumn() > 0) {
            throw new DomainException('This product is in orders, so it cannot be deleted.');
        }
        $this->pdo->prepare("DELETE FROM product WHERE product_id = ?")->execute([$id]);
    }
}
