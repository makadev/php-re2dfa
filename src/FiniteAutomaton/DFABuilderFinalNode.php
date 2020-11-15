<?php


namespace makadev\RE2DFA\FiniteAutomaton;


class DFABuilderFinalNode {

    /**
     * whether this node is connected to multiple EpsilonNFA or not
     *
     * @var bool
     */
    private bool $connected;

    /**
     * the name (or f.e. token identifier) for this final state
     *
     * @var string
     */
    protected string $finalName;

    /**
     * the actual final state node
     *
     * @var int
     */
    protected int $node;

    /**
     * DFABuilderFinalNode constructor.
     *
     * @param int $node
     * @param string $finalName
     */
    public function __construct(int $node, string $finalName) {
        $this->node = $node;
        $this->finalName = $finalName;
        $this->connected = false;
    }

    /**
     * get final state name
     *
     * @return string
     */
    public function getFinalName(): string {
        return $this->finalName;
    }

    /**
     * get final state node
     *
     * @return int
     */
    public function getNode(): int {
        return $this->node;
    }

    /**
     * get connected state
     *
     * @return bool
     */
    public function getConnected(): bool {
        return $this->connected;
    }

    /**
     * set connected state and update the node
     *
     * @param int $newFinalStateNode
     */
    public function setConnected(int $newFinalStateNode): void {
        $this->connected = true;
        $this->node = $newFinalStateNode;
    }
}