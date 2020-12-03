<?php

use makadev\RE2DFA\CharacterSet\AlphaSet;
use makadev\RE2DFA\FiniteAutomaton\DFA;
use makadev\RE2DFA\FiniteAutomaton\DFABuilder;
use makadev\RE2DFA\FiniteAutomaton\DFAFixedNode;
use makadev\RE2DFA\FiniteAutomaton\DFAFixedNodeTransition;
use makadev\RE2DFA\FiniteAutomaton\DFAMinimizer;
use makadev\RE2DFA\RegEx\RegExParser;
use PHPUnit\Framework\TestCase;

class DFAMinimizerTest extends TestCase {

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
        $minimizer = new DFAMinimizer($dfa);
        $mdfa = $minimizer->minimize();

        // min dfa should be same as dfa, since the partition algorithm has nothing to do on an empty automaton
        $this->assertSame($dfa, $mdfa);
    }

    public function testComplexRETransformation(): void {
        $dfaBuilder = new DFABuilder();

        $floatingPoint = new RegExParser('([-+]?)([0-9]*)([.]?)([0-9]+)(([eE]([-+]?)([0-9]+))?)');
        $dfaBuilder->addENFA($floatingPoint->build(), "float");

        $integer = new RegExParser('([-+]?)([0-9]+)');
        $dfaBuilder->addENFA($integer->build(), "integer");

        // introduce additional states using a subset of the previous integer automaton (construction for test purpose)
        $integer = new RegExParser('([-+]?)[0-9]([0-9]+)');
        $dfaBuilder->addENFA($integer->build(), "integer");

        $integer = new RegExParser('[3-4]([6-7]*)');
        $dfaBuilder->addENFA($integer->build(), "integer");

        $dfa = $dfaBuilder->build();
        $minimizer = new DFAMinimizer($dfa);
        $mdfa = $minimizer->minimize();

        $this->assertNotSame($dfa, $mdfa);

        $this->assertNotNull($mdfa);
        $this->assertNoDFAMatch($mdfa, "");
        $this->assertDFAMatch($mdfa, "10", ['integer']);
        $this->assertDFAMatch($mdfa, "10", ['float']);
        $this->assertDFAMatch($mdfa, "+213", ['integer']);
        $this->assertDFAMatch($mdfa, "+213", ['float']);
        $this->assertDFAMatch($mdfa, "-34", ['integer']);
        $this->assertDFAMatch($mdfa, "-34", ['float']);
        $this->assertDFAMatch($mdfa, "3434.4E-2344", ['float']);
        $this->assertNoDFAMatch($mdfa, "3434.4E-2344", ['integer']);
        $this->assertDFAMatch($mdfa, "234.4E+24", ['float']);
        $this->assertNoDFAMatch($mdfa, "234.4E+24", ['integer']);
        $this->assertDFAMatch($mdfa, "1123E2", ['float']);
        $this->assertNoDFAMatch($mdfa, "1123E2", ['integer']);
        $this->assertDFAMatch($mdfa, "1213.4", ['float']);
        $this->assertNoDFAMatch($mdfa, "1213.4", ['integer']);
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
        $minimizer = new DFAMinimizer($dfa);
        $mdfa = $minimizer->minimize();

        $this->assertNotSame($dfa, $mdfa);

        $this->assertNotNull($mdfa);
        $this->assertNoDFAMatch($mdfa, "");
        $this->assertDFAMatch($mdfa, "1", ['float']);
        $this->assertDFAMatch($mdfa, "1", ['digit']);
        $this->assertDFAMatch($mdfa, "10", ['float']);
        $this->assertDFAMatch($mdfa, "+213", ['float']);
        $this->assertDFAMatch($mdfa, "3434.4E-2344", ['float']);
    }

    public function testNonCollapsingFinals(): void {
        $digetAlpha = new AlphaSet();
        $digetAlpha->setRange(ord('0'), ord('9'));

        $startNode = new DFAFixedNode();
        $startNode->transitions->push(new DFAFixedNodeTransition(clone $digetAlpha, 1));

        //
        $node = new DFAFixedNode();
        $node->finalStates = SplFixedArray::fromArray(['digit', 'number'], false);
        $node->transitions->push(new DFAFixedNodeTransition(clone $digetAlpha, 2));

        //
        $node2 = new DFAFixedNode();
        $node2->finalStates = SplFixedArray::fromArray(['number']);
        $node2->transitions->push(new DFAFixedNodeTransition(clone $digetAlpha, 3));

        //
        $node3 = new DFAFixedNode();
        $node3->finalStates = SplFixedArray::fromArray(['number']);
        $node3->transitions->push(new DFAFixedNodeTransition(clone $digetAlpha, 3));

        $dfa = new DFA(SplFixedArray::fromArray([$startNode, $node, $node2, $node3]), 0);
        $minimizer = new DFAMinimizer($dfa);
        $mdfa = $minimizer->minimize();

        $this->assertNotSame($dfa, $mdfa);

        $this->assertNotNull($mdfa);
        $this->assertNoDFAMatch($mdfa, "");
        $this->assertDFAMatch($mdfa, "1", ['number']);
        $this->assertDFAMatch($mdfa, "1", ['digit']);
        $this->assertDFAMatch($mdfa, "1", ['number']);
        $this->assertNoDFAMatch($mdfa, "10", ['digit']);
        $this->assertDFAMatch($mdfa, "10", ['number']);
    }

    /** TODO: check minimization against correct min dfa constructions
    public function testCollapsingFinals(): void {
        $digetAlpha = new AlphaSet();
        $digetAlpha->setRange(ord('0'), ord('9'));

        $startNode = new DFAFixedNode();
        $startNode->transitions->push(new DFAFixedNodeTransition(clone $digetAlpha, 1));

        //
        $node = new DFAFixedNode();
        $node->finalStates = SplFixedArray::fromArray(['digit', 'number'], false);
        $node->transitions->push(new DFAFixedNodeTransition(clone $digetAlpha, 2));

        //
        $node2 = new DFAFixedNode();
        $node2->finalStates = SplFixedArray::fromArray(['number']);
        $node2->transitions->push(new DFAFixedNodeTransition(clone $digetAlpha, 3));

        //
        $node3 = new DFAFixedNode();
        $node3->finalStates = SplFixedArray::fromArray(['number']);
        $node3->transitions->push(new DFAFixedNodeTransition(clone $digetAlpha, 3));

        $dfa = new DFA(SplFixedArray::fromArray([$startNode, $node, $node2, $node3]), 0);
        $minimizer = new DFAMinimizer($dfa, false);
        $mdfa = $minimizer->minimize();

        $this->assertNotSame($dfa, $mdfa);

        $this->assertNotNull($mdfa);
        $this->assertNoDFAMatch($mdfa, "");
        $this->assertDFAMatch($mdfa, "1", ['number']);
        $this->assertDFAMatch($mdfa, "", ['digit']);
        $this->assertDFAMatch($mdfa, "1", ['number']);
        $this->assertNoDFAMatch($mdfa, "10", ['digit']);
        $this->assertDFAMatch($mdfa, "10", ['number']);
    }
     */
}