<?php
require 'auth.php';
require 'config.php';

$repo = new ProductRepository($pdo);
$error = '';

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    try {
        $repo->delete((int)$_POST['delete_id']);   // refuses if the product is in an order
        $_SESSION['msg'] = "Product deleted.";
        header("Location: products.php");
        exit;
    } catch (DomainException $e) {
        $error = $e->getMessage();
    }
}

$search = trim($_GET['search'] ?? '');
$products = $repo->search($search);
$pageTitle = 'Products';
include 'header.php';
?>
    <h1>Products</h1>

    <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="GET" class="toolbar">
        <input type="text" name="search" placeholder="Search name or flavor"
               value="<?= htmlspecialchars($search) ?>">
        <button type="submit">Search</button>
        <a href="products.php" class="btn btn-secondary">Clear</a>
        <a href="product_form.php" class="btn">+ Add product</a>
    </form>

    <table>
        <tr>
            <th>Name</th><th>Flavor</th><th>Size</th><th>Quality</th>
            <th class="col-num">Price</th><th class="col-actions">Actions</th>
        </tr>
        <?php foreach ($products as $p): ?>
        <tr>
            <td><?= htmlspecialchars($p->getName()) ?></td>
            <td><?= htmlspecialchars($p->getFlavor()) ?></td>
            <td><?= htmlspecialchars($p->getSize()) ?></td>
            <td><?= htmlspecialchars($p->getQualityLevel()) ?></td>
            <td class="col-num">₱<?= number_format($p->getPrice(), 2) ?></td>
            <td class="col-actions">
                <div class="row-actions">
                    <a href="product_form.php?id=<?= $p->getId() ?>" class="btn btn-secondary btn-small">Edit</a>
                    <form method="POST" onsubmit="return confirm('Delete this product?')">
                        <input type="hidden" name="delete_id" value="<?= $p->getId() ?>">
                        <button type="submit" class="btn-danger btn-small">Delete</button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$products): ?>
        <tr><td colspan="6">No products found.</td></tr>
        <?php endif; ?>
    </table>
<?php include 'footer.php'; ?>
