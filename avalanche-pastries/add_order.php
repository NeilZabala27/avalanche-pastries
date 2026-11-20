<?php
require 'auth.php';
require 'config.php';

// $form = what the page shows: what was typed (POST), the saved order (edit), or empty (add).
function old($key, $default = '') { global $form; return htmlspecialchars((string)($form[$key] ?? $default)); }
function sel($key, $value) { global $form; return (($form[$key] ?? '') === (string)$value) ? 'selected' : ''; }

// The saved order turned into the same shape as $_POST, so the form fills itself in when editing.
function formFromOrder(Order $o): array
{
    $p = $o->getPayment();
    $f = [
        'customer_id'        => (string)$o->getCustomer()->getId(),
        'order_date'         => $o->getOrderDate(),
        'delivery_date'      => $o->getDeliveryDate(),
        'delivery_time'      => $o->getDeliveryTime() ?? '',
        'preorder_id'        => $o->getPreorderId() !== null ? (string)$o->getPreorderId() : '',
        'order_status'       => $o->getStatus(),
        'shipping_fee'       => $o->getShippingFee() > 0 ? (string)$o->getShippingFee() : '',
        'design_description' => $o instanceof CustomizedCakeOrder ? $o->getDesignDescription() : '',
        'product_id'         => [],
        'quantity'           => [],
    ];
    if ($o->isCustomized())  $f['is_customized'] = 'on';
    if ($o->isTermsGiven())  $f['terms_given'] = 'on';
    foreach ($o->getItems() as $item) {
        $f['product_id'][] = (string)$item->getProduct()->getId();
        $f['quantity'][]   = (string)$item->getQuantity();
    }
    if ($p) {
        $f['total_amount']        = $p->getTotal() !== null ? (string)$p->getTotal() : '';
        $f['payment_status']      = $p->getStatus();
        $f['payment_method']      = $p->getMethod();
        $f['down_payment']        = $p->getDownPayment() > 0 ? (string)$p->getDownPayment() : '';
        $f['down_payment_status'] = $p->getDownPaymentStatus();
    }
    return $f;
}

function productRow($products, $pid, $qty) { ?>
    <div class="item-row">
        <select name="product_id[]">
            <option value="">-- Product --</option>
            <?php foreach ($products as $p): ?>
                <option value="<?= $p->getId() ?>" <?= ((string)$pid === (string)$p->getId()) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p->getLabel()) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="quantity[]" min="1" step="1" placeholder="Qty" value="<?= htmlspecialchars($qty) ?>">
        <button type="button" class="btn-secondary btn-small" onclick="removeRow(this)">Remove</button>
    </div>
<?php }

$customerRepo = new CustomerRepository($pdo);
$orderRepo    = new OrderRepository($pdo);
$productRepo  = new ProductRepository($pdo);

$customers = $customerRepo->findAll();
$preorders = $orderRepo->findPreorders();
$products  = $productRepo->findAll();
$productsById = [];
foreach ($products as $p) $productsById[$p->getId()] = $p;

$errors = [];

// add_order.php = new order, add_order.php?id=5 = edit order 5
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$existing = null;
if ($id) {
    $existing = $orderRepo->findById($id);
    if (!$existing) die("Order not found.");
}
$form = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : ($existing ? formFromOrder($existing) : []);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ---- Customer: pick an existing one or build a new one ----
    $customer_id = $_POST['customer_id'] ?? '';
    $customer = null;
    if ($customer_id === '') {
        $customer = new Customer();
        if (trim($_POST['new_name'] ?? '') === '') {
            $errors[] = "Choose a customer or enter the new customer name.";
        } else {
            FormHelper::attempt($errors, fn() => $customer->setName($_POST['new_name']));
        }
        FormHelper::attempt($errors, fn() => $customer->setContactNumber($_POST['new_contact'] ?? ''));
        $customer->setAddress($_POST['new_address'] ?? '');
    } else {
        $customer = $customerRepo->findById((int)$customer_id);
        if (!$customer) $errors[] = "The selected customer no longer exists.";
    }

    // ---- The right kind of order (standard / pre-order / customized cake) ----
    $isCustomized = isset($_POST['is_customized']);
    $preorderId = ($_POST['preorder_id'] ?? '') !== '' ? (int)$_POST['preorder_id'] : null;
    $order = Order::forType($isCustomized, $preorderId);
    if ($customer) $order->setCustomer($customer);
    $order->setPreorderId($preorderId);
    $order->setTermsGiven(isset($_POST['terms_given']));

    // ---- Dates, status, shipping: the Order validates its own data ----
    FormHelper::attempt($errors, fn() => $order->setOrderDate($_POST['order_date'] ?? ''));
    FormHelper::attempt($errors, fn() => $order->setDeliveryDate($_POST['delivery_date'] ?? ''));
    FormHelper::attempt($errors, fn() => $order->setDeliveryTime($_POST['delivery_time'] ?? ''));
    FormHelper::attempt($errors, fn() => $order->setStatus($_POST['order_status'] ?? ''));
    FormHelper::attempt($errors, fn() => $order->setShippingFee($_POST['shipping_fee'] ?? ''));

    // ---- Cake design ----
    if ($order instanceof CustomizedCakeOrder) {
        FormHelper::attempt($errors, fn() => $order->setDesignDescription($_POST['design_description'] ?? ''));
    }

    // ---- Products ----
    $rowError = false;
    $postedIds = is_array($_POST['product_id'] ?? null) ? $_POST['product_id'] : [];
    foreach ($postedIds as $i => $pid) {
        $pid = trim((string)$pid);
        $qty = trim((string)($_POST['quantity'][$i] ?? ''));
        if ($pid === '' && $qty === '') continue;                    // empty row, skip
        $product = ctype_digit($pid) ? ($productsById[(int)$pid] ?? null) : null;
        if ($product === null) {
            $rowError = true;
            $errors[] = "Each product row needs a product and a quantity of at least one.";
            continue;
        }
        if (!FormHelper::attempt($errors, fn() => $order->addItem($product, $qty))) $rowError = true;
    }
    if (!$order->getItems() && !$rowError) $errors[] = "Add at least one product.";

    // ---- Payment ----
    $payment = new Payment();
    FormHelper::attempt($errors, fn() => $payment->setTotal($_POST['total_amount'] ?? ''));
    FormHelper::attempt($errors, fn() => $payment->setDownPayment($_POST['down_payment'] ?? ''));
    FormHelper::attempt($errors, fn() => $payment->setStatus($_POST['payment_status'] ?? ''));
    FormHelper::attempt($errors, fn() => $payment->setMethod($_POST['payment_method'] ?? ''));
    FormHelper::attempt($errors, fn() => $payment->setDownPaymentStatus($_POST['down_payment_status'] ?? ''));
    $order->setPayment($payment);

    $errors = array_values(array_unique($errors));

    if (!$errors) {
        try {
            if ($id) {
                $orderRepo->update($id, $order);     // saves everything or nothing
                $_SESSION['msg'] = "Order updated.";
            } else {
                $orderRepo->create($order);          // saves everything or nothing
                $_SESSION['msg'] = "Order saved.";
            }
            header("Location: orders.php");
            exit;
        } catch (Exception $e) {
            $errors[] = "Could not save the order. Check the form and try again.";
        }
    }
}

