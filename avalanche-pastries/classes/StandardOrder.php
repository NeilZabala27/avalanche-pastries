<?php
// A normal pastry order: not from a pre-order and without a cake design.
class StandardOrder extends Order
{
    public function getType(): string
    {
        return 'Standard order';
    }

    public function getSummary(): string
    {
        return $this->describeBase();
    }
}
