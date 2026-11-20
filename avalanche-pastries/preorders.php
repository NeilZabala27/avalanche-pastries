<?php
require 'auth.php';
require 'config.php';

$repo = new PreorderBatchRepository($pdo);
$errors = [];
$date = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        // Delete a date (refused if it has orders)
        try {
            $repo->delete((int)$_POST['delete_id']);
            $_SESSION['msg'] = "Pre-order date deleted.";
            header("Location: preorders.php");
            exit;
        } catch (DomainException $e) {
            $errors[] = $e->getMessage();
        }
    } else {
        // Add a date. The PreorderBatch object checks it.
        $date = trim($_POST['preorder_date'] ?? '');
        $batch = new PreorderBatch();
        if (FormHelper::attempt($errors, fn() => $batch->setDate($date))) {
            try {
                $repo->save($batch);
                $_SESSION['msg'] = "Pre-order date added.";
                header("Location: preorders.php");
                exit;
            } catch (DomainException $e) {
                $errors[] = $e->getMessage();
            }
        }
    }
}

$batches = $repo->findAll();
$pageTitle = 'Pre-orders';
include 'header.php';
?>
    <h1>Pre-orders</h1>
    <p class="subtitle">Post a pre-order date here. Then you can choose it when you add a pastry order.</p>

    <?php foreach ($errors as $e): ?><div class="error"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>

    <form method="POST" class="toolbar">
        <input type="date" name="preorder_date" value="<?= htmlspecialchars($date) ?>">
        <button type="submit">+ Add pre-order date</button>
    </form>

    <table>
        <tr><th>Pre-order date</th><th class="col-num">Orders</th><th class="col-actions">Actions</th></tr>
        <?php foreach ($batches as $b): ?>
        <tr>
            <td><?= htmlspecialchars($b->getDate()) ?></td>
            <td class="col-num"><?= $b->getOrderCount() ?></td>
            <td class="col-actions">
                <div class="row-actions">
                    <a href="orders.php?preorder=<?= $b->getId() ?>" class="btn btn-secondary btn-small">View orders</a>
                    <form method="POST" onsubmit="return confirm('Delete this pre-order date?')">
                        <input type="hidden" name="delete_id" value="<?= $b->getId() ?>">
                        <button type="submit" class="btn-danger btn-small">Delete</button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$batches): ?>
        <tr><td colspan="3">No pre-order dates yet.</td></tr>
        <?php endif; ?>
    </table>
<?php include 'footer.php'; ?>
