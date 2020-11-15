<?php


namespace makadev\RE2DFA\FiniteAutomaton;

class DFARunner {

    /**
     * used dfa
     *
     * @var DFA
     */
    private DFA $dfa;

    /**
     * current node of the simulation
     *
     * @var int
     */
    private int $node;

    /**
     * whether last transition entered an error state or not
     *
     * @var bool
     */
    private bool $errorState;

    /**
     * whether current node is final or not
     *
     * @var bool
     */
    private bool $finalState;

    /**
     * DFARunner constructor.
     *
     * @param DFA $dfa
     */
    public function __construct(DFA $dfa) {
        $this->dfa = $dfa;
        $this->reset();
    }

    /**
     * check if last transition was unable to consume
     *
     * @return bool
     */
    public function isErrorState(): bool {
        return $this->errorState;
    }

    /**
     * check if current node is a final state
     *
     * @return bool
     */
    public function isFinalState(): bool {
        return $this->finalState;
    }

    /**
     * get the current node in the dfa
     *
     * @return int
     */
    public function getNode(): int {
        return $this->node;
    }

    /**
     * reset the state machine to the start and clear error state
     */
    public function reset(): void {
        $this->node = $this->dfa->getStartNode();
        $this->errorState = false;
        $this->finalState = $this->dfa->isFinal($this->node);
    }

    /**
     * from current state of the runner consume $alpha and go into next state (or error state)
     *
     * @param int $alpha
     * @return bool true if new state is a final state, false otherwise
     */
    public function next(int $alpha): bool {
        if ($this->errorState) return false;
        $res = $this->dfa->getNextFor($this->node, $alpha);
        if ($res !== null) {
            $this->node = $res;
            $this->finalState = $this->dfa->isFinal($res);
            return true;
        }
        $this->errorState = true;
        return false;
    }

    /**
     * check if given string matches the dfa for this runner
     *
     * _the runner will be reset before matching_
     *
     * @param string $match
     * @param string[] $states
     * @return bool true if any of the given final $states - or if empty any at all - was reached
     */
    public function match(string $match, $states = []): bool {
        $this->reset();
        for ($i = 0; $i < strlen($match); $i++) {
            if (!$this->next(ord($match[$i]))) {
                return false;
            }
        }
        if (!$this->finalState) {
            return false;
        }
        if (count($states) > 0) {
            $fins = $this->dfa->getFinalStates($this->node);
            foreach ($states as $state) {
                for ($fins->rewind(); $fins->valid(); $fins->next()) {
                    if ($fins->current() === $state) {
                        return true;
                    }
                }
            }
            return false;
        }
        return true;
    }
}