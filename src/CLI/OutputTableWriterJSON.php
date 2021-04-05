<?php


namespace makadev\RE2DFA\CLI;


class OutputTableWriterJSON extends OutputTableWriter {

    public function print() {
        $this->createTables();

        $output = [
            "START_NODE" => $this->dfa->getStartNode(),
            "ALPHA_SET_TABLE" => $this->setStringTable,
            "FINAL_STATE_TABLE" => $this->finalsTable,
            "STATE_TRANSITIONS" => $this->nodes,
        ];

        echo json_encode($output, JSON_PRETTY_PRINT) . PHP_EOL;
    }
}