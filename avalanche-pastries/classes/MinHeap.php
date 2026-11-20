<?php
// Min-heap of ingredients ordered by stock quantity: the lowest stock is always on top.
// The heap array is private, so it can only be changed through push() and pop().
class MinHeap {
    private $heap = [];

    private function quantity($i) {
        return $this->heap[$i]->getStockQuantity();
    }

    private function swap($a, $b) {
        $temp = $this->heap[$a];
        $this->heap[$a] = $this->heap[$b];
        $this->heap[$b] = $temp;
    }

    public function push(Ingredient $item) {
        $this->heap[] = $item;
        $i = count($this->heap) - 1;
        while ($i > 0) {
            $parent = intdiv($i - 1, 2);
            if ($this->quantity($parent) <= $this->quantity($i)) break;
            $this->swap($parent, $i);
            $i = $parent;
        }
    }

    public function pop() {
        if (count($this->heap) === 0) return null;
        $top = $this->heap[0];
        $last = array_pop($this->heap);
        if (count($this->heap) > 0) {
            $this->heap[0] = $last;
            $i = 0;
            $n = count($this->heap);
            while (true) {
                $left = 2 * $i + 1;
                $right = $left + 1;
                $smallest = $i;
                if ($left < $n && $this->quantity($left) < $this->quantity($smallest)) $smallest = $left;
                if ($right < $n && $this->quantity($right) < $this->quantity($smallest)) $smallest = $right;
                if ($smallest === $i) break;
                $this->swap($smallest, $i);
                $i = $smallest;
            }
        }
        return $top;
    }
}
