<?php


namespace makadev\RE2DFA\RegEx;


use Error;
use makadev\RE2DFA\CharacterSet\AlphaSet;
use makadev\RE2DFA\FiniteAutomaton\EpsilonNFA;
use SplStack;

class RegExParserStack {

    /**
     * internal stack
     *
     * @var SplStack<RegExParserStackElement>
     */
    private SplStack $internalStack;

    /**
     * RegExParserStack constructor.
     */
    public function __construct() {
        $this->internalStack = new SplStack();
    }

    /**
     * print/stringify stack layout
     *
     * @param callable|null $printer
     */
    public function stackDump(?callable $printer = null): void {
        if ($printer === null) {
            $printer = function (string $elem, bool $last) {
                echo $elem;
                if ($last) {
                    echo PHP_EOL;
                } else {
                    echo ' ';
                }
            };
        }
        $printer("ENFAStackPrint [", false);
        $this->internalStack->rewind();
        $stack = "";
        while ($this->internalStack->valid()) {
            /**
             * @var RegExParserStackElement $elem
             */
            $elem = $this->internalStack->current();
            switch ($elem->getOperand()) {
                case RegExParserStackElement::OP_RENFA:
                    $stack .= "R";
                    break;
                case RegExParserStackElement::OP_CHOICE:
                    $stack .= "|";
                    break;
                case RegExParserStackElement::OP_ENTER_NEW:
                    $stack .= "(";
                    break;
                case RegExParserStackElement::OP_EXIT_RET:
                    $stack .= ")";
                    break;
                default:
                    $stack .= "E";
            }
            $this->internalStack->next();
        }
        for ($i = strlen($stack) - 1; $i >= 0; $i--) {
            $printer($stack[$i], false);
        }
        $printer("]", true);
    }

    /**
     * return stack layout
     *
     * @return string
     */
    public function getStackLayout(): string {
        $dump = "";
        $this->stackDump(function ($item, $last) use (&$dump) {
            $dump .= $item;
            if (!$last) $dump .= " ";
        });
        return $dump;
    }

    /**
     * return stack layout
     *
     * @return string
     * @todo should not be layout but a real representation with sub expressions
     *
     */
    public function __toString(): string {
        return $this->getStackLayout();
    }

    /**
     * pop element from internal stack
     *
     * @return RegExParserStackElement
     */
    protected function pop(): RegExParserStackElement {
        return $this->internalStack->pop();
    }

    /**
     * get the operand from stack top
     *
     * @return int
     */
    public function topOperand(): int {
        if ($this->internalStack->isEmpty()) {
            return RegExParserStackElement::OP_SNONE;
        }
        /**
         * @var RegExParserStackElement $elem
         */
        $elem = $this->internalStack->top();
        return $elem->getOperand();
    }

    /**
     * get the EpsilonNFA from stack top
     *
     * @return EpsilonNFA|null
     */
    public function topENFA(): ?EpsilonNFA {
        if ($this->internalStack->isEmpty()) {
            return null;
        }
        /**
         * @var RegExParserStackElement $elem
         */
        $elem = $this->internalStack->top();
        return $elem->getEnfa();
    }

    /**
     * push an operator
     *
     * @param int $op
     */
    protected function pushElementWithOp(int $op): void {
        $this->internalStack->push(new RegExParserStackElement($op));
    }

    /**
     * push an EpsilonNFA
     *
     * @param EpsilonNFA $enfa
     */
    protected function pushElementWithENFA(EpsilonNFA $enfa): void {
        $this->internalStack->push(new RegExParserStackElement(RegExParserStackElement::OP_RENFA, $enfa));
    }

    /**
     * push another element on the stack and check if operations can be done to reduce subexpressions:
     *
     * this is the main expression parser algorithm: typically regex has either postfix or infix operators,
     * inside a subexpression postfix operators can be applied to the last RE on stack while
     * infix operators are applied to a series of stack elements depending on precedence.
     *
     * @param int $o
     * @param EpsilonNFA|null $enfa
     * @throws RegExParserStackException
     * @todo while straight forward and fast, this is really hard to maintain, switch to AST parser which is much simpler and easier to modify/extend
     *
     */
    public function push(int $o, ?EpsilonNFA $enfa): void {
        switch ($o) {
            case RegExParserStackElement::OP_RENFA:
                assert($enfa !== null);
                $this->pushElementWithENFA($enfa);
                break;
            case RegExParserStackElement::OP_CHOICE:
                $this->mergeConcatOps();
                $this->pushElementWithOp($o);
                break;
            case RegExParserStackElement::OP_ENTER_NEW:
                $this->pushElementWithOp($o);
                break;
            case RegExParserStackElement::OP_EXIT_RET:
                $this->mergeChoiceOps();
                break;
            default:
                throw new Error("Unknown Op");
        }
    }

    /**
     * part of the main parser algorithm which mostly handles some backwards application of concat
     *
     * @throws RegExParserStackException
     */
    protected function mergeConcatOps(): void {
        $term = true;
        $tmp1 = null;
        $tmp2 = null;
        switch ($this->topOperand()) {
            case RegExParserStackElement::OP_ENTER_NEW:
                $emptyAlpha = new AlphaSet();
                $emptyMatchingMachine = new EpsilonNFA($emptyAlpha);
                $this->pushElementWithENFA($emptyMatchingMachine);
                break;
            case RegExParserStackElement::OP_CHOICE:
                $emptyAlpha = new AlphaSet();
                $emptyMatchingMachine = new EpsilonNFA($emptyAlpha);
                $this->pushElementWithENFA($emptyMatchingMachine);
                break;
            case RegExParserStackElement::OP_RENFA:
                $term = false;
                $tmp1 = $this->topENFA();
                $this->pop();
                break;
            case RegExParserStackElement::OP_SNONE:
                $e = new RegExParserStackException("Stack Underflow");
                $e->setStackLayout($this->getStackLayout());
                throw $e;
            default:
                throw new Error("Unknown Op");
        }

        while (!$term) {
            $term = true;

            switch ($this->topOperand()) {
                case RegExParserStackElement::OP_ENTER_NEW:
                    $this->pushElementWithENFA($tmp1);
                    break;
                case RegExParserStackElement::OP_CHOICE:
                    $this->pushElementWithENFA($tmp1);
                    break;
                case RegExParserStackElement::OP_RENFA:
                    $term = false;
                    $tmp2 = $this->topENFA();
                    $this->pop();
                    $tmp2->Concat($tmp1);
                    $tmp1 = $tmp2;
                    $tmp2 = null;
                    break;
                case RegExParserStackElement::OP_SNONE:
                    $e = new RegExParserStackException("Stack Underflow");
                    $e->setStackLayout($this->getStackLayout());
                    throw $e;
                default:
                    throw new Error("Unknown Op");
            }
        }
    }

    /**
     * part of the main parser algorithm which mostly handles some backwards application of choice
     *
     * @throws RegExParserStackException
     */
    protected function mergeChoiceOps(): void {
        $this->mergeConcatOps();
        $term = true;
        $tmp1 = null;
        $tmp2 = null;
        switch ($this->topOperand()) {
            case RegExParserStackElement::OP_RENFA:
                $term = false;
                $tmp1 = $this->topENFA();
                $this->pop();
                break;
            case RegExParserStackElement::OP_ENTER_NEW:
            case RegExParserStackElement::OP_CHOICE:
                throw new Error("Stack Operands out of Order: " . $this->getStackLayout());
            case RegExParserStackElement::OP_SNONE:
                $e = new RegExParserStackException("Stack Underflow");
                $e->setStackLayout($this->getStackLayout());
                throw $e;
            default:
                throw new Error("Unknown Op");
        }

        while (!$term) {
            $term = true;

            switch ($this->topOperand()) {
                case RegExParserStackElement::OP_ENTER_NEW:
                    $this->pop();
                    $this->pushElementWithENFA($tmp1);
                    break;
                case RegExParserStackElement::OP_CHOICE:
                    $this->pop();
                    switch ($this->topOperand()) {
                        case RegExParserStackElement::OP_RENFA:
                            $term = false;
                            $tmp2 = $this->topENFA();
                            $this->pop();
                            $tmp2->Choice($tmp1);
                            $tmp1 = $tmp2;
                            $tmp2 = null;
                            break;
                        case RegExParserStackElement::OP_ENTER_NEW:
                        case RegExParserStackElement::OP_CHOICE:
                            throw new Error("Stack Operands out of Order: " . $this->getStackLayout());
                        case RegExParserStackElement::OP_SNONE:
                            $e = new RegExParserStackException("Stack Underflow");
                            $e->setStackLayout($this->getStackLayout());
                            throw $e;
                        default:
                            throw new Error("Unknown Op");
                    }
                    break;
                case RegExParserStackElement::OP_RENFA:
                    throw new Error("Stack Operands out of Order: " . $this->getStackLayout());
                case RegExParserStackElement::OP_SNONE:
                    $e = new RegExParserStackException("Stack Underflow");
                    $e->setStackLayout($this->getStackLayout());
                    throw $e;
                default:
                    throw new Error("Unknown Op");
            }
        }
    }

    /**
     * check if the regular expression could be parsed
     *
     * @return bool
     */
    public function complete(): bool {
        return ($this->internalStack->count() === 1) &&
            ($this->topOperand() === RegExParserStackElement::OP_RENFA);
    }

    /**
     * check if stack is empty due to underflow or parser didn't do anything
     *
     * @return bool
     */
    public function empty(): bool {
        return $this->internalStack->count() <= 0;
    }
}
