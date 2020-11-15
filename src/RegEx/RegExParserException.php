<?php


namespace makadev\RE2DFA\RegEx;


use Exception;
use Throwable;

class RegExParserException extends Exception {

    /**
     *
     * @var string
     */
    protected string $regex;

    /**
     *
     * @var int
     */
    protected int $currentPosition;

    /**
     *
     * @var RegExParserStackException|null
     */
    protected ?RegExParserStackException $propagatedStackError;

    /**
     * RegExParserException constructor.
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($message = "", $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    /**
     *
     * @param string $regex
     * @param int $cpos
     * @param RegExParserStackException|null $stackError
     */
    public function setRegExErrorInfo(string $regex, int $cpos, ?RegExParserStackException $stackError = null): void {
        $this->regex = $regex;
        $this->currentPosition = $cpos;
        $this->propagatedStackError = $stackError;
    }

    /**
     * expression which was faulty
     *
     * @return string
     */
    public function getRegex(): string {
        return $this->regex;
    }

    /**
     * position where the exception was raised,
     * _this might be greater than length of the regex_
     *
     * @return int
     */
    public function getCurrentPosition(): int {
        return $this->currentPosition;
    }

    /**
     * wrapped internal parser stack exception if existent
     *
     * @return RegExParserStackException|null
     */
    public function getPropagatedStackError(): ?RegExParserStackException {
        return $this->propagatedStackError;
    }
}