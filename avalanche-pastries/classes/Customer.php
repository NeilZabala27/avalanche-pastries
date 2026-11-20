<?php
// INHERITANCE: Customer extends Person and reuses getName() / setName().
class Customer extends Person
{
    private ?int $id = null;
    private string $address = '';
    private string $contactNumber = '';
    private int $orderCount = 0;

    // Builds a Customer from a database row (data already stored, so no re-validation).
    public static function fromRow(array $row): self
    {
        $customer = new self();
        $customer->id = isset($row['customer_id']) ? (int)$row['customer_id'] : null;
        $customer->loadName((string)$row['customer_name']);
        $customer->address = (string)($row['address'] ?? '');
        $customer->contactNumber = (string)($row['contact_number'] ?? '');
        $customer->orderCount = (int)($row['order_count'] ?? 0);
        return $customer;
    }

    public function getId(): ?int { return $this->id; }
    public function getOrderCount(): int { return $this->orderCount; }

    // Used by CustomerRepository after a new customer is inserted.
    public function setId(int $id): void { $this->id = $id; }

    public function getAddress(): string { return $this->address; }
    public function setAddress(string $address): void
    {
        $this->address = trim($address);
    }

    public function getContactNumber(): string { return $this->contactNumber; }
    public function setContactNumber(string $contactNumber): void
    {
        $contactNumber = trim($contactNumber);
        if ($contactNumber === '') {
            throw new InvalidArgumentException('Contact number is required.');
        }
        $digits = preg_replace('/\D/u', '', $contactNumber);
        if (!preg_match('/^(09|639)\d{9}$/', $digits)) {
            throw new InvalidArgumentException('Enter a valid mobile number, like 0912 345 6789.');
        }
        $this->contactNumber = $digits;
    }

    // Required by the abstract method in Person.
    public function getRole(): string
    {
        return 'Customer';
    }

    // Changes the wording of the error from Person::setName().
    protected function nameLabel(): string
    {
        return 'Customer name';
    }
}
