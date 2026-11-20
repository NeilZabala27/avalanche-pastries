<?php
// ABSTRACTION: this only says "a sort strategy can compare two ingredients".
// The merge sort uses it without knowing which field is being compared.
interface SortStrategy {
    // Returns a negative number if $a comes first, 0 if equal, positive if $b comes first
    public function compare(Ingredient $a, Ingredient $b);
}
