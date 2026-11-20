<?php
// ENCAPSULATION: the fields are private. The rest of the program can only read
// or change them through the methods below, and those methods check the values.
class Ingredient {
    // Days before the expiration date when the ingredient shows "Expiring Soon".
    // 3 is a guess, so confirm the number with the owner.
    const EXPIRY_WARNING_DAYS = 3;

    private $id;
    private $name;
    private $stockQuantity;
    private $lowStockLevel;
    private $expirationDate;

    public function __construct($id, $name, $stockQuantity, $lowStockLevel, $expirationDate) {
        $this->id = ($id === null) ? null : (int)$id;
        $this->setName($name);
        $this->setStockQuantity($stockQuantity);
        $this->setLowStockLevel($lowStockLevel);
        $this->setExpirationDate($expirationDate);
    }

    // Builds an Ingredient from one row of the ingredient table
    public static function fromRow($row) {
        return new Ingredient(
            $row['ingredient_id'], $row['ingredient_name'], $row['stock_quantity'],
            $row['low_stock_level'], $row['expiration_date']
        );
    }

    // ----- getters -----
    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getStockQuantity() { return $this->stockQuantity; }
    public function getLowStockLevel() { return $this->lowStockLevel; }
    public function getExpirationDate() { return $this->expirationDate; }

    // ----- setters (they validate, so a bad value can never get in) -----
    public function setName($name) {
        $name = trim((string)$name);
        if ($name === "" || strlen($name) > 100) {
            throw new InvalidArgumentException("Ingredient name is required (max 100 characters).");
        }
        $this->name = $name;
    }

    public function setStockQuantity($quantity) {
        if (!is_numeric($quantity) || $quantity < 0) {
            throw new InvalidArgumentException("Stock quantity must be a number, 0 or more.");
        }
        $this->stockQuantity = (float)$quantity;
    }

    public function setLowStockLevel($level) {
        if (!is_numeric($level) || $level < 0) {
            throw new InvalidArgumentException("Low-stock level must be a number, 0 or more.");
        }
        $this->lowStockLevel = (float)$level;
    }

    public function setExpirationDate($date) {
        if ($date === null || $date === "") {
            $this->expirationDate = null;
            return;
        }
        $d = DateTime::createFromFormat("Y-m-d", (string)$date);
        if (!$d || $d->format("Y-m-d") !== (string)$date) {
            throw new InvalidArgumentException("Expiration date must be a valid date.");
        }
        $this->expirationDate = (string)$date;
    }

    // Takes an amount out of stock. Stock can never go below zero.
    public function deduct($amount) {
        if (!is_numeric($amount) || $amount <= 0) {
            throw new InvalidArgumentException("Amount must be greater than 0.");
        }
        if ($amount > $this->stockQuantity) {
            throw new InvalidArgumentException("Not enough " . $this->name . " in stock.");
        }
        $this->stockQuantity = round($this->stockQuantity - $amount, 2);
    }

    // Threshold check: In Stock, Low Stock, or Out of Stock
    public function getStatus() {
        if ($this->stockQuantity <= 0) return "Out of Stock";
        if ($this->stockQuantity <= $this->lowStockLevel) return "Low Stock";
        return "In Stock";
    }

    public function isLowOrOut() {
        return $this->getStatus() !== "In Stock";
    }

    public function getExpiryStatus() {
        if ($this->expirationDate === null) return "";
        $today = strtotime(date("Y-m-d"));
        $exp = strtotime($this->expirationDate);
        if ($exp < $today) return "Expired";
        if ($exp <= $today + self::EXPIRY_WARNING_DAYS * 86400) return "Expiring Soon";
        return "";
    }

    // Checks form input using the same rules as the setters. Returns a list of messages.
    public static function validate($name, $quantity, $level, $date) {
        $probe = new Ingredient(null, "probe", 0, 0, null);
        $errors = [];
        $checks = [
            ["setName", $name],
            ["setStockQuantity", $quantity],
            ["setLowStockLevel", $level],
            ["setExpirationDate", $date],
        ];
        foreach ($checks as $check) {
            try {
                $probe->{$check[0]}($check[1]);
            } catch (InvalidArgumentException $e) {
                $errors[] = $e->getMessage();
            }
        }
        // The owner checks expiration dates (Form 4, rule 13), so the form requires one.
        if (trim((string)$date) === "") {
            $errors[] = "Expiration date is required.";
        }
        return $errors;
    }
}
