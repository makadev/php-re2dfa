<?php


namespace makadev\RE2DFA\FiniteAutomaton;


use makadev\RE2DFA\CharacterSet\AlphaSet;
use makadev\RE2DFA\EdgeListGraph\IEdgePayload;

class AlphaSetPayload implements IEdgePayload {

    /**
     *
     * @var AlphaSet
     */
    private AlphaSet $alphaSet;

    /**
     * AlphaSetPayload constructor.
     *
     * @param AlphaSet|null $alphaSet
     */
    public function __construct(?AlphaSet $alphaSet = null) {
        $this->alphaSet = $alphaSet ?? new AlphaSet();
    }

    /**
     *
     * @param IEdgePayload $other
     * @return bool
     */
    public function isColliding(IEdgePayload $other): bool {
        assert($other instanceof AlphaSetPayload);
        return !$this->alphaSet->isDisjoint($other->alphaSet);
    }

    /**
     *
     *
     * @return IEdgePayload
     */
    public function copy(): IEdgePayload {
        return new AlphaSetPayload(clone $this->alphaSet);
    }

    /**
     *
     * @return AlphaSet
     */
    public function getAlphaSet(): AlphaSet {
        return $this->alphaSet;
    }

    /**
     *
     * @param AlphaSet $alphaSet
     */
    public function setAlphaSet(AlphaSet $alphaSet): void {
        $this->alphaSet = $alphaSet;
    }
}