<?php
require 'auth.php';
require 'config.php';

$repo = new OrderRepository($pdo);
$id = (int)($_GET['id'] ?? 0);

// Toggle Done / Not done
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_status'])) {
    $new = $_POST['new_status'] === 'Done' ? 'Done' : 'Not done';
    $repo->updateStatus($id, $new);
    header("Location: view_order.php?id=$id");
    exit;
}

// Delete this order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    try {
        $repo->delete($id);
        $_SESSION['msg'] = "Order deleted.";
        header("Location: orders.php");
        exit;
    } catch (Exception $e) {
        $_SESSION['msg'] = "Could not delete the order. Try again.";
        header("Location: view_order.php?id=$id");
        exit;
    }
}

$order = $repo->findById($id);
if (!$order) die("Order not found.");

$customer = $order->getCustomer();
$payment = $order->getPayment();

function peso($v) { return $v !== null ? '₱' . number_format($v, 2) : '-'; }
$pageTitle = 'Order #' . $id;
include 'header.php';
?>
    <h1>Order #<?= $id ?></h1>
    <div class="card">
    <p><strong><?= htmlspecialchars($order->getType()) ?></strong>: <?= htmlspecialchars($order->getSummary()) ?></p>

    <h3>Customer</h3>
    <dl class="detail">
        <dt>Name</dt><dd><?= htmlspecialchars($customer->getName()) ?></dd>
        <dt>Address</dt><dd><?= htmlspecialchars($customer->getAddress() ?: '-') ?></dd>
        <dt>Contact</dt><dd><?= htmlspecialchars($customer->getContactNumber() ?: '-') ?></dd>
    </dl>

    <h3>Order</h3>
    <dl class="detail">
        <dt>Order date</dt><dd><?= htmlspecialchars($order->getOrderDate()) ?></dd>
        <dt>Delivery</dt><dd><?= htmlspecialchars(trim($order->getDeliveryDate() . ' ' . ($order->getDeliveryTime() ?? ''))) ?></dd>
        <?php if ($order->getPreorderDate()): ?>
        <dt>Pre-order date</dt><dd><?= htmlspecialchars($order->getPreorderDate()) ?></dd>
        <?php endif; ?>
        <dt>Status</dt>
        <dd><span class="badge <?= $order->getStatus() === 'Done' ? 'done' : 'pending' ?>"><?= htmlspecialchars($order->getStatus()) ?></span></dd>
        <dt>Shipping fee</dt><dd><?= peso($order->getShippingFee()) ?></dd>
        <dt>Terms and conditions told</dt><dd><?= $order->isTermsGiven() ? 'Yes' : 'No' ?></dd>
    </dl>

    <?php if ($order instanceof CustomizedCakeOrder): ?>
        <h3>Cake design</h3>
        <p><?= nl2br(htmlspecialchars($order->getDesignDescription() ?: '-')) ?></p>
    <?php endif; ?>

    <h3>Products</h3>
    <table>
        <tr><th>Product</th><th>Quantity</th></tr>
        <?php foreach ($order->getItems() as $item): ?>
        <tr><td><?= htmlspecialchars($item->getProduct()->getName()) ?></td><td><?= $item->getQuantity() ?></td></tr>
        <?php endforeach; ?>
    </table>

    <h3>Payment</h3>
    <?php if ($payment): ?>
    <dl class="detail">
        <dt>Total</dt><dd><?= peso($payment->getTotal()) ?></dd>
        <dt>Status</dt><dd><?= htmlspecialchars($payment->getStatus()) ?></dd>
        <dt>Method</dt><dd><?= htmlspecialchars($payment->getMethod()) ?></dd>
        <dt>Down payment</dt><dd><?= peso($payment->getDownPayment()) ?> (<?= htmlspecialchars($payment->getDownPaymentStatus()) ?>)</dd>
        <dt>Balance due</dt><dd><?= peso($payment->getBalanceDue()) ?></dd>
    </dl>
    <?php else: ?>
        <p>No payment recorded.</p>
    <?php endif; ?>

    </div>

    <div class="toolbar">
        <form method="POST">
            <input type="hidden" name="new_status" value="<?= $order->getStatus() === 'Done' ? 'Not done' : 'Done' ?>">
            <button type="submit">Mark as <?= $order->getStatus() === 'Done' ? 'not done' : 'done' ?></button>
        </form>
        <a href="add_order.php?id=<?= $id ?>" class="btn btn-secondary">Edit order</a>
        <form method="POST" onsubmit="return confirm('Delete this order and its payment record?')">
            <input type="hidden" name="delete_id" value="<?= $id ?>">
            <button type="submit" class="btn-danger">Delete order</button>
        </form>
        <a href="orders.php" class="btn btn-secondary">Back to orders</a>
    </div>
<?php include 'footer.php'; ?>
