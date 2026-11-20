<?php
// The ONE menu used on every page (header.php includes this file).
// The inventory sub-menu (Wastage, Baking records, Add ingredient) only appears on inventory pages.
$navPage = basename($_SERVER['SCRIPT_NAME']);
$navInventoryPages = ['inventory.php', 'ingredient_form.php', 'wastage.php', 'baking.php'];

// Orders has no separate "Add order" tab, so the order form (add and edit) highlights Orders
$navOrderPages = ['orders.php', 'view_order.php', 'add_order.php'];

$navMenu = [
    ['orders.php',    'Orders',      $navOrderPages],
    ['customers.php', 'Customers',   ['customers.php', 'customer_form.php']],
    ['products.php',  'Products',    ['products.php', 'product_form.php']],
    ['preorders.php', 'Pre-orders',  ['preorders.php']],
    ['inventory.php', 'Inventory',   $navInventoryPages],
    ['reports.php',   'Reports',     ['reports.php']],
];
$navInventoryMenu = [
    ['inventory.php',       'Ingredients'],
    ['ingredient_form.php', '+ Add ingredient'],
    ['wastage.php',         'Wastage'],
    ['baking.php',          'Baking records'],
];
?>
<nav>
    <button type="button" class="nav-toggle" aria-expanded="false" aria-controls="nav-links">&#9776; Menu</button>
    <div class="nav-links" id="nav-links">
    <?php foreach ($navMenu as [$navHref, $navLabel, $navPages]): ?>
        <a href="<?= $navHref ?>"<?= in_array($navPage, $navPages, true) ? ' class="active"' : '' ?>><?= $navLabel ?></a>
    <?php endforeach; ?>
    <span class="spacer"></span>
    <span class="user"><?= htmlspecialchars($_SESSION['username'] ?? '') ?></span>
    <a href="logout.php">Log out</a>
    </div>
</nav>
<?php if (in_array($navPage, $navInventoryPages, true)): ?>
<div class="subnav">
    <?php foreach ($navInventoryMenu as [$navHref, $navLabel]): ?>
        <a href="<?= $navHref ?>"<?= $navPage === $navHref ? ' class="active"' : '' ?>><?= $navLabel ?></a>
    <?php endforeach; ?>
</div>
<?php endif; ?>
