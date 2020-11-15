<?php


namespace makadev\RE2DFA\RegEx;

use Exception;

class RegExParserStackException extends Exception {

    /**
     * stack layout
     *
     * @var string
     */
    protected string $stackLayout;

    /**
     *
     * @param string $slay
     */
    public function setStackLayout(string $slay): void {
        $this->stackLayout = $slay;
    }

    /**
     *
     * @return string
     */
    public function getStackLayout(): string {
        return $this->stackLayout;
    }
}