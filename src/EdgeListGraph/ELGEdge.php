<?php


namespace makadev\RE2DFA\EdgeListGraph;

/**
 * Edge List Graph - Edge
 * Edge for a typical graph implementation with a full edge list, containing source/target nodes
 * and edge data (payload).
 *
 * @package makadev\RE2DFA\EdgeListGraph
 */
class ELGEdge {
    /**
     * source node id
     *
     * @var int node id
     */
    protected int $fromNode;

    /**
     * target node id
     *
     * @var int NodeID
     */
    protected int $toNode;

    /**
     * edge data/weight/guard/..
     *
     * @var IEdgePayload|null
     */
    protected ?IEdgePayload $payload;

    /**
     * constructor
     *
     * @param int $fromNode source node
     * @param int $toNode target node
     * @param IEdgePayload|null $payload payload
     */
    public function __construct(int $fromNode, int $toNode, ?IEdgePayload $payload = null) {
        $this->fromNode = $fromNode;
        $this->toNode = $toNode;
        $this->payload = $payload;
    }

    /**
     * get source node id
     *
     * @return int node id
     */
    public function getFromNode(): int {
        return $this->fromNode;
    }

    /**
     * set source node id
     *
     * @param int $fromNode node id
     */
    public function setFromNode(int $fromNode): void {
        $this->fromNode = $fromNode;
    }

    /**
     * get target node id
     *
     * @return int node id
     */
    public function getToNode(): int {
        return $this->toNode;
    }

    /**
     * set target node id
     *
     * @param int $toNode node id
     */
    public function setToNode(int $toNode): void {
        $this->toNode = $toNode;
    }

    /**
     * get payload
     *
     * @return IEdgePayload|null
     */
    public function getPayload(): ?IEdgePayload {
        return $this->payload;
    }

    /**
     * set payload
     *
     * @param IEdgePayload|null $payload
     */
    public function setPayload(?IEdgePayload $payload): void {
        $this->payload = $payload;
    }

    /**
     * specialized edge copy method
     *
     * @param int $nodeRebase
     * @return ELGEdge
     */
    public function edgeClone(int $nodeRebase = 0): ELGEdge {
        return new ELGEdge(
            $this->fromNode + $nodeRebase,
            $this->toNode + $nodeRebase,
            $this->payload === null ? null : $this->payload->copy());
    }

    /**
     * check if this and another edge are colliding (meaning same source and target node as well as same payload)
     *
     * @param ELGEdge $otherEdge
     * @return bool true if both edges collide, false otherwise
     */
    public function isColliding(ELGEdge $otherEdge): bool {
        if ($this->fromNode === $otherEdge->fromNode &&
            $this->toNode === $otherEdge->toNode) {
            if ($this->payload !== null && $otherEdge->payload !== null) {
                // both have payload, use payload collision check
                return $this->payload->isColliding($otherEdge->payload);
            }
            // colliding when both are null
            return $this->payload === $otherEdge->payload;
        }
        return false;
    }
}
