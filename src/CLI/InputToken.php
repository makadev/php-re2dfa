<?php


namespace makadev\RE2DFA\CLI;


class InputToken {
    public string $type = "NONE";
    public string $content = "";
    public int $line = 0;
    public int $char = 0;

    public function __construct($type, $content, $line, $char) {
        $this->type = $type;
        $this->content = $content;
        $this->line = $line;
        $this->char = $char;
    }
}
