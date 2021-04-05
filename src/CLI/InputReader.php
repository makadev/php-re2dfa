<?php


namespace makadev\RE2DFA\CLI;


class InputReader {

    protected string $inputFile;
    protected $handle;
    protected int $bufferLength = 0;
    protected string $buffer;
    protected int $position = 0;
    protected int $linePos = 1;
    protected int $charPos = 0;

    public function __construct(string $file) {
        $this->inputFile = $file;
        $this->handle = fopen($this->inputFile, "r");
    }

    protected function eof(): bool {
        return $this->position >= $this->bufferLength &&
            $this->bufferEnd;
    }

    protected bool $bufferEnd = false;

    protected function nextBuffer(): void {
        if ($this->bufferEnd) return;
        if (!feof($this->handle)) {
            $this->position = 0;
            $this->buffer = fread($this->handle, 1024);
            $this->bufferLength = strlen($this->buffer);
        }
        $this->bufferEnd = true;
        fclose($this->handle);
    }

    protected function readChar(): string {
        if ($this->position >= $this->bufferLength) {
            $this->nextBuffer();
        }
        if (!$this->eof()) {
            return $this->buffer[$this->position++];
        }
        return '';
    }

    protected string $pushBackChar = "";

    protected function pushBack(string $char) {
        $this->pushBackChar = $char;
    }

    protected function nextChar($skipWhitespace = false): string {
        if (strlen($this->pushBackChar) > 0) {
            $char = $this->pushBackChar;
            $this->pushBackChar = "";
        } else {
            $char = $this->readChar();
            $this->charPos++;
        }

        // skip newlines
        while ($char === "\n" || $char === "\r" || ($skipWhitespace && ($char === " " || $char === "\t"))) {
            // mixed mode: we try to detect and ignore either CR, LF or CRLF (but not CRLF)
            // most of the time this should be a good line ending estimation
            if ($char === "\n") {
                $char = $this->readChar();
                $this->linePos++;
                $this->charPos = 1;
            } elseif ($char === "\r") {
                $char = $this->readChar();
                $this->linePos++;
                $this->charPos = 1;
                if ($char === "\n") {
                    $char = $this->readChar();
                }
            } else {
                // whitespace
                $char = $this->readChar();
                $this->charPos++;
            }
        }

        return $char;
    }

    protected function skipComment(): string {
        $char = $this->readChar();
        $this->charPos++;

        // skip newlines
        while ($char !== "\n" && $char !== "\r" && $char !== "") {
            $char = $this->readChar();
            $this->charPos++;
        }

        // handle newline
        if ($char === "\n") {
            $char = $this->readChar();
            $this->linePos++;
            $this->charPos = 1;
        } elseif ($char === "\r") {
            $char = $this->readChar();
            $this->linePos++;
            $this->charPos = 1;
            if ($char === "\n") {
                $char = $this->readChar();
            }
        }

        return $char;
    }

    protected int $state = 0;

    public function nextToken(): InputToken {
        do {
            $char = $this->nextChar(true);
            // comment line
            if (($this->state === 0) && ($char === "#")) {
                $char = $this->skipComment();
                $this->pushBack($char);
                continue;
            }
            // (allowed) end of file
            if (($this->state === 0) && strlen($char) <= 0) {
                // file end
                return new InputToken("EOF", "", $this->linePos, $this->charPos);
            }
            // parse a definition block starting with an ID
            if (($this->state === 0) && strlen($char) > 0) {
                $lpos = $this->linePos;
                $cpos = $this->charPos;
                $o = ord($char);
                $content = "";
                while (
                    (strlen($char) > 0) && (
                        // 0-9
                        (($o >= 48) && ($o <= 57)) ||
                        // A-Z
                        (($o >= 65) && ($o <= 90)) ||
                        // a-z
                        (($o >= 97) && ($o <= 122)) ||
                        // _
                        ($o === 95)
                    )
                ) {
                    $content .= $char;
                    $char = $this->nextChar();
                    if (strlen($char) > 0) {
                        $o = ord($char);
                    }
                }
                if (strlen($content) <= 0) {
                    return new InputToken("ERROR", "Unexpected character, expect ID [0-9a-zA-Z_]+", $this->linePos, $this->charPos);
                }
                // put lookahead character back so we read it again
                $this->pushBack($char);
                // switch state
                $this->state = 1;
                return new InputToken("ID", $content, $lpos, $cpos);
            }
            // parse definition block part 2, a delimiter
            if ($this->state === 1) {
                if ($char === ":") {
                    $this->state = 2;
                    return new InputToken("DELIMITER", ":", $this->linePos, $this->charPos);
                }
                if ($char === "") {
                    return new InputToken("ERROR", "Unexpected end of file, expect DELIMITER :", $this->linePos, $this->charPos);
                } else {
                    return new InputToken("ERROR", "Unexpected character, expect DELIMITER :", $this->linePos, $this->charPos);
                }
            }
            // parse definition block part 3, everything to the line ending
            if ($this->state === 2) {
                $lpos = $this->linePos;
                $cpos = $this->charPos;
                // read until end
                $content = "";
                while ($char !== "\n" && $char !== "\r" && $char !== "") {
                    $content .= $char;
                    $char = $this->readChar();
                    $this->charPos++;
                }
                if (strlen($content) <= 0) {
                    return new InputToken("ERROR", "Unexpected end of file, expect REGEX", $this->linePos, $this->charPos);
                }
                $this->state = 0;
                return new InputToken("REGEX", $content, $lpos, $cpos);
            }
        } while ($char !== "");

        return new InputToken("ERROR", "Unknown", $this->linePos, $this->charPos);
    }

}