<?php
// A customized cake. It has a design and is never part of a pre-order (pre-orders are for pastries only).
class CustomizedCakeOrder extends Order
{
    private string $designDescription = '';

    // Overrides Order::isCustomized().
    public function isCustomized(): bool
    {
        return true;
    }

    public function getType(): string
    {
        return 'Customized cake order';
    }

    public function getSummary(): string
    {
        $text = $this->designDescription;
        if (strlen($text) > 50) {
            $text = substr($text, 0, 47) . '...';
        }
        return $this->describeBase() . ', design: ' . ($text !== '' ? $text : 'not described');
    }

    public function getDesignDescription(): string
    {
        return $this->designDescription;
    }

    public function setDesignDescription(string $description): void
    {
        $description = trim($description);
        if ($description === '') {
            throw new InvalidArgumentException('Describe the cake design for a customized cake.');
        }
        $this->designDescription = $description;
    }

    // A customized cake never belongs to a pre-order, even if one was picked on the form.
    public function setPreorderId(?int $id): void
    {
        parent::setPreorderId(null);
    }

    // Loads the saved design when the order is read from the database.
    protected function hydrate(array $row): void
    {
        $this->designDescription = trim((string)($row['design_description'] ?? ''));
    }
}
