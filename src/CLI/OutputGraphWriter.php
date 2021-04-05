<?php


namespace makadev\RE2DFA\CLI;


use Fhaculty\Graph\Graph;
use makadev\RE2DFA\CharacterSet\AlphaSet;
use makadev\RE2DFA\FiniteAutomaton\DFA;
use makadev\RE2DFA\FiniteAutomaton\DFAFixedNode;
use makadev\RE2DFA\FiniteAutomaton\DFAFixedNodeTransition;

abstract class OutputGraphWriter extends OutputWriter {

    protected Graph $graph;

    public function __construct(DFA $dfa) {
        parent::__construct($dfa);
        $this->graph = new Graph();
    }

    protected function createGraph($nodeLabel, $edgeLabel) {
        $this->graph->createVertices($this->dfa->getNodes()->count());
        $cnt = 0;
        foreach ($this->dfa->getNodes() as $node) {
            /**
             * @var DFAFixedNode $node
             */
            $vertex = $this->graph->getVertex($cnt);
            $vertex->setAttribute($nodeLabel, "$cnt");
            foreach ($node->transitions as $transition) {
                /**
                 * @var DFAFixedNodeTransition $transition
                 */
                $toVertex = $this->graph->getVertex($transition->targetNode);
                $transEdge = $vertex->createEdgeTo($toVertex);
                $transEdge->setAttribute($edgeLabel, $this->getAlphaString($transition->transitionSet));
            }

            if ($node->finalStates) {
                $finalStates = "";
                foreach ($node->finalStates as $finalState) {
                    $finalStates .= "\n" . $finalState;
                }
                if (strlen($finalStates) > 0) {
                    $vertex->setAttribute($nodeLabel, "$cnt $finalStates");
                }
            }

            $cnt++;
        }
    }

    protected function getAlphaString(AlphaSet $set): string {
        return "" . $set;
    }
}