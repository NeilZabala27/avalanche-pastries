<?php
require 'auth.php';     // login check
require 'config.php';   // database connection

$repo = new OrderRepository($pdo);
$error = '';

// Delete an order (with its items and payment), then come back to the same list
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    try {
        $repo->delete((int)$_POST['delete_id']);
        $_SESSION['msg'] = "Order deleted.";
        header("Location: orders.php" . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
        exit;
    } catch (Exception $e) {
        $error = $e instanceof DomainException ? $e->getMessage() : "Could not delete the order. Try again.";
    }
}

$search = trim($_GET['search'] ?? '');
$month  = $_GET['month'] ?? '';
$dateBy = ($_GET['date_by'] ?? 'order') === 'delivery' ? 'delivery' : 'order';

// Pre-order filter: '' = all, 'none' = no pre-order, or the id of one pre-order
$preorder = $_GET['preorder'] ?? '';
if ($preorder !== 'none' && !ctype_digit($preorder)) {
    $preorder = '';
}
$preorders = $repo->findPreorders();

$orders = $repo->search($search, $month, $dateBy, $preorder);
$pageTitle = 'Orders';
include 'header.php';
?>
    <h1>Order Log</h1>

    <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="GET" class="toolbar">
        <input type="text" name="search" placeholder="Search customer name"
               value="<?= htmlspecialchars($search) ?>">
        <select name="date_by" aria-label="Sort and filter by this date" onchange="this.form.submit()">
            <option value="order" <?= $dateBy === 'order' ? 'selected' : '' ?>>By order date</option>
            <option value="delivery" <?= $dateBy === 'delivery' ? 'selected' : '' ?>>By delivery date</option>
        </select>
        <input type="month" name="month" aria-label="Month" value="<?= htmlspecialchars($month) ?>" onchange="this.form.submit()">
        <select name="preorder" aria-label="Pre-order" onchange="this.form.submit()">
            <option value="">All pre-orders</option>
            <option value="none" <?= $preorder === 'none' ? 'selected' : '' ?>>No pre-order</option>
            <?php foreach ($preorders as $p): ?>
                <option value="<?= $p['preorder_id'] ?>" <?= $preorder === (string)$p['preorder_id'] ? 'selected' : '' ?>>
                    Pre-order <?= htmlspecialchars($p['preorder_date']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Search</button>
        <a href="orders.php" class="btn btn-secondary">Clear</a>
        <a href="add_order.php" class="btn">+ Add order</a>
    </form>

    <table>
        <tr>
            <th>Customer</th><th>Type</th><th>Pre-order</th><th>Order date</th><th>Delivery date</th>
            <th>Total</th><th>Payment</th><th>Status</th><th class="col-actions">Actions</th>
        </tr>
        <?php foreach ($orders as $o): ?>
        <?php $payment = $o->getPayment(); ?>
        <tr>
            <td><?= htmlspecialchars($o->getCustomer()->getName()) ?></td>
            <td><?= htmlspecialchars($o->getType()) ?></td>
            <td><?= htmlspecialchars($o->getPreorderDate() ?? '-') ?></td>
            <td><?= htmlspecialchars($o->getOrderDate()) ?></td>
            <td><?= htmlspecialchars($o->getDeliveryDate()) ?></td>
            <td><?= $payment && $payment->getTotal() !== null ? '₱' . number_format($payment->getTotal(), 2) : '-' ?></td>
            <td><?= htmlspecialchars($payment ? $payment->getStatus() : '-') ?></td>
            <td><span class="badge <?= $o->getStatus() === 'Done' ? 'done' : 'pending' ?>">
                <?= htmlspecialchars($o->getStatus()) ?></span></td>
            <td class="col-actions">
                <div class="row-actions">
                    <a href="view_order.php?id=<?= $o->getId() ?>" class="btn btn-secondary btn-small">View</a>
                    <a href="add_order.php?id=<?= $o->getId() ?>" class="btn btn-secondary btn-small">Edit</a>
                    <form method="POST" onsubmit="return confirm('Delete this order and its payment record?')">
                        <input type="hidden" name="delete_id" value="<?= $o->getId() ?>">
                        <button type="submit" class="btn-danger btn-small">Delete</button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$orders): ?>
        <tr><td colspan="9">No orders found.</td></tr>
        <?php endif; ?>
    </table>
<?php include 'footer.php'; ?>
