<?php
require 'auth.php';
require 'config.php';

$repo = new CustomerRepository($pdo);
$message = '';
$error = '';

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    try {
        $repo->delete((int)$_POST['delete_id']);   // refuses if the customer has orders
        $message = "Customer deleted.";
    } catch (DomainException $e) {
        $error = $e->getMessage();
    }
}

// List with search
$search = trim($_GET['search'] ?? '');
$customers = $repo->search($search);
$pageTitle = 'Customers';
include 'header.php';
?>
    <h1>Customers</h1>

    <?php if ($message): ?><div class="success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="GET" class="toolbar">
        <input type="text" name="search" placeholder="Search name or contact number"
               value="<?= htmlspecialchars($search) ?>">
        <button type="submit">Search</button>
        <a href="customers.php" class="btn btn-secondary">Clear</a>
        <a href="customer_form.php" class="btn">+ Add customer</a>
    </form>

    <table>
        <tr>
            <th>Name</th><th>Address</th><th>Contact number</th>
            <th class="col-num">Orders</th><th class="col-actions">Actions</th>
        </tr>
        <?php foreach ($customers as $c): ?>
        <tr>
            <td><?= htmlspecialchars($c->getName()) ?></td>
            <td><?= htmlspecialchars($c->getAddress()) ?></td>
            <td><?= htmlspecialchars($c->getContactNumber()) ?></td>
            <td class="col-num"><?= $c->getOrderCount() ?></td>
            <td class="col-actions">
                <div class="row-actions">
                    <a href="orders.php?search=<?= urlencode($c->getName()) ?>" class="btn btn-secondary btn-small">View orders</a>
                    <a href="customer_form.php?id=<?= $c->getId() ?>" class="btn btn-secondary btn-small">Edit</a>
                    <form method="POST" onsubmit="return confirm('Delete this customer?')">
                        <input type="hidden" name="delete_id" value="<?= $c->getId() ?>">
                        <button type="submit" class="btn-danger btn-small">Delete</button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$customers): ?>
        <tr><td colspan="5">No customers found.</td></tr>
        <?php endif; ?>
    </table>
<?php include 'footer.php'; ?>
