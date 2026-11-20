<?php
// The searching, sorting, and alert algorithms (array, linear search, merge sort, min-heap)
class InventoryAlgorithms {

    // Linear search: checks every ingredient and keeps names that contain the keyword
    public static function searchByName($ingredients, $keyword) {
        $result = [];
        for ($i = 0; $i < count($ingredients); $i++) {
            if (stripos($ingredients[$i]->getName(), $keyword) !== false) {
                $result[] = $ingredients[$i];
            }
        }
        return $result;
    }

    // Merge sort. The SortStrategy decides what "smaller" means (by name or by quantity).
    public static function mergeSort($items, SortStrategy $strategy) {
        if (count($items) <= 1) return $items;
        $mid = intdiv(count($items), 2);
        $left = self::mergeSort(array_slice($items, 0, $mid), $strategy);
        $right = self::mergeSort(array_slice($items, $mid), $strategy);

        $out = [];
        $i = 0;
        $j = 0;
        while ($i < count($left) && $j < count($right)) {
            if ($strategy->compare($left[$i], $right[$j]) <= 0) {
                $out[] = $left[$i++];
            } else {
                $out[] = $right[$j++];
            }
        }
        while ($i < count($left)) $out[] = $left[$i++];
        while ($j < count($right)) $out[] = $right[$j++];
        return $out;
    }

    // Min-heap: puts every low or out-of-stock ingredient in the heap, then takes them out
    // one by one, so the list comes out with the lowest stock first.
    public static function lowStockAlerts($ingredients) {
        $heap = new MinHeap();
        foreach ($ingredients as $ingredient) {
            if ($ingredient->isLowOrOut()) $heap->push($ingredient);
        }
        $alerts = [];
        while (($next = $heap->pop()) !== null) {
            $alerts[] = $next;
        }
        return $alerts;
    }
}
