<?php


namespace makadev\RE2DFA\FiniteAutomaton;


use makadev\RE2DFA\CharacterSet\AlphaSet;
use makadev\RE2DFA\EdgeListGraph\ELGEdge;

class AlphaTransition extends ELGEdge {

    /**
     * Get the alpha set for this transition
     *
     * @return AlphaSet
     */
    public function getAlphaSet(): AlphaSet {
        assert($this->payload !== null);
        assert($this->payload instanceof AlphaSetPayload);
        return $this->payload->getAlphaSet();
    }

    /**
     * Allow to update the AlphaSet
     *
     * @param AlphaSet $alphaSet
     */
    public function setAlphaSet(AlphaSet $alphaSet): void {
        assert($this->payload !== null);
        assert($this->payload instanceof AlphaSetPayload);
        $this->payload->setAlphaSet($alphaSet);
    }

    /**
     * create a (deep) transition copy
     *
     * @param int $nodeRebase
     * @return AlphaTransition
     */
    public function edgeClone(int $nodeRebase = 0): AlphaTransition {
        return new AlphaTransition($this->fromNode + $nodeRebase, $this->toNode + $nodeRebase, $this->payload->copy());
    }
}
