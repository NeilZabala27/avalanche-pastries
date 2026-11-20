<?php
// A pastry the business sells. ENCAPSULATION: every field is private and changes only through a checked setter.
class Product
{
    // The quality levels offered on the Add / Edit Product form (a dropdown). Add a word here to offer a new one.
    public const QUALITY_LEVELS = ['Standard', 'Premium'];

    private ?int $id;
    private string $name = '';
    private string $flavor = '';
    private string $size = '';
    private string $qualityLevel = '';
    private float $price = 0.0;

    // Same constructor as before, so the order classes keep working. A new product has no id yet.
    public function __construct(?int $id = null, string $name = '')
    {
        $this->id = $id;
        $this->name = $name;
    }

    // Builds a Product from a database row (data already stored, so no re-validation).
    public static function fromRow(array $row): self
    {
        $product = new self((int)$row['product_id'], (string)$row['product_name']);
        $product->flavor = (string)($row['flavor'] ?? '');
        $product->size = (string)($row['size'] ?? '');
        $product->qualityLevel = (string)($row['quality_level'] ?? '');
        $product->price = (float)($row['price'] ?? 0);
        return $product;
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getFlavor(): string { return $this->flavor; }
    public function getSize(): string { return $this->size; }
    public function getQualityLevel(): string { return $this->qualityLevel; }
    public function getPrice(): float { return $this->price; }

    // Used by ProductRepository after a new product is inserted.
    public function setId(int $id): void { $this->id = $id; }

    public function setName(string $name): void
    {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('Product name is required.');
        }
        if (strlen($name) > 100) {
            throw new InvalidArgumentException('Product name must be 100 characters or less.');
        }
        $this->name = $name;
    }

    // Flavor and size are typed (optional). Quality level is a choice (optional) and must be one of the list.
    public function setFlavor(string $flavor): void { $this->flavor = self::shortText($flavor, 'Flavor'); }
    public function setSize(string $size): void { $this->size = self::shortText($size, 'Size'); }
    public function setQualityLevel(string $level): void
    {
        $this->qualityLevel = self::choice($level, self::QUALITY_LEVELS, $this->qualityLevel, 'Choose a quality level from the list.');
    }

    public function setPrice($value): void
    {
        $value = trim((string)$value);
        if ($value === '' || !is_numeric($value) || (float)$value < 0) {
            throw new InvalidArgumentException('Price must be a number, 0 or more.');
        }
        $this->price = round((float)$value, 2);
    }

    // For dropdowns: "Muffin - Chocolate, Regular"
    public function getLabel(): string
    {
        $extra = array_filter([$this->flavor, $this->size], fn($v) => $v !== '');
        return $this->name . ($extra ? ' - ' . implode(', ', $extra) : '');
    }

    private static function shortText(string $value, string $label): string
    {
        $value = trim($value);
        if (strlen($value) > 50) {
            throw new InvalidArgumentException($label . ' must be 50 characters or less.');
        }
        return $value;
    }

    // Accepts a value only if it is in $allowed. A value the product already has is also accepted,
    // so an older product saved before the lists existed can still be edited without changing it.
    private static function choice(string $value, array $allowed, string $current, string $message): string
    {
        $value = trim($value);
        if ($value !== '' && !in_array($value, $allowed, true) && $value !== $current) {
            throw new InvalidArgumentException($message);
        }
        return $value;
    }
}
