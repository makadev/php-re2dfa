<?php


namespace makadev\RE2DFA\RegEx;


use makadev\RE2DFA\CharacterSet\AlphaSet;
use makadev\RE2DFA\FiniteAutomaton\EpsilonNFA;

/**
 * Class RegExParser
 *
 * @todo replace with a parser which creates an AST rather than this stack parser, for flexibility and sanity
 *
 * @package makadev\RE2DFA\RegEx
 */
class RegExParser {

    /**
     *
     * @var RegExParserStack
     */
    private RegExParserStack $stack;

    /**
     *
     * @var string
     */
    private string $regex;

    /**
     *
     * @var int
     */
    private int $pos;

    /**
     *
     * @var int
     */
    private int $endPos;

    /**
     * RegExParser constructor.
     *
     * @param string $regex
     */
    public function __construct(string $regex) {
        $this->stack = new RegExParserStack();
        $this->regex = $regex;
        $this->endPos = strlen($regex);
        $this->pos = 0;
    }

    /**
     * parser as set like "[a-z]"
     *
     * @return AlphaSet
     * @throws RegExParserException
     */
    protected function parseSet(): AlphaSet {
        $result = new AlphaSet();
        $this->pos++;

        if ($this->regex[$this->pos] !== ']') {
            $invert = $this->regex[$this->pos] === '^';
            if ($invert) {
                $this->pos++;
            }
            while (($this->pos < $this->endPos)
                && $this->regex[$this->pos] !== "]") {
                $current = $this->regex[$this->pos];
                if ($current === '#') {
                    $current = $this->decodeChar();
                }
                if (($this->pos < ($this->endPos - 2)) &&
                    ($this->regex[$this->pos + 1] === "-") &&
                    ($this->regex[$this->pos + 2] !== "]")) {
                    $this->pos += 2;
                    $current2 = $this->regex[$this->pos];
                    if ($current2 === '#') {
                        $current2 = $this->decodeChar();
                    }
                    $result->setRange(ord($current), ord($current2));
                } else {
                    $result->set(ord($current));
                }
                $this->pos++;
            }
            if ($invert) {
                $complement = (new AlphaSet())->complement();
                $result = $complement->subtract($result);
            }
            if (($this->pos >= $this->endPos) || $this->regex[$this->pos] !== ']') {
                $e = new RegExParserException("Unexpected set end (missing ]).");
                $e->setRegExErrorInfo($this->regex, $this->pos);
                throw $e;
            }
        }
        return $result;
    }

    /**
     * decode a coded character like "#10"
     *
     * @return string
     */
    protected function decodeChar(): string {
        $result = '#';
        if ((($this->pos + 3) < $this->endPos) &&
            (strpos('012', $this->regex[$this->pos + 1]) !== false) &&
            (strpos('0123456789', $this->regex[$this->pos + 2]) !== false) &&
            (strpos('0123456789', $this->regex[$this->pos + 3]) !== false)) {
            $byte = (ord($this->regex[$this->pos + 1]) - ord('0')) * 100 +
                (ord($this->regex[$this->pos + 2]) - ord('0')) * 10 +
                (ord($this->regex[$this->pos + 3]) - ord('0'));
            if (($byte >= 0) && ($byte <= 255)) {
                $this->pos += 3;
                return chr($byte);
            }
        }
        return $result;
    }

    /**
     * push EpsilonNFA onto stack
     *
     * @param EpsilonNFA $enfa
     */
    protected function pushENFA(EpsilonNFA $enfa): void {
        $this->stack->push(RegExParserStackElement::OP_RENFA, $enfa);
    }

    /**
     * push Choice "|" operator on stack
     *
     */
    protected function pushChoice(): void {
        $this->stack->push(RegExParserStackElement::OP_CHOICE, null);
    }

    /**
     * push ENFA Start/Open Subexpression "(" on stack
     */
    protected function pushOpeningBrace(): void {
        $this->stack->push(RegExParserStackElement::OP_ENTER_NEW, null);
    }

    /**
     * push ENFA End/Close Subexpression ")" on stack
     */
    protected function pushClosingBrace(): void {
        $this->stack->push(RegExParserStackElement::OP_EXIT_RET, null);
    }

    /**
     * enfa calculated from expression transformation
     *
     * @var EpsilonNFA|null
     */
    private ?EpsilonNFA $enfa = null;

    /**
     * calculate and return enfa from expression
     *
     * @return EpsilonNFA
     * @throws RegExParserException
     */
    public function build(): EpsilonNFA {
        if ($this->enfa !== null) return $this->enfa;
        $this->pos = 0;
        $result = null;

        try {
            $this->pushOpeningBrace();
            while ($this->pos < $this->endPos) {
                switch ($this->regex[$this->pos]) {
                    case "[":
                        $this->pushENFA(new EpsilonNFA($this->parseSet()));
                        break;
                    case "|":
                        $this->pushChoice();
                        break;
                    case "(":
                        $this->pushOpeningBrace();
                        break;
                    case ")":
                        $this->pushClosingBrace();
                        break;
                    case "?":
                        if ($this->stack->topOperand() === RegExParserStackElement::OP_RENFA) {
                            $this->stack->topENFA()->NoneOrOne();
                        } else {
                            $e = new RegExParserException("No expression to use ? on.");
                            $e->setRegExErrorInfo($this->regex, $this->pos);
                            throw $e;
                        }
                        break;
                    case "*":
                        if ($this->stack->topOperand() === RegExParserStackElement::OP_RENFA) {
                            $this->stack->topENFA()->MultipleTimes();
                        } else {
                            $e = new RegExParserException("No expression to use * on.");
                            $e->setRegExErrorInfo($this->regex, $this->pos);
                            throw $e;
                        }
                        break;
                    case "+":
                        if ($this->stack->topOperand() === RegExParserStackElement::OP_RENFA) {
                            $this->stack->topENFA()->AtLeastOne();
                        } else {
                            $e = new RegExParserException("No expression to use + on.");
                            $e->setRegExErrorInfo($this->regex, $this->pos);
                            throw $e;
                        }
                        break;
                    case "#":
                        $alpha = new AlphaSet();
                        $alpha->set(ord($this->decodeChar()));
                        $this->pushENFA(new EpsilonNFA($alpha));
                        break;
                    default:
                        $alpha = new AlphaSet();
                        $alpha->set(ord($this->regex[$this->pos]));
                        $this->pushENFA(new EpsilonNFA($alpha));
                }
                $this->pos++;
            }
            $this->pushClosingBrace();
            if (!$this->stack->complete()) {
                $e = new RegExParserException("Unexpected end of regex (check opening and closing braces).");
                $e->setRegExErrorInfo($this->regex, $this->pos);
                throw $e;
            } else {
                $result = $this->stack->topENFA();
            }
        } catch (RegExParserStackException $se) {
            $e = new RegExParserException("Unexpected stack error.");
            $e->setRegExErrorInfo($this->regex, $this->pos, $se);
            throw $e;
        }
        // at this point result should not be null or somethings broken internally
        assert($result !== null);
        $this->enfa = $result;
        return $result;
    }

}