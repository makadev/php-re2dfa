<?php

use makadev\RE2DFA\FiniteAutomaton\DFA;
use makadev\RE2DFA\FiniteAutomaton\DFABuilder;
use makadev\RE2DFA\RegEx\RegExParser;
use PHPUnit\Framework\TestCase;

class DFABuilderTest extends TestCase {

    /**
     * @param DFA $dfa
     * @param string $match
     * @param string[] $states
     */
    public function assertDFAMatch(DFA $dfa, string $match, array $states = []): void {
        $runner = $dfa->newRunner();
        // test separately that it matches AND the states
        $this->assertTrue($runner->match($match, $states), "DFA did not match '" . $match . "', expected it should (without states) " .
        "given following DFA." . PHP_EOL . $dfa);
        if (count($states) > 0) {
            $this->assertTrue($runner->match($match, $states), "DFA did not match given states, expected it should" .
                "given following DFA." . PHP_EOL . $dfa);
        }
    }

    /**
     * @param DFA $dfa
     * @param string $match
     * @param string[] $states
     */
    public function assertNoDFAMatch(DFA $dfa, string $match, array $states = []): void {
        $runner = $dfa->newRunner();
        $this->assertFalse($runner->match($match, $states), "DFA did match '" . $match . "', expected it should not" .
            "given following DFA." . PHP_EOL . $dfa);
    }

    public function testEmptyDFA(): void {
        $dfaBuilder = new DFABuilder();
        $dfa = $dfaBuilder->build();

        $runner = $dfa->newRunner();
        $this->assertFalse($runner->next(0));
        $this->assertFalse($runner->isFinalState());
        $this->assertTrue($runner->isErrorState());
    }

    public function testComplexRETransformation(): void {
        $dfaBuilder = new DFABuilder();

        $floatingPoint = new RegExParser('([-+]?)([0-9]*)([.]?)([0-9]+)(([eE]([-+]?)([0-9]+))?)');
        $dfaBuilder->addENFA($floatingPoint->build(), "float");

        $integer = new RegExParser('([-+]?)([0-9]+)');
        $dfaBuilder->addENFA($integer->build(), "integer");

        $dfa = $dfaBuilder->build();

        $this->assertNotNull($dfa);
        $this->assertNoDFAMatch($dfa, "");
        $this->assertDFAMatch($dfa, "10", ['integer']);
        $this->assertDFAMatch($dfa, "10", ['float']);
        $this->assertDFAMatch($dfa, "+213", ['integer']);
        $this->assertDFAMatch($dfa, "+213", ['float']);
        $this->assertDFAMatch($dfa, "-34", ['integer']);
        $this->assertDFAMatch($dfa, "-34", ['float']);
        $this->assertDFAMatch($dfa, "3434.4E-2344", ['float']);
        $this->assertNoDFAMatch($dfa, "3434.4E-2344", ['integer']);
        $this->assertDFAMatch($dfa, "234.4E+24", ['float']);
        $this->assertNoDFAMatch($dfa, "234.4E+24", ['integer']);
        $this->assertDFAMatch($dfa, "1123E2", ['float']);
        $this->assertNoDFAMatch($dfa, "1123E2", ['integer']);
        $this->assertDFAMatch($dfa, "1213.4", ['float']);
        $this->assertNoDFAMatch($dfa, "1213.4", ['integer']);
    }

    public function testComplexRETransformationMergeFinals(): void {
        $dfaBuilder = new DFABuilder();

        $floatingPoint = new RegExParser('([-+]?)([0-9]*)([.]?)([0-9]+)(([eE]([-+]?)([0-9]+))?)');
        $dfaBuilder->addENFA($floatingPoint->build(), "float");

        $integer = new RegExParser('([-+]?)([0-9]+)');
        $dfaBuilder->addENFA($integer->build(), "float");

        $positive = new RegExParser('[0-9]+');
        $dfaBuilder->addENFA($positive->build(), "float");

        $digit = new RegExParser('[0-9]');
        $dfaBuilder->addENFA($digit->build(), "digit");

        $dfa = $dfaBuilder->build();

        $this->assertNotNull($dfa);
        $this->assertNoDFAMatch($dfa, "");
        $this->assertDFAMatch($dfa, "1", ['float']);
        $this->assertDFAMatch($dfa, "1", ['digit']);
        $this->assertDFAMatch($dfa, "10", ['float']);
        $this->assertDFAMatch($dfa, "+213", ['float']);
        $this->assertDFAMatch($dfa, "3434.4E-2344", ['float']);
    }
}