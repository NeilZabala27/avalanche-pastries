<?php
require "db.php";
$pageTitle = "Inventory";
include "header.php";

// 1. Load all ingredients from the database as Ingredient objects (an array of objects)
$repo = new IngredientRepository($pdo);
$items = $repo->findAll();

// 2. Search (linear search) and sort (merge sort with a sort strategy)
$search = trim($_GET['search'] ?? "");
$sort = ($_GET['sort'] ?? "name") === "quantity" ? "quantity" : "name";
$dir = ($_GET['dir'] ?? "asc") === "desc" ? "desc" : "asc";
$strategy = ($sort === "quantity") ? new QuantitySort() : new NameSort();
if ($dir === "desc") {
    $strategy = new DescendingSort($strategy);     // same sort, opposite direction
}

$shown = ($search !== "") ? InventoryAlgorithms::searchByName($items, $search) : $items;
$shown = InventoryAlgorithms::mergeSort($shown, $strategy);

// 3. Low-stock alert (min-heap)
$alerts = InventoryAlgorithms::lowStockAlerts($items);
?>

<h1>Ingredient Inventory</h1>

<form method="get" class="toolbar">
  <input type="text" name="search" placeholder="Search by name" value="<?= h($search) ?>">
  <select name="sort" aria-label="Sort by" onchange="this.form.submit()">
    <option value="name" <?= $sort === "name" ? "selected" : "" ?>>Sort by name</option>
    <option value="quantity" <?= $sort === "quantity" ? "selected" : "" ?>>Sort by quantity</option>
  </select>
  <select name="dir" aria-label="Order" onchange="this.form.submit()">
    <option value="asc" <?= $dir === "asc" ? "selected" : "" ?>>Ascending (A-Z, low to high)</option>
    <option value="desc" <?= $dir === "desc" ? "selected" : "" ?>>Descending (Z-A, high to low)</option>
  </select>
  <button type="submit">Apply</button>
  <a href="inventory.php" class="btn btn-secondary">Clear</a>
</form>

<table>
  <tr>
    <th>Name</th><th>Stock quantity</th><th>Low-stock level</th>
    <th>Expiration</th><th>Status</th><th class="col-actions">Actions</th>
  </tr>
  <?php if (count($shown) === 0): ?>
    <tr><td colspan="6">No ingredients found.</td></tr>
  <?php endif; ?>
  <?php foreach ($shown as $it):
        $status = $it->getStatus();
        $expiry = $it->getExpiryStatus();
        $cls = $status === "Out of Stock" ? "out" : ($status === "Low Stock" ? "low" : "instock");
  ?>
    <tr>
      <td><?= h($it->getName()) ?></td>
      <td><?= num($it->getStockQuantity()) ?></td>
      <td><?= num($it->getLowStockLevel()) ?></td>
      <td><?= h($it->getExpirationDate() ?: "-") ?></td>
      <td>
        <span class="<?= $cls ?>"><?= h($status) ?></span>
        <?php if ($expiry !== ""): ?> | <span class="expired"><?= h($expiry) ?></span><?php endif; ?>
      </td>
      <td class="col-actions">
        <div class="row-actions">
          <a href="ingredient_form.php?id=<?= (int)$it->getId() ?>" class="btn btn-secondary btn-small">Edit</a>
          <form method="post" action="delete.php"
                onsubmit="return confirm('Remove this ingredient?');">
            <input type="hidden" name="id" value="<?= (int)$it->getId() ?>">
            <button type="submit" class="btn-danger btn-small">Remove</button>
          </form>
        </div>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<?php if (count($alerts) > 0): ?>
  <div id="popup">
    <strong>Low stock alert</strong>
    <ul>
      <?php foreach ($alerts as $a): ?>
        <li><?= h($a->getName()) ?>: <?= num($a->getStockQuantity()) ?> (<?= h($a->getStatus()) ?>)</li>
      <?php endforeach; ?>
    </ul>
    <button onclick="document.getElementById('popup').style.display='none'">Close</button>
  </div>
<?php endif; ?>
<?php include 'footer.php'; ?>
