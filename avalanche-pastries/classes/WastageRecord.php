<?php
// INHERITANCE: WastageRecord extends StockRecord, so it reuses the date check,
// the stock check, and the save steps. It only adds what is different: one ingredient.
class WastageRecord extends StockRecord {
    private $ingredientId;
    private $quantity;

    public function __construct(PDO $pdo, $ingredientId, $quantity, $date) {
        parent::__construct($pdo, $date);
        $this->ingredientId = (int)$ingredientId;
        if ($this->ingredientId === 0) {
            $this->errors[] = "Please choose an ingredient.";
        }
        if (!is_numeric($quantity) || $quantity <= 0) {
            $this->errors[] = "Wastage quantity must be a number greater than 0.";
            $this->quantity = 0;
        } else {
            $this->quantity = (float)$quantity;
        }
    }

    protected function usages() {
        if ($this->ingredientId > 0 && $this->quantity > 0) {
            return [$this->ingredientId => $this->quantity];
        }
        return [];
    }

    protected function insertRecord($usages) {
        $this->pdo->prepare("INSERT INTO wastage (ingredient_id, wastage_quantity, wastage_date) VALUES (?,?,?)")
            ->execute([$this->ingredientId, $this->quantity, $this->date]);
    }

    public function successMessage() {
        return "Wastage recorded successfully.";
    }
}
