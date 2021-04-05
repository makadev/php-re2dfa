<?php


namespace makadev\RE2DFA\CLI;


use Graphp\GraphViz\Dot;
use makadev\RE2DFA\FiniteAutomaton\DFAFixedNode;

class OutputGraphWriterDOT extends OutputGraphWriter {

    public function print() {
        $this->createGraph("graphviz.label", "graphviz.label");
        $cnt = 0;
        foreach ($this->dfa->getNodes() as $node) {
            /**
             * @var DFAFixedNode $node
             */
            $vertex = $this->graph->getVertex($cnt);
            if ($node->finalStates) {
                $vertex->setAttribute('graphviz.shape', "octagon");
            } else {
                $vertex->setAttribute('graphviz.shape', "ellipse");
            }
            $cnt++;
        }
        $vertex = $this->graph->createVertex($this->dfa->getNodes()->count());
        $vertex->setAttribute("graphviz.label", "START");
        $vertex->setAttribute("graphviz.shape", "doubleoctagon");
        $startVertex = $this->graph->getVertex($this->dfa->getStartNode());
        $vertex->createEdgeTo($startVertex);

        $graphvizDOT = new Dot();
        echo $graphvizDOT->getOutput($this->graph);
    }
}