<?php
require 'auth.php';
require 'config.php';

// Draws a dropdown. A saved value that is not in the list (an older product) is added so it is never lost.
function choiceSelect(string $name, array $options, string $selected, string $blank): void
{
    if ($selected !== '' && !in_array($selected, $options, true)) {
        $options[] = $selected;
    }
    echo '<select name="' . htmlspecialchars($name) . '">';
    echo '<option value="">' . htmlspecialchars($blank) . '</option>';
    foreach ($options as $option) {
        echo '<option' . ($option === $selected ? ' selected' : '') . '>' . htmlspecialchars($option) . '</option>';
    }
    echo '</select>';
}

$repo = new ProductRepository($pdo);
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];

// Edit: load the existing product. Add: start with an empty one.
if ($id) {
    $product = $repo->findById($id);
    if (!$product) die("Product not found.");
} else {
    $product = new Product();
}

// What the form shows (what was typed, or the saved values)
$form = [
    'product_name'  => $product->getName(),
    'flavor'        => $product->getFlavor(),
    'size'          => $product->getSize(),
    'quality_level' => $product->getQualityLevel(),
    'price'         => $id ? number_format($product->getPrice(), 2, '.', '') : '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $key => $value) {
        $form[$key] = trim($_POST[$key] ?? '');
    }

    // The Product object validates its own data
    FormHelper::attempt($errors, fn() => $product->setName($form['product_name']));
    FormHelper::attempt($errors, fn() => $product->setFlavor($form['flavor']));
    FormHelper::attempt($errors, fn() => $product->setSize($form['size']));
    FormHelper::attempt($errors, fn() => $product->setQualityLevel($form['quality_level']));
    FormHelper::attempt($errors, fn() => $product->setPrice($form['price']));

    if (!$errors) {
        try {
            $repo->save($product);
            $_SESSION['msg'] = $id ? "Product updated." : "Product added.";
            header("Location: products.php");
            exit;
        } catch (DomainException $e) {
            $errors[] = $e->getMessage();
        } catch (PDOException $e) {
            $errors[] = FormHelper::dbError($e, 'The product could not be saved.');
        }
    }
}
$pageTitle = $id ? 'Edit Product' : 'Add Product';
include 'header.php';
?>
    <h1><?= $id ? 'Edit' : 'Add' ?> Product</h1>

    <?php if ($errors): ?>
        <div class="error"><strong>Please fix these:</strong>
            <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="POST" class="card">
        <div class="field"><label>Product name</label>
            <input type="text" name="product_name" value="<?= htmlspecialchars($form['product_name']) ?>" placeholder="e.g. Muffin"></div>
        <div class="grid">
            <div class="field"><label>Flavor</label>
                <input type="text" name="flavor" value="<?= htmlspecialchars($form['flavor']) ?>" placeholder="e.g. Chocolate"></div>
            <div class="field"><label>Size</label>
                <input type="text" name="size" value="<?= htmlspecialchars($form['size']) ?>" placeholder="e.g. Regular, 8 inch"></div>
            <div class="field"><label>Quality level</label>
                <?php choiceSelect('quality_level', Product::QUALITY_LEVELS, $form['quality_level'], '-- None --'); ?>
                <small>The price depends on the quality of the ingredients.</small></div>
            <div class="field"><label>Price (₱)</label>
                <input type="number" step="any" min="0" name="price" value="<?= htmlspecialchars($form['price']) ?>" placeholder="0.00"></div>
        </div>
        <div class="actions">
            <button type="submit">Save product</button>
            <a href="products.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
<?php include 'footer.php'; ?>
