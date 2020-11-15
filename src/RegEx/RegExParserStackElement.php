<?php


namespace makadev\RE2DFA\RegEx;


use makadev\RE2DFA\FiniteAutomaton\EpsilonNFA;

class RegExParserStackElement {

    public const OP_CONCAT = 0;
    public const OP_CHOICE = 1;
    public const OP_SNONE = 2;
    public const OP_ENTER_NEW = 3;
    public const OP_EXIT_RET = 4;
    public const OP_RENFA = 5;

    /**
     * One of self::OP_*
     *
     * @var int
     */
    private int $operand;

    /**
     * Get Operand
     *
     * @return int
     */
    public function getOperand(): int {
        return $this->operand;
    }

    /**
     * EpsilonNFA for the case $operand===self::OP_RENFA
     *
     * @var EpsilonNFA|null $enfa
     */
    private ?EpsilonNFA $enfa;

    /**
     * Get EpsilonNFA
     *
     * @return EpsilonNFA|null
     */
    public function getEnfa(): ?EpsilonNFA {
        return $this->enfa;
    }

    /**
     * RegExParserStackElement constructor.
     *
     * @param int $op
     * @param EpsilonNFA|null $enfa
     */
    public function __construct(int $op = self::OP_SNONE, ?EpsilonNFA $enfa = null) {
        $this->operand = $op;
        $this->enfa = $enfa;
    }
}