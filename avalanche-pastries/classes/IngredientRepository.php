<?php
// Handles every database action for ingredients, so the pages never write SQL for them.
class IngredientRepository {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function findAll() {
        $rows = $this->pdo->query("SELECT * FROM ingredient")->fetchAll();
        $list = [];
        foreach ($rows as $row) {
            $list[] = Ingredient::fromRow($row);
        }
        return $list;
    }

    public function find($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM ingredient WHERE ingredient_id = ?");
        $stmt->execute([(int)$id]);
        $row = $stmt->fetch();
        return $row ? Ingredient::fromRow($row) : null;
    }

    public function nameExists($name, $exceptId = 0) {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM ingredient WHERE LOWER(ingredient_name) = LOWER(?) AND ingredient_id <> ?"
        );
        $stmt->execute([trim((string)$name), (int)$exceptId]);
        return $stmt->fetchColumn() > 0;
    }

    // Adds a new ingredient (no id yet) or updates an existing one
    public function save(Ingredient $ingredient) {
        if ($ingredient->getId() === null) {
            $stmt = $this->pdo->prepare(
                "INSERT INTO ingredient (ingredient_name, stock_quantity, low_stock_level, expiration_date) VALUES (?,?,?,?)"
            );
            $stmt->execute([
                $ingredient->getName(), $ingredient->getStockQuantity(),
                $ingredient->getLowStockLevel(), $ingredient->getExpirationDate()
            ]);
        } else {
            $stmt = $this->pdo->prepare(
                "UPDATE ingredient SET ingredient_name=?, stock_quantity=?, low_stock_level=?, expiration_date=? WHERE ingredient_id=?"
            );
            $stmt->execute([
                $ingredient->getName(), $ingredient->getStockQuantity(),
                $ingredient->getLowStockLevel(), $ingredient->getExpirationDate(),
                $ingredient->getId()
            ]);
        }
    }

    // Throws a PDOException if the ingredient is still used in a wastage or baking record
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM ingredient WHERE ingredient_id = ?");
        $stmt->execute([(int)$id]);
    }
}
