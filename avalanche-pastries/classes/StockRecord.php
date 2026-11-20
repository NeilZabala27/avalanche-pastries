<?php
// ABSTRACTION: a StockRecord is "something that takes ingredients out of stock".
// This class cannot be used on its own. It fixes the steps of saving a record,
// and leaves the details (which table, how many ingredients) to the child classes.
abstract class StockRecord {
    protected $pdo;
    protected $date;
    protected $errors = [];

    public function __construct(PDO $pdo, $date) {
        $this->pdo = $pdo;
        $this->date = trim((string)$date);
    }

    // Each kind of record says which ingredients it uses: [ingredient_id => quantity]
    abstract protected function usages();

    // Each kind of record saves itself in its own table
    abstract protected function insertRecord($usages);

    abstract public function successMessage();

    public function getErrors() {
        return $this->errors;
    }

    // The same steps for every kind of record: check, save the record, then deduct the stock
    public function save() {
        if ($this->date === "") {
            $this->errors[] = "Date is required.";
        }

        $usages = $this->usages();
        if (count($usages) === 0 && count($this->errors) === 0) {
            $this->errors[] = "Add at least one ingredient used.";
        }

        $repo = new IngredientRepository($this->pdo);
        $ingredients = [];
        foreach ($usages as $id => $qty) {
            $ingredient = $repo->find($id);
            if (!$ingredient) {
                $this->errors[] = "An ingredient was not found.";
            } elseif ($qty > $ingredient->getStockQuantity()) {
                $this->errors[] = "Not enough " . $ingredient->getName() . " in stock.";
            } else {
                $ingredients[$id] = $ingredient;
            }
        }
        if (count($this->errors) > 0) return false;

        $this->pdo->beginTransaction();
        try {
            $this->insertRecord($usages);
            foreach ($usages as $id => $qty) {
                $ingredients[$id]->deduct($qty);
                $repo->save($ingredients[$id]);
            }
            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            $this->errors[] = "The record could not be saved.";
            return false;
        }
        return true;
    }
}
