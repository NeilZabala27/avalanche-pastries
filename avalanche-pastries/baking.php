<?php
require "db.php";

// One row of the "ingredients used" list (the same idea as a product row on the order form)
function ingredientRow($ingredients, $selectedId, $qty) { ?>
    <div class="item-row">
      <select name="ingredient_id[]">
        <option value="">-- ingredient --</option>
        <?php foreach ($ingredients as $i): ?>
          <option value="<?= (int)$i->getId() ?>" <?= ((string)$selectedId === (string)$i->getId()) ? 'selected' : '' ?>><?= h($i->getName()) ?> (stock: <?= num($i->getStockQuantity()) ?>)</option>
        <?php endforeach; ?>
      </select>
      <input type="number" step="any" min="0" name="quantity_used[]" placeholder="Qty" value="<?= h($qty) ?>">
      <button type="button" class="btn-secondary btn-small" onclick="removeRow(this)">Remove</button>
    </div>
<?php }

$errors = [];
$repo = new IngredientRepository($pdo);
$ingredients = InventoryAlgorithms::mergeSort($repo->findAll(), new NameSort());

// The rows sent by the form (any number of them)
$postedIds = is_array($_POST['ingredient_id'] ?? null) ? array_values($_POST['ingredient_id']) : [];
$postedQty = is_array($_POST['quantity_used'] ?? null) ? array_values($_POST['quantity_used']) : [];

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    // BakingRecord also extends StockRecord, so save() is called the same way as for wastage
    $record = new BakingRecord(
        $pdo,
        $postedIds,
        $postedQty,
        trim($_POST['baking_date'] ?? ""),
        max(count($postedIds), count($postedQty))
    );
    if ($record->save()) {
        flash($record->successMessage());
        header("Location: inventory.php");
        exit;
    }
    $errors = $record->getErrors();
}

// What the form shows (the rows that were typed, or one empty row)
$rows = [];
foreach ($postedIds as $i => $rid) {
    $rows[] = [$rid, $postedQty[$i] ?? ''];
}
if (!$rows) {
    $rows[] = ['', ''];
}

$records = $pdo->query("SELECT b.baking_id, b.baking_date,
        GROUP_CONCAT(CONCAT(i.ingredient_name, ' ', bi.quantity_used) SEPARATOR ', ') AS used_items
        FROM baking b
        JOIN baking_ingredient bi ON bi.baking_id = b.baking_id
        JOIN ingredient i ON i.ingredient_id = bi.ingredient_id
        GROUP BY b.baking_id, b.baking_date ORDER BY b.baking_date DESC, b.baking_id DESC LIMIT 20")->fetchAll();
$pageTitle = "Baking Records";
include "header.php";
?>
<h1>Baking Records</h1>
<p class="subtitle">Record the ingredients used for a baking. The amounts are taken out of stock.</p>
<?php foreach ($errors as $e): ?><div class="error"><?= h($e) ?></div><?php endforeach; ?>

<div class="two-col">
  <form method="post" class="card">
    <h3 class="card-title">New baking record</h3>
    <div class="field" style="margin-bottom: 18px;">
      <label for="baking_date">Date baked</label>
      <input type="date" id="baking_date" name="baking_date" value="<?= h($_POST['baking_date'] ?? date('Y-m-d')) ?>">
    </div>
    <div class="field"><label>Ingredients used</label></div>
    <div id="rows">
      <?php foreach ($rows as [$rid, $rqty]) ingredientRow($ingredients, $rid, $rqty); ?>
    </div>
    <button type="button" class="btn-secondary btn-small" onclick="addRow()">+ Add ingredient</button>
    <div class="actions"><button type="submit">Save baking record</button></div>
    <template id="rowTemplate"><?php ingredientRow($ingredients, '', ''); ?></template>
  </form>

  <div class="card">
    <h3 class="card-title">Recent baking records</h3>
    <?php if (count($records) === 0): ?>
      <p class="empty">No baking records yet.</p>
    <?php else: ?>
      <table>
        <tr><th>Date</th><th>Ingredients used</th></tr>
        <?php foreach ($records as $r): ?>
          <tr><td><?= h($r['baking_date']) ?></td><td><?= h($r['used_items']) ?></td></tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </div>
</div>
<?php include 'footer.php'; ?>
