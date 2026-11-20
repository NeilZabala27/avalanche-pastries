<?php
// An order that belongs to a pre-order batch (pastries only).
class PreOrder extends Order
{
    public function getType(): string
    {
        return 'Pre-order';
    }

    public function getSummary(): string
    {
        return $this->describeBase() . ', pre-order date ' . ($this->getPreorderDate() ?? 'not set');
    }
}
