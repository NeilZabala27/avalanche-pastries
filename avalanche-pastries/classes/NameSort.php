<?php
// POLYMORPHISM: same method name (compare), different behavior in each class
class NameSort implements SortStrategy {
    public function compare(Ingredient $a, Ingredient $b) {
        return strcmp(strtolower($a->getName()), strtolower($b->getName()));
    }
}
