<?php


namespace makadev\RE2DFA\CharacterSet;

use Generator;
use SplDoublyLinkedList;
use SplQueue;

class DisjointAlphaSets {

    /**
     * Disjoint AlphaSets list
     *
     * @var SplQueue<AlphaSet>
     */
    private SplQueue $disjointAlphaSets;

    /**
     *
     *
     * @var SplQueue<AlphaSet>
     */
    private SplQueue $insertionQueue;

    private static ?AlphaSet $emptySet = null;

    private static function getEmptySet(): AlphaSet {
        if (static::$emptySet === null) {
            static::$emptySet = new AlphaSet();
        }
        return static::$emptySet;
    }

    public function __construct() {
        $this->disjointAlphaSets = new SplQueue();
        $this->insertionQueue = new SplQueue();
    }

    public function isEmpty(): bool {
        return $this->disjointAlphaSets->isEmpty();
    }

    public function addAlpha(AlphaSet $alphaSet): void {
        if ($alphaSet->isEmpty()) return;
        $insertionSet = clone $alphaSet;
        while (!$this->disjointAlphaSets->isEmpty()) {
            /**
             * @var AlphaSet $currentSet
             */
            $currentSet = $this->disjointAlphaSets->dequeue();
            // test 1.: set is already fully contained
            if ($insertionSet->equals($currentSet)) {
                $this->insertionQueue->enqueue($currentSet);
                $insertionSet = static::getEmptySet();
                break;
            }
            // test 2.: skip if disjoint
            if ($insertionSet->isDisjoint($currentSet)) {
                $this->insertionQueue->enqueue($currentSet);
                continue;
            }
            // 3.: otherwise break the sets apart into:
            //  - a common part (nonempty since both sets where not disjoint in test 2)
            //    and already known to be disjoint since it's in the disjoint set
            //  - the rest of the current set which might be empty if insertionSet contains currentSet
            //  - the rest of the insertion which might be empty if currentSet contains insertionSet
            //    and needs to be checked against the other sets if not empty
            $newInsertion = $insertionSet->subtract($currentSet);
            $common = $insertionSet->intersect($currentSet);
            $currentSet = $currentSet->subtract($insertionSet);
            assert(!$common->isEmpty());
            $this->insertionQueue->enqueue($common);
            if (!$currentSet->isEmpty()) {
                $this->insertionQueue->enqueue($currentSet);
            }
            if (!$newInsertion->isEmpty()) {
                break;
            }
            $insertionSet = $newInsertion;
        }
        // check whether to merge it back into the list or switch the lists
        if ($this->disjointAlphaSets->isEmpty() && ($this->insertionQueue->count() > 0)) {
            $tmp = $this->disjointAlphaSets;
            $this->disjointAlphaSets = $this->insertionQueue;
            $this->insertionQueue = $tmp;
        } else {
            if ($this->insertionQueue->count() > $this->disjointAlphaSets->count()) {
                // add newly generated disjoint sets back to the disjoint AlphaSets
                while (!$this->insertionQueue->isEmpty()) {
                    $this->disjointAlphaSets->push($this->insertionQueue->dequeue());
                }
            } else {
                // merge everything in the insertion list and exchange them
                while (!$this->disjointAlphaSets->isEmpty()) {
                    $this->insertionQueue->push($this->disjointAlphaSets->dequeue());
                }
                $tmp = $this->disjointAlphaSets;
                $this->disjointAlphaSets = $this->insertionQueue;
                $this->insertionQueue = $tmp;
            }
        }
        // add disjoint rest
        if (!$insertionSet->isEmpty()) {
            $this->disjointAlphaSets->push($insertionSet);
        }
    }

    /**
     * Return a Generator which enumerates the current AlphaSets in the list
     *
     * @param bool $safe
     * @return Generator<AlphaSet>
     */
    public function enumerator(bool $safe = true): Generator {
        $list = $this->disjointAlphaSets;
        if ($safe) {
            // create a full copy of the internal list, making the iteration/enumeration run on an immutable copy
            $newList = new SplDoublyLinkedList();
            $list->rewind();
            while ($list->valid()) {
                /**
                 * @var AlphaSet $alphaSet
                 */
                $alphaSet = clone $list->current();
                $newList->push(clone $alphaSet);
                $list->next();
            }
        }
        for ($list->rewind(); $list->valid(); $list->next()) {
            yield $list->current();
        }
    }
}