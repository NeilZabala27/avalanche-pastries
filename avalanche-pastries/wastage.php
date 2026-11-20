<?php
require "db.php";

$errors = [];
$repo = new IngredientRepository($pdo);
$ingredients = InventoryAlgorithms::mergeSort($repo->findAll(), new NameSort());

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    // WastageRecord extends StockRecord: save() checks the stock, saves, and deducts it
    $record = new WastageRecord(
        $pdo,
        $_POST['ingredient_id'] ?? 0,
        trim($_POST['wastage_quantity'] ?? ""),
        trim($_POST['wastage_date'] ?? "")
    );
    if ($record->save()) {
        flash($record->successMessage());
        header("Location: inventory.php");
        exit;
    }
    $errors = $record->getErrors();
}

$records = $pdo->query("SELECT w.*, i.ingredient_name FROM wastage w
                        JOIN ingredient i ON i.ingredient_id = w.ingredient_id
                        ORDER BY w.wastage_date DESC, w.wastage_id DESC LIMIT 20")->fetchAll();
$pageTitle = "Wastage Records";
include "header.php";
?>
<h1>Wastage Records</h1>
<p class="subtitle">Record ingredients that were thrown away or spoiled. The amount is taken out of stock.</p>
<?php foreach ($errors as $e): ?><div class="error"><?= h($e) ?></div><?php endforeach; ?>

<div class="two-col">
  <form method="post" class="card">
    <h3 class="card-title">New wastage record</h3>
    <div class="form-grid">
      <div class="field full">
        <label for="ingredient_id">Ingredient</label>
        <select id="ingredient_id" name="ingredient_id">
          <option value="">-- choose --</option>
          <?php foreach ($ingredients as $i): ?>
            <option value="<?= (int)$i->getId() ?>"><?= h($i->getName()) ?> (stock: <?= num($i->getStockQuantity()) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="wastage_quantity">Quantity wasted</label>
        <input type="number" step="any" min="0" id="wastage_quantity" name="wastage_quantity" placeholder="0">
      </div>
      <div class="field">
        <label for="wastage_date">Date</label>
        <input type="date" id="wastage_date" name="wastage_date" value="<?= date('Y-m-d') ?>">
      </div>
    </div>
    <div class="actions"><button type="submit">Save wastage</button></div>
  </form>

  <div class="card">
    <h3 class="card-title">Recent wastage</h3>
    <?php if (count($records) === 0): ?>
      <p class="empty">No wastage records yet.</p>
    <?php else: ?>
      <table>
        <tr><th>Date</th><th>Ingredient</th><th>Quantity wasted</th></tr>
        <?php foreach ($records as $r): ?>
          <tr><td><?= h($r['wastage_date']) ?></td><td><?= h($r['ingredient_name']) ?></td><td><?= h($r['wastage_quantity']) ?></td></tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </div>
</div>
<?php include 'footer.php'; ?>
