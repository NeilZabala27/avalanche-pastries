<?php
// A pre-order date the owner posts. Many orders can belong to one pre-order date.
// (Not the same as the PreOrder class, which is a kind of Order.)
class PreorderBatch
{
    private ?int $id = null;
    private string $date = '';
    private int $orderCount = 0;

    public static function fromRow(array $row): self
    {
        $batch = new self();
        $batch->id = (int)$row['preorder_id'];
        $batch->date = (string)$row['preorder_date'];
        $batch->orderCount = (int)($row['order_count'] ?? 0);
        return $batch;
    }

    public function getId(): ?int { return $this->id; }
    public function getDate(): string { return $this->date; }
    public function getOrderCount(): int { return $this->orderCount; }

    // Used by PreorderBatchRepository after a new date is inserted.
    public function setId(int $id): void { $this->id = $id; }

    public function setDate(string $date): void
    {
        $date = trim($date);
        $d = DateTime::createFromFormat('Y-m-d', $date);
        if ($date === '' || !$d || $d->format('Y-m-d') !== $date) {
            throw new InvalidArgumentException('Enter a valid pre-order date.');
        }
        $this->date = $date;
    }
}
