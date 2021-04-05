<?php


namespace makadev\RE2DFA\CLI;


use makadev\RE2DFA\CharacterSet\AlphaSet;
use makadev\RE2DFA\FiniteAutomaton\DFA;
use makadev\RE2DFA\FiniteAutomaton\DFAFixedNode;
use makadev\RE2DFA\FiniteAutomaton\DFAFixedNodeTransition;
use SplFixedArray;

abstract class OutputTableWriter extends OutputWriter {

    protected $nodes = [];

    protected function createTables() {
        foreach ($this->dfa->getNodes() as $node) {
            /**
             * @var DFAFixedNode $node
             */
            $currentNode = [];
            foreach ($node->transitions as $transition) {
                /**
                 * @var DFAFixedNodeTransition $transition
                 */
                $alpha = $this->addToAlphaSetTable($transition->transitionSet);
                $toNode = $transition->targetNode;
                $finals = $this->addToFinalStateTable($this->dfa->getFinalStates($toNode));
                $currentNode[] = [
                    $alpha,
                    $toNode,
                    $finals
                ];
            }
            if (count($currentNode) <= 0) {
                $this->nodes[] = null;
            } else {
                $this->nodes[] = $currentNode;
            }
        }
    }

    protected $finalsLookupTable = [];
    protected $finalsTable = [];
    protected $finalsPointer = 0;

    protected function addToFinalStateTable(SplFixedArray $finals): int {
        if ($finals->count() <= 0) {
            return -1;
        }
        $lookupString = join(":", $finals->toArray());
        if (isset($this->finalsLookupTable[$lookupString])) {
            return $this->finalsLookupTable[$lookupString];
        }
        $index = count($this->finalsTable);
        $this->finalsLookupTable[$lookupString] = $index;
        $this->finalsTable[] = $finals->count() > 1 ? $finals->toArray() : $finals[0];
        return $index;
    }

    protected $setStringTable = [];
    protected $setStringLookupTable = [];

    protected function addToAlphaSetTable(AlphaSet $set): int {
        $charIndexString = str_repeat("0", 256);
        for ($i = 0; $i < 255; $i++) {
            if ($set->test($i)) {
                $charIndexString[$i] = "1";
            }
        }
        if (isset($this->setStringLookupTable[$charIndexString])) {
            return $this->setStringLookupTable[$charIndexString];
        }
        $index = count($this->setStringTable);
        $this->setStringLookupTable[$charIndexString] = $index;
        $this->setStringTable[] = $charIndexString;
        return $index;
    }
}