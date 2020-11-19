<?php


namespace makadev\RE2DFA\EdgeListGraph;

use Generator;
use SplDoublyLinkedList;

/**
 * Class ELGEdgeList
 *
 * @package makadev\RE2DFA\EdgeListGraph
 */
class ELGEdgeList {

    /**
     * internal edge list
     *
     * @var SplDoublyLinkedList<ELGEdge>
     */
    private SplDoublyLinkedList $internalList;

    /**
     * ELGEdgeList constructor.
     */
    public function __construct() {
        $this->internalList = new SplDoublyLinkedList();
    }

    /**
     * Copy edges from another Edge List, possibly rebasing (when merging graphs into a common node space) nodes.
     *
     * @param ELGEdgeList $other
     * @param int $rebase node id rebase value
     * @param bool $append whether to check for collisions or simply append the edges without collision test
     * @param bool $safe whether internal list iteration should be safe against mutation or not,
     * for concurrent iterations this MUST be true or it will result in endless loops or unexpected behavior
     */
    public function copyFrom(ELGEdgeList $other, int $rebase = 0, bool $append = true, bool $safe = true): void {
        $other->each(fn(ELGEdge $edge) => $this->add($edge->edgeClone($rebase), $append, $safe));
    }

    /**
     * execute visitor function $fn for each edge
     *
     * @param callable(ELGEdge): bool $fn visitor function, should return true to continue visiting or false to stop
     * @param bool $safe whether internal list iteration should be safe against mutation or not,
     * for concurrent iterations this MUST be true or it will result in endless loops or unexpected behavior
     * @return bool returns true if visitor was executed for each elements, otherwise false (when visitor returned false)
     */
    public function each(callable $fn, bool $safe = true): bool {
        foreach ($this->enumerator($safe) as $edge) {
            /**
             * @var ELGEdge $edge
             */
            $result = $fn($edge);
            if (!$result) {
                return false;
            }
        }
        return true;
    }

    /**
     * check if the given edge is colliding using edges testCollision() method
     *
     * @param ELGEdge $edge
     * @param bool $safe whether internal list iteration should be safe against mutation or not,
     * for concurrent iterations this MUST be true or it will result in endless loops or unexpected behavior
     * @return bool
     * @see ELGEdge::isColliding()
     *
     */
    public function isColliding(ELGEdge $edge, bool $safe = true): bool {
        return !$this->each(fn(ELGEdge $otherEdge) => !$edge->isColliding($otherEdge), $safe);
    }

    /**
     * add a new Edge
     *
     * @param ELGEdge $edge
     * @param bool $append whether to check for collisions or simply append the edge without collision test
     * @param bool $safe whether internal list iteration should be safe against mutation or not,
     * for concurrent iterations this MUST be true or it will result in endless loops or unexpected behavior
     * @return bool true if edge was added, false if it was colliding with another edge
     */
    public function add(ELGEdge $edge, bool $append = true, bool $safe = true): bool {
        if (!$append && !$this->isEmpty()) {
            if ($this->isColliding($edge, $safe)) {
                return false;
            }
        }
        $this->internalList->push($edge);
        return true;
    }

    /**
     * check if edge list is empty or not
     *
     * @return bool
     */
    public function isEmpty(): bool {
        return $this->internalList->isEmpty();
    }

    /**
     * get number of edges
     *
     * @return int
     */
    public function count(): int {
        return $this->internalList->count();
    }

    /**
     * return an enumerator (generator) which yields each edge
     *
     * @param bool $safe whether internal list iteration should be safe against mutation or not,
     * for concurrent iterations this MUST be true or it will result in endless loops or unexpected behavior
     * @return Generator<ELGEdge>
     */
    public function enumerator(bool $safe = true): Generator {
        $list = $this->internalList;
        if ($safe) {
            // create a full copy of the internal list, making the iteration/enumeration run on an immutable copy
            $newList = new SplDoublyLinkedList();
            $list->rewind();
            while ($list->valid()) {
                /**
                 * @var ELGEdge $edge
                 */
                $edge = clone $list->current();
                $newList->push($edge->edgeClone());
                $list->next();
            }
        }
        for ($list->rewind(); $list->valid(); $list->next()) {
            yield $list->current();
        }
    }
}