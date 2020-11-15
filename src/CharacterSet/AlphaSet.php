<?php


namespace makadev\RE2DFA\CharacterSet;

use makadev\BitSet\BitSet;

class AlphaSet {
    /**
     * Internal BitSet
     *
     * @var BitSet
     */
    private BitSet $bitSet;

    /**
     * Internal Empty State, might be null if it needs to be recalculated by scanning the bitmap
     *
     * @var bool|null
     */
    private ?bool $empty;

    /**
     * AlphaSet constructor.
     *
     * Initialize the bitmap and empty state.
     */
    public function __construct() {
        $this->bitSet = new BitSet(256);
        $this->empty = true;
    }

    /**
     * Explicit copy the internal bitmap when cloning
     *
     */
    public function __clone() {
        $this->bitSet = clone $this->bitSet;
    }

    /**
     * Set bit/char position in alphabet set
     *
     * @param int $ordinalChar 0-255
     */
    public function set(int $ordinalChar): void {
        $this->bitSet->set($ordinalChar);
        $this->empty = false;
    }

    /**
     * Unset bit/char position in alphabet set
     *
     * @param int $ordinalChar 0-255
     */
    public function unset(int $ordinalChar): void {
        $wasSet = $this->bitSet->unset($ordinalChar);
        if ($wasSet) $this->empty = null;
    }

    /**
     * Check if bi/character position is set
     *
     * @param int $ordinalChar 0-255
     * @return bool
     */
    public function test(int $ordinalChar): bool {
        return $this->bitSet->test($ordinalChar);
    }

    /**
     * Set a Range of Characters
     *
     * @param int $startChar 0-255
     * @param int $endChar 0-255
     * @return bool
     */
    public function setRange(int $startChar, int $endChar): bool {
        $res = $this->bitSet->setRange($startChar, $endChar);
        $this->empty = !($startChar <= $endChar);
        return $res;
    }

    /**
     * Check that this set and other don't have any common elements
     *
     * @param AlphaSet $other
     * @return bool
     */
    public function isDisjoint(AlphaSet $other): bool {
        return $this->bitSet->isDisjoint($other->bitSet);
    }

    /**
     * Check if this set is empty
     *
     * @return bool
     */
    public function isEmpty(): bool {
        if ($this->empty !== null) return $this->empty;
        $empty = $this->bitSet->isEmpty();
        $this->empty = $empty;
        return $empty;
    }

    /**
     * Check if this set contains the other
     *
     * @param AlphaSet $other
     * @return bool
     */
    public function contains(AlphaSet $other): bool {
        return $this->bitSet->contains($other->bitSet);
    }

    /**
     * Check that this set is equal to the other
     *
     * @param AlphaSet $other
     * @return bool
     */
    public function equals(AlphaSet $other): bool {
        return $this->bitSet->equals($other->bitSet);
    }

    /**
     * Calculate the Union set C = A + B where C contains all elements which are in A or B.
     *
     * @param AlphaSet $other
     * @return AlphaSet
     */
    public function union(AlphaSet $other): AlphaSet {
        $result = clone $this;
        $result->bitSet->union($other->bitSet, true);
        $result->empty = null;
        return $result;
    }

    /**
     * Calculate the Intersection set C = A & B where C contains all elements which are in A and B.
     *
     * @param AlphaSet $other
     * @return AlphaSet
     */
    public function intersect(AlphaSet $other): AlphaSet {
        $result = clone $this;
        $result->bitSet->intersect($other->bitSet, true);
        $result->empty = null;
        return $result;
    }

    /**
     * Calculate the set substraction C = A - B where the resulting C contains all elements from A which are not in B.
     *
     * @param AlphaSet $other
     * @return AlphaSet
     */
    public function subtract(AlphaSet $other): AlphaSet {
        $result = clone $this;
        $result->bitSet->subtract($other->bitSet, true);
        $result->empty = null;
        return $result;
    }

    /**
     * Calculate the set Complement
     *
     * @return AlphaSet
     */
    public function complement(): AlphaSet {
        $result = clone $this;
        $result->bitSet->complement(true);
        $result->empty = $this->isEmpty() ? false : null;
        return $result;
    }

    /**
     * return string presentation of alpha set
     *
     * @return string
     */
    public function __toString(): string {
        $printable = new BitSet($this->bitSet->getBitLength());
        $printable->setRange(0x20, 0x7E);
        $charToString = fn(int $ord) => $printable->test($ord) ? chr($ord) : ('#' . str_pad(strval($ord), 3, "0", STR_PAD_LEFT));
        $result = "[";
        $first = true;
        $startRange = -1;
        $stopRange = -1;
        $charPos = 0;
        do {
            if ($this->test($charPos)) {
                // find end of range
                $startRange = $charPos;
                do {
                    $charPos++;
                } while ($charPos < $this->bitSet->getBitLength() && $this->test($charPos));
                $stopRange = $charPos - 1;
                if ($charPos >= $this->bitSet->getBitLength()) {
                    // reset to last char out of range
                    $charPos--;
                }
                // convert char/char range to string
                if ($startRange === $stopRange) {
                    $result .= ($first ? '' : ',') . $charToString($startRange);
                } else {
                    $result .= ($first ? '' : ',') . $charToString($startRange) . '..' . $charToString($stopRange);
                }
                $first = false;
            }
            $charPos++;
        } while ($charPos < $this->bitSet->getBitLength());
        return $result . "]";
    }
}