<?php
class QuantitySort implements SortStrategy {
    public function compare(Ingredient $a, Ingredient $b) {
        return $a->getStockQuantity() <=> $b->getStockQuantity();
    }
}
