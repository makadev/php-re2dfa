<?php


namespace makadev\RE2DFA\CLI;


class OutputTableWriterPHP extends OutputTableWriter {

    public function print() {
        $this->createTables();

        // write intial node
        echo '<?php' . PHP_EOL . PHP_EOL;
        echo '$START_STATE = ' . $this->dfa->getStartNode() . ';' . PHP_EOL . PHP_EOL;
        // write transition sets
        echo '$ALPHA_SET_TABLE = [' . PHP_EOL;
        foreach ($this->setStringTable as $value) {
            echo '    "' . $value . '",' . PHP_EOL;
        }
        echo '];' . PHP_EOL . PHP_EOL;
        // write final states
        echo '$FINAL_STATE_TABLE = [' . PHP_EOL;
        foreach ($this->finalsTable as $value) {
            if (is_string($value)) {
                echo '    "' . $value . '",' . PHP_EOL;
            } else {
                echo '    [' . PHP_EOL;
                foreach ($value as $fin) {
                    echo '        "' . $fin . '",' . PHP_EOL;
                }
                echo '    ],' . PHP_EOL;
            }
        }
        echo '];' . PHP_EOL . PHP_EOL;
        // write node transition table
        echo '$STATE_TRANSITIONS = [' . PHP_EOL;
        foreach ($this->nodes as $node) {
            if ($node === null) {
                echo '    null,' . PHP_EOL;
            } else {
                echo '    [' . PHP_EOL;
                foreach ($node as $transition) {
                    echo '        [' . $transition[0] . ', ' . $transition[1] . ', ' . $transition[2] . '],' . PHP_EOL;
                }
                echo '    ],' . PHP_EOL;

            }
        }
        echo '];' . PHP_EOL;
    }
}