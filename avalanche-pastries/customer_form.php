<?php
require 'auth.php';
require 'config.php';

$repo = new CustomerRepository($pdo);
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];

// Edit: load the existing customer. Add: start with an empty one.
if ($id) {
    $customer = $repo->findById($id);
    if (!$customer) die("Customer not found.");
} else {
    $customer = new Customer();
}

// What the form shows (what was typed, or the saved values)
$form = [
    'customer_name'  => $customer->getName(),
    'address'        => $customer->getAddress(),
    'contact_number' => $customer->getContactNumber(),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['customer_name']  = trim($_POST['customer_name'] ?? '');
    $form['address']        = trim($_POST['address'] ?? '');
    $form['contact_number'] = trim($_POST['contact_number'] ?? '');

    // The Customer object validates its own data
    FormHelper::attempt($errors, fn() => $customer->setName($form['customer_name']));
    FormHelper::attempt($errors, fn() => $customer->setContactNumber($form['contact_number']));
    $customer->setAddress($form['address']);

    if (!$errors) {
        $repo->save($customer);
        $_SESSION['msg'] = $id ? "Customer updated." : "Customer added.";
        header("Location: customers.php");
        exit;
    }
}
$pageTitle = $id ? 'Edit Customer' : 'Add Customer';
include 'header.php';
?>
    <h1><?= $id ? 'Edit' : 'Add' ?> Customer</h1>

    <?php if ($errors): ?>
        <div class="error"><strong>Please fix these:</strong>
            <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="POST" class="card">
        <div class="field"><label>Name</label>
            <input type="text" name="customer_name" value="<?= htmlspecialchars($form['customer_name']) ?>"></div>
        <div class="field"><label>Address</label>
            <input type="text" name="address" value="<?= htmlspecialchars($form['address']) ?>"></div>
        <div class="field"><label>Contact number</label>
            <input type="text" inputmode="numeric" data-digits maxlength="12" placeholder="09123456789" name="contact_number" value="<?= htmlspecialchars($form['contact_number']) ?>"></div>
        <div class="actions">
            <button type="submit">Save customer</button>
            <a href="customers.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
<?php include 'footer.php'; ?>
