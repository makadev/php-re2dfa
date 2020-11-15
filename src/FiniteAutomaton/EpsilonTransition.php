<?php


namespace makadev\RE2DFA\FiniteAutomaton;


use makadev\RE2DFA\EdgeListGraph\ELGEdge;

class EpsilonTransition extends ELGEdge {

    /**
     * create a transition copy
     *
     * @param int $nodeRebase
     * @return EpsilonTransition
     */
    public function edgeClone(int $nodeRebase = 0): EpsilonTransition {
        return new EpsilonTransition($this->fromNode + $nodeRebase, $this->toNode + $nodeRebase);
    }
}