$rows = [];
if (!empty($form['product_id']) && is_array($form['product_id'])) {
    foreach ($form['product_id'] as $i => $pid) $rows[] = [$pid, $form['quantity'][$i] ?? ''];
} else {
    $rows[] = ['', ''];
}
$pageTitle = $id ? 'Edit Order #' . $id : 'Add Order';
include 'header.php';
?>
    <h1><?= $id ? 'Edit Order #' . $id : 'Add Order' ?></h1>

    <?php if ($errors): ?>
        <div class="error"><strong>Please fix these:</strong>
            <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="POST" class="card">
        <h3>Customer</h3>
        <div class="field">
            <label>Existing customer</label>
            <select name="customer_id">
                <option value="">-- New customer --</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?= $c->getId() ?>" <?= sel('customer_id', $c->getId()) ?>>
                        <?= htmlspecialchars($c->getName()) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <small>If new customer:</small>
        <div class="grid" style="margin-top:8px">
            <div class="field"><input name="new_name" placeholder="Name" value="<?= old('new_name') ?>"></div>
            <div class="field"><input name="new_contact" inputmode="numeric" data-digits maxlength="12" placeholder="Contact number" value="<?= old('new_contact') ?>"></div>
            <div class="field"><input name="new_address" placeholder="Address" value="<?= old('new_address') ?>"></div>
        </div>

        <h3>Order</h3>
        <div class="grid">
            <div class="field"><label>Order date</label>
                <input type="date" name="order_date" value="<?= old('order_date', date('Y-m-d')) ?>"></div>
            <div class="field"><label>Delivery date</label>
                <input type="date" name="delivery_date" value="<?= old('delivery_date') ?>"></div>
            <div class="field"><label>Delivery time</label>
                <input type="time" name="delivery_time" value="<?= old('delivery_time') ?>"></div>
            <div class="field"><label>Pre-order (pastries only)</label>
                <select name="preorder_id">
                    <option value="">None</option>
                    <?php foreach ($preorders as $p): ?>
                        <option value="<?= $p['preorder_id'] ?>" <?= sel('preorder_id', $p['preorder_id']) ?>><?= htmlspecialchars($p['preorder_date']) ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div class="field"><label>Status</label>
                <select name="order_status">
                    <?php foreach (Order::STATUSES as $s): ?>
                        <option <?= sel('order_status', $s) ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div class="field"><label>Shipping fee</label>
                <input type="number" step="any" min="0" name="shipping_fee" value="<?= old('shipping_fee') ?>"></div>
        </div>
        <label class="check"><input type="checkbox" name="is_customized" <?= isset($form['is_customized']) ? 'checked' : '' ?>> Customized cake</label>
        <label class="check"><input type="checkbox" name="terms_given" <?= isset($form['terms_given']) ? 'checked' : '' ?>> Terms and conditions told</label>
        <div class="field"><label>Cake design (for customized cake)</label>
            <textarea name="design_description" rows="3"><?= old('design_description') ?></textarea></div>

        <h3>Products</h3>
        <div id="rows">
            <?php foreach ($rows as [$pid, $qty]) productRow($products, $pid, $qty); ?>
        </div>
        <button type="button" class="btn-secondary btn-small" onclick="addRow()">+ Add product</button>

        <h3>Payment</h3>
        <div class="grid">
            <div class="field"><label>Total amount</label>
                <input type="number" step="any" min="0" name="total_amount" value="<?= old('total_amount') ?>"></div>
            <div class="field"><label>Payment status</label>
                <select name="payment_status">
                    <?php foreach (Payment::STATUSES as $s): ?>
                        <option <?= sel('payment_status', $s) ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div class="field"><label>Payment method</label>
                <select name="payment_method">
                    <?php foreach (Payment::METHODS as $s): ?>
                        <option <?= sel('payment_method', $s) ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div class="field"><label>Down payment</label>
                <input type="number" step="any" min="0" name="down_payment" value="<?= old('down_payment') ?>"></div>
            <div class="field"><label>Down payment status</label>
                <select name="down_payment_status">
                    <?php foreach (Payment::DOWN_STATUSES as $s): ?>
                        <option <?= sel('down_payment_status', $s) ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select></div>
        </div>

        <div class="actions-terms">
            <div class="actions">
                <button type="submit"><?= $id ? 'Save changes' : 'Save order' ?></button>
                <a href="orders.php" class="btn btn-secondary">Cancel</a>
            </div>
            <div class="terms">
                <strong>Terms and conditions</strong>
                <ul>
                    <li>Cake orders need a down payment of half the price.</li>
                    <li>The full payment (the balance) is due upon successful delivery.</li>
                    <li>If the order is cancelled in the last hour before delivery, we keep half of the down payment.</li>
                </ul>
            </div>
        </div>
    </form>

    <template id="rowTemplate"><?php productRow($products, '', ''); ?></template>

<?php include 'footer.php'; ?>
