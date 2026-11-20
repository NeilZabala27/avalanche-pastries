<?php
require "db.php";

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $id = (int)($_POST['id'] ?? 0);
    try {
        $repo = new IngredientRepository($pdo);
        $repo->delete($id);
        flash("Ingredient removed successfully.");
    } catch (PDOException $e) {
        // The ingredient is still used in a wastage or baking record
        flash("This ingredient cannot be removed because it is used in wastage or baking records.");
    }
}
header("Location: inventory.php");
exit;
