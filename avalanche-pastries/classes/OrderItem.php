<?php
// One line of an order: a product and how many were ordered. Read-only once created.
// (Order::addItem() is the gatekeeper that checks the quantity and duplicates.)
class OrderItem
{
    private Product $product;
    private int $quantity;

    public function __construct(Product $product, int $quantity)
    {
        $this->product = $product;
        $this->quantity = $quantity;
    }

    public function getProduct(): Product { return $this->product; }
    public function getQuantity(): int { return $this->quantity; }
}
