<?php
// ONE Payment class for the whole system. The order form fills it in with the setters,
// and OrderRepository saves it together with the order.
class Payment
{
    public const STATUSES      = ['Unpaid', 'Partially paid', 'Paid'];
    public const METHODS       = ['Cash', 'GCash', 'Bank transfer'];
    public const DOWN_STATUSES = ['Not paid', 'Paid'];

    // ENCAPSULATION: private, changed only through the checked setters.
    private ?int $orderId = null;
    private ?float $total = null;
    private float $downPayment = 0.0;
    private string $status = 'Unpaid';
    private string $method = 'Cash';
    private string $downPaymentStatus = 'Not paid';

    // Builds a Payment from a database row (data already stored, so no re-validation).
    public static function fromRow(array $row): self
    {
        $p = new self();
        $p->orderId = isset($row['order_id']) ? (int)$row['order_id'] : null;
        $p->total = isset($row['total_amount']) ? (float)$row['total_amount'] : null;
        $p->status = (string)($row['payment_status'] ?? 'Unpaid');
        $p->method = (string)($row['payment_method'] ?? 'Cash');
        $p->downPayment = (float)($row['down_payment'] ?? 0);
        $p->downPaymentStatus = (string)($row['down_payment_status'] ?? 'Not paid');
        return $p;
    }

    // ---- getters ----
    public function getOrderId(): ?int { return $this->orderId; }
    public function getTotal(): ?float { return $this->total; }
    public function getTotalAmount(): float { return $this->total ?? 0.0; }
    public function getDownPayment(): float { return $this->downPayment; }
    public function getStatus(): string { return $this->status; }
    public function getMethod(): string { return $this->method; }
    public function getDownPaymentStatus(): string { return $this->downPaymentStatus; }

    // ---- validating setters ----
    public function setTotal($value): void
    {
        $value = trim((string)$value);
        if ($value === '' || !is_numeric($value) || (float)$value < 0) {
            throw new InvalidArgumentException('Enter the total amount (0 or more).');
        }
        $this->total = round((float)$value, 2);
    }

    public function setDownPayment($value): void
    {
        $value = trim((string)$value);
        if ($value === '') $value = '0';
        if (!is_numeric($value) || (float)$value < 0) {
            throw new InvalidArgumentException('Down payment must be 0 or more.');
        }
        if ($this->total !== null && (float)$value > $this->total) {
            throw new InvalidArgumentException('Down payment cannot be more than the total amount.');
        }
        $this->downPayment = round((float)$value, 2);
    }

    public function setStatus(string $status): void
    {
        if (!in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException('Choose a valid payment status.');
        }
        $this->status = $status;
    }

    public function setMethod(string $method): void
    {
        if (!in_array($method, self::METHODS, true)) {
            throw new InvalidArgumentException('Payment method must be cash, GCash or bank transfer.');
        }
        $this->method = $method;
    }

    public function setDownPaymentStatus(string $status): void
    {
        if (!in_array($status, self::DOWN_STATUSES, true)) {
            throw new InvalidArgumentException('Choose a valid down payment status.');
        }
        $this->downPaymentStatus = $status;
    }

    // How much the customer still has to pay.
    public function getBalanceDue(): float
    {
        $total = $this->total ?? 0.0;
        if ($this->status === 'Paid') {
            return 0.0;
        }
        $paid = $this->downPaymentStatus === 'Paid' ? $this->downPayment : 0.0;
        return max(0.0, $total - $paid);
    }

    public function getBalance(): float
    {
        return $this->getBalanceDue();
    }
}
