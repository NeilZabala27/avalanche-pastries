<?php
// INHERITANCE + POLYMORPHISM: BakingRecord also extends StockRecord, but it uses
// many ingredients and saves to two tables. Calling save() works the same way for
// both child classes, but each one does its own part differently.
class BakingRecord extends StockRecord {
    private $used = [];

    public function __construct(PDO $pdo, $ingredientIds, $quantities, $date, $rows) {
        parent::__construct($pdo, $date);
        $ingredientIds = is_array($ingredientIds) ? $ingredientIds : [];
        $quantities = is_array($quantities) ? $quantities : [];

        for ($r = 0; $r < $rows; $r++) {
            $ingId = (int)($ingredientIds[$r] ?? 0);
            $qty = trim((string)($quantities[$r] ?? ""));
            if ($ingId === 0 && $qty === "") continue;
            if ($ingId === 0 || !is_numeric($qty) || $qty <= 0) {
                $this->errors[] = "Row " . ($r + 1) . ": choose an ingredient and a quantity greater than 0.";
                continue;
            }
            // If the same ingredient is chosen twice, the quantities are added
            $this->used[$ingId] = ($this->used[$ingId] ?? 0) + (float)$qty;
        }
    }

    protected function usages() {
        return $this->used;
    }

    protected function insertRecord($usages) {
        $this->pdo->prepare("INSERT INTO baking (baking_date) VALUES (?)")->execute([$this->date]);
        $bakingId = $this->pdo->lastInsertId();
        foreach ($usages as $ingId => $qty) {
            $this->pdo->prepare("INSERT INTO baking_ingredient (baking_id, ingredient_id, quantity_used) VALUES (?,?,?)")
                ->execute([$bakingId, $ingId, $qty]);
        }
    }

    public function successMessage() {
        return "Baking record saved successfully.";
    }
}
