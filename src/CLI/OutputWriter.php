<?php


namespace makadev\RE2DFA\CLI;


use makadev\RE2DFA\FiniteAutomaton\DFA;

abstract class OutputWriter {

    protected DFA $dfa;

    public function __construct(DFA $dfa) {
        $this->dfa = $dfa;
    }

    public function write($file) {
        ob_start();
        $this->print();
        $generated = ob_get_contents();
        ob_end_clean();
        file_put_contents($file, $generated);
    }

    abstract public function print();
}