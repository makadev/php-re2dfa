<?php


namespace makadev\RE2DFA\StringSet;


class StringMapper {

    /**
     * mapping int => string
     *
     * @var string[]
     */
    private array $forwardMapping = [];

    /**
     * mapping string => int
     *
     * @var array
     */
    private array $lookupTable = [];

    function has(string $s): bool {
        return isset($this->lookupTable[$s]);
    }

    function lookupAdd(string $s): int {
        if (!isset($this->lookupTable[$s])) {
            $id = count($this->forwardMapping);
            $this->forwardMapping[] = $s;
            $this->lookupTable[$s] = $id;
        } else {
            $id = $this->lookupTable[$s];
        }
        return $id;
    }

    function count(): int {
        return count($this->forwardMapping);
    }
}
