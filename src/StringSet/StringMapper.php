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

    /**
     * check if string is in this mapping
     *
     * @param string $s
     * @return bool
     */
    function has(string $s): bool {
        return isset($this->lookupTable[$s]);
    }

    /**
     * return the mapped id for string, add string if it is not in the mapping and assign a new (consecutive) id
     *
     * @param string $s
     * @return int
     */
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

    /**
     * get amount of unique strings / assigned ids in the string mapping
     *
     * @return int
     */
    function count(): int {
        return count($this->forwardMapping);
    }
}
