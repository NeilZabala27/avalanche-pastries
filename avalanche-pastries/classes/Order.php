<?php
// ABSTRACT: you never create a plain "Order". You create a StandardOrder, a PreOrder
// or a CustomizedCakeOrder, and each one describes itself differently (polymorphism).
abstract class Order
{
    public const STATUSES = ['Not done', 'Done'];

    private ?int $id = null;
    private ?Customer $customer = null;
    private ?int $preorderId = null;
    private ?string $preorderDate = null;
    private string $orderDate = '';
    private string $deliveryDate = '';
    private ?string $deliveryTime = null;
    private string $status = 'Not done';
    private float $shippingFee = 0.0;
    private bool $termsGiven = false;
    private array $items = [];                 // OrderItem objects
    private ?Payment $payment = null;

    // ---- Creating orders -------------------------------------------------

    // Chooses the right kind of order.
    public static function forType(bool $isCustomized, ?int $preorderId): Order
    {
        if ($isCustomized) {
            return new CustomizedCakeOrder();
        }
        if ($preorderId !== null) {
            return new PreOrder();
        }
        return new StandardOrder();
    }

    // Builds an order from database rows (data already stored, so no re-validation).
    public static function fromRow(array $row, array $itemRows = []): Order
    {
        $preorderId = isset($row['preorder_id']) ? (int)$row['preorder_id'] : null;
        $order = self::forType((bool)($row['is_customized'] ?? false), $preorderId);

        $order->id = (int)$row['order_id'];
        $order->customer = Customer::fromRow($row);
        $order->preorderId = $preorderId;
        $order->preorderDate = $row['preorder_date'] ?? null;
        $order->orderDate = (string)($row['order_date'] ?? '');
        $order->deliveryDate = (string)($row['delivery_date'] ?? '');
        $order->deliveryTime = isset($row['delivery_time']) ? substr((string)$row['delivery_time'], 0, 5) : null;   // 10:30:00 -> 10:30
        $order->status = (string)($row['order_status'] ?? 'Not done');
        $order->shippingFee = (float)($row['shipping_fee'] ?? 0);
        $order->termsGiven = (bool)($row['terms_given'] ?? false);

        if (isset($row['total_amount'])) {
            $order->payment = Payment::fromRow($row);
        }
        foreach ($itemRows as $r) {
            $product = new Product((int)$r['product_id'], (string)$r['product_name']);
            $order->items[] = new OrderItem($product, (int)$r['quantity']);
        }
        $order->hydrate($row);                 // lets a subclass load its own extra data
        return $order;
    }

    // Subclasses override this when they have extra columns to load.
    protected function hydrate(array $row): void
    {
    }

    // ---- Getters ---------------------------------------------------------

    public function getId(): ?int { return $this->id; }
    public function getCustomer(): ?Customer { return $this->customer; }
    public function getPreorderId(): ?int { return $this->preorderId; }
    public function getPreorderDate(): ?string { return $this->preorderDate; }
    public function getOrderDate(): string { return $this->orderDate; }
    public function getDeliveryDate(): string { return $this->deliveryDate; }
    public function getDeliveryTime(): ?string { return $this->deliveryTime; }
    public function getStatus(): string { return $this->status; }
    public function getShippingFee(): float { return $this->shippingFee; }
    public function isTermsGiven(): bool { return $this->termsGiven; }
    public function getItems(): array { return $this->items; }
    public function getPayment(): ?Payment { return $this->payment; }

    public function getItemCount(): int
    {
        $count = 0;
        foreach ($this->items as $item) {
            $count += $item->getQuantity();
        }
        return $count;
    }

    // ---- Validating setters ---------------------------------------------

    public function setCustomer(Customer $customer): void { $this->customer = $customer; }
    public function setPreorderId(?int $id): void { $this->preorderId = $id; }
    public function setTermsGiven(bool $given): void { $this->termsGiven = $given; }
    public function setPayment(Payment $payment): void { $this->payment = $payment; }

    public function setOrderDate(string $date): void
    {
        $date = trim($date);
        if ($date === '') {
            throw new InvalidArgumentException('Order date is required.');
        }
        if (!self::isValidDate($date)) {
            throw new InvalidArgumentException('Enter a valid order date.');
        }
        $this->orderDate = $date;
    }

    public function setDeliveryDate(string $date): void
    {
        $date = trim($date);
        if ($date === '') {
            throw new InvalidArgumentException('Delivery date is required.');
        }
        if (!self::isValidDate($date)) {
            throw new InvalidArgumentException('Enter a valid delivery date.');
        }
        if ($this->orderDate !== '' && $date < $this->orderDate) {
            throw new InvalidArgumentException('Delivery date cannot be before the order date.');
        }
        $this->deliveryDate = $date;
    }

    public function setDeliveryTime(string $time): void
    {
        $time = trim($time);
        if ($time === '') {
            $this->deliveryTime = null;          // delivery time is optional
            return;
        }
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $time)) {
            throw new InvalidArgumentException('Enter a valid delivery time.');
        }
        $this->deliveryTime = $time;
    }

    public function setStatus(string $status): void
    {
        if (!in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException('Choose a valid order status.');
        }
        $this->status = $status;
    }

    public function setShippingFee($value): void
    {
        if ($value === null || $value === '') {
            $value = 0;                           // shipping fee is optional
        }
        if (!is_numeric($value) || $value < 0) {
            throw new InvalidArgumentException('Shipping fee must be 0 or more.');
        }
        $this->shippingFee = round((float)$value, 2);
    }

    // The order guards its own item list: quantity must be 1 or more, no product twice.
    public function addItem(Product $product, $quantity): void
    {
        $quantity = trim((string)$quantity);
        if (!ctype_digit($quantity) || (int)$quantity < 1) {
            throw new InvalidArgumentException('Each product row needs a product and a quantity of at least one.');
        }
        foreach ($this->items as $item) {
            if ($item->getProduct()->getId() === $product->getId()) {
                throw new InvalidArgumentException('The same product is listed twice. Put it in one row.');
            }
        }
        $this->items[] = new OrderItem($product, (int)$quantity);
    }

    // ---- What every kind of order must be able to do ---------------------

    // Not every order is a customized cake; CustomizedCakeOrder overrides this.
    public function isCustomized(): bool
    {
        return false;
    }

    abstract public function getType(): string;       // e.g. "Pre-order"
    abstract public function getSummary(): string;    // a one-line description

    // Shared first part of every summary.
    protected function describeBase(): string
    {
        $who = $this->customer ? $this->customer->getName() : 'unknown customer';
        $when = trim($this->deliveryDate . ' ' . ($this->deliveryTime ?? ''));
        return 'Order #' . ($this->id ?? '?') . ' for ' . $who
            . ', deliver ' . $when . ', ' . $this->getItemCount() . ' item(s)';
    }

    private static function isValidDate(string $date): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d !== false && $d->format('Y-m-d') === $date;
    }
}
