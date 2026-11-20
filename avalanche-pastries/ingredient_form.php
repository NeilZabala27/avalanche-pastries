<?php
// One page for both Add (no id) and Edit (?id=...)
require "db.php";

$repo = new IngredientRepository($pdo);
$id = (int)($_GET['id'] ?? 0);
$errors = [];
$current = null;
$data = ["ingredient_name" => "", "stock_quantity" => "", "low_stock_level" => "", "expiration_date" => ""];

if ($id > 0) {
    $current = $repo->find($id);
    if (!$current) { flash("Ingredient not found."); header("Location: inventory.php"); exit; }
    $data = [
        "ingredient_name" => $current->getName(),
        "stock_quantity" => num($current->getStockQuantity()),
        "low_stock_level" => num($current->getLowStockLevel()),
        "expiration_date" => $current->getExpirationDate() ?? "",
    ];
}

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $data['ingredient_name'] = trim($_POST['ingredient_name'] ?? "");
    $data['stock_quantity'] = trim($_POST['stock_quantity'] ?? "");
    $data['low_stock_level'] = trim($_POST['low_stock_level'] ?? "");
    $data['expiration_date'] = trim($_POST['expiration_date'] ?? "");

    // Input validation: the Ingredient class checks the values with its own rules
    $errors = Ingredient::validate(
        $data['ingredient_name'], $data['stock_quantity'],
        $data['low_stock_level'], $data['expiration_date']
    );
    try {
        if (count($errors) === 0 && $repo->nameExists($data['ingredient_name'], $id)) {
            $errors[] = "An ingredient with this name already exists.";
        }

        if (count($errors) === 0) {
            $ingredient = new Ingredient(
                $id > 0 ? $id : null, $data['ingredient_name'], $data['stock_quantity'],
                $data['low_stock_level'], $data['expiration_date']
            );
            $repo->save($ingredient);
            flash($id > 0 ? "Ingredient updated successfully." : "Ingredient added successfully.");
            header("Location: inventory.php");
            exit;
        }
    } catch (PDOException $e) {
        $errors[] = FormHelper::dbError($e, "The ingredient could not be saved.");
    }
}

$pageTitle = $id > 0 ? "Edit Ingredient" : "Add Ingredient";
include "header.php";

$isEdit = $id > 0;
if ($isEdit && $current) {
    $status = $current->getStatus();
    $expiry = $current->getExpiryStatus();
    $stock = $current->getStockQuantity();
    $low = $current->getLowStockLevel();
    $pct = $low > 0 ? min(100, ($stock / ($low * 2)) * 100) : ($stock > 0 ? 100 : 0);
    $bg = $status === "Out of Stock" ? "bg-out" : ($status === "Low Stock" ? "bg-low" : "bg-instock");
    $txt = $status === "Out of Stock" ? "out" : ($status === "Low Stock" ? "low" : "instock");
}
?>
<h1><?= $isEdit ? "Edit Ingredient" : "Add Ingredient" ?></h1>
<p class="subtitle"><?= $isEdit ? "Update the details of this ingredient." : "Fill in the details of the new ingredient." ?></p>

<?php foreach ($errors as $e): ?><div class="error"><?= h($e) ?></div><?php endforeach; ?>

<div class="form-layout">
  <form method="post" class="card">
    <div class="form-grid">
      <div class="field full">
        <label for="ingredient_name">Ingredient name</label>
        <input type="text" id="ingredient_name" name="ingredient_name" value="<?= h($data['ingredient_name']) ?>" placeholder="e.g. All-purpose flour">
      </div>
      <div class="field">
        <label for="stock_quantity">Stock quantity</label>
        <input type="number" step="any" min="0" id="stock_quantity" name="stock_quantity" value="<?= h($data['stock_quantity']) ?>" placeholder="0">
        <small>How much is on hand right now.</small>
      </div>
      <div class="field">
        <label for="low_stock_level">Low-stock level</label>
        <input type="number" step="any" min="0" id="low_stock_level" name="low_stock_level" value="<?= h($data['low_stock_level']) ?>" placeholder="0">
        <small>Shows Low Stock at or below this number.</small>
      </div>
      <div class="field full">
        <label for="expiration_date">Expiration date</label>
        <input type="date" id="expiration_date" name="expiration_date" value="<?= h($data['expiration_date']) ?>">
      </div>
    </div>
    <div class="actions">
      <button type="submit"><?= $isEdit ? "Save changes" : "Add ingredient" ?></button>
      <a class="btn btn-secondary" href="inventory.php">Cancel</a>
    </div>
  </form>

  <aside class="card side">
    <?php if ($isEdit && $current): ?>
      <h3 class="card-title">Current status</h3>
      <div class="stat"><span>Stock status</span><strong class="<?= $txt ?>"><?= h($status) ?></strong></div>
      <div class="bar"><span class="<?= $bg ?>" style="width: <?= round($pct) ?>%"></span></div>
      <div class="stat"><span>In stock</span><strong><?= num($current->getStockQuantity()) ?></strong></div>
      <div class="stat"><span>Low-stock level</span><strong><?= num($current->getLowStockLevel()) ?></strong></div>
      <div class="stat"><span>Expires</span><strong><?= h($current->getExpirationDate() ?: "-") ?></strong></div>
      <?php if ($expiry !== ""): ?>
        <div class="stat"><span>Expiration</span><strong class="expired"><?= h($expiry) ?></strong></div>
      <?php endif; ?>
    <?php else: ?>
      <h3 class="card-title">Tips</h3>
      <ul class="hint-list">
        <li>Use the same name every time, so searching is easy.</li>
        <li>Set the low-stock level to the amount where you need to buy more.</li>
        <li>Items close to their expiration date are marked <strong>Expiring Soon</strong>.</li>
      </ul>
    <?php endif; ?>
  </aside>
</div>
<?php include 'footer.php'; ?>
