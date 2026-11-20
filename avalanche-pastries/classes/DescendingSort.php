<?php
// POLYMORPHISM: wraps another SortStrategy and turns its answer around, so the same
// merge sort can also sort from Z to A or from high to low without any change.
class DescendingSort implements SortStrategy {
    private $inner;

    public function __construct(SortStrategy $inner) {
        $this->inner = $inner;
    }

    public function compare(Ingredient $a, Ingredient $b) {
        return $this->inner->compare($b, $a);
    }
}
