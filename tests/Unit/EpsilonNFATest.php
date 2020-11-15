<?php

use makadev\RE2DFA\CharacterSet\AlphaSet;
use makadev\RE2DFA\FiniteAutomaton\EpsilonNFA;
use makadev\RE2DFA\FiniteAutomaton\AlphaTransition;
use makadev\RE2DFA\FiniteAutomaton\EpsilonTransition;
use PHPUnit\Framework\TestCase;

class EpsilonNFATest extends TestCase {

    public function assertNFAMatch(EpsilonNFA $enfa, string $match): void {
        $this->assertTrue($enfa->simulateMatch($match), "eNFA Simulation did not match '" . $match . "', expected it should");
    }

    public function assertNoNFAMatch(EpsilonNFA $enfa, string $match): void {
        $this->assertFalse($enfa->simulateMatch($match), "eNFA Simulation did match '" . $match . "', expected it should not");
    }

    public function testEmptyENFA(): void {
        $enfa = new EpsilonNFA(new AlphaSet());
        $this->assertEquals(2, $enfa->getAllocator()->allocations());
        $this->assertEquals(0, $enfa->getStartNode());
        $this->assertEquals($enfa->getAllocator()->getLast(), $enfa->getFinalNode());
        $e = iterator_to_array($enfa->getEpsilonTransitions()->enumerator(false));
        $this->assertCount(1, $e);
        $this->assertEquals(0, $enfa->getAlphaTransitions()->count());
        /**
         * @var \makadev\RE2DFA\FiniteAutomaton\EpsilonTransition[] $e
         */
        $this->assertEquals($enfa->getStartNode(), $e[0]->getFromNode());
        $this->assertEquals($enfa->getFinalNode(), $e[0]->getToNode());
        //
        $this->assertNFAMatch($enfa, "");
        $this->assertNoNFAMatch($enfa, "a");
    }

    public function testSingleSetENFA(): void {
        $alpha = new AlphaSet();
        $alpha->set(10);
        $enfa = new EpsilonNFA($alpha);
        $this->assertEquals(2, $enfa->getAllocator()->allocations());
        $this->assertEquals(0, $enfa->getStartNode());
        $this->assertEquals($enfa->getAllocator()->getLast(), $enfa->getFinalNode());
        $this->assertEquals(0, $enfa->getEpsilonTransitions()->count());
        $a = iterator_to_array($enfa->getAlphaTransitions()->enumerator(false));
        $this->assertCount(1, $a);
        /**
         * @var AlphaTransition[] $a
         */
        $this->assertEquals($enfa->getStartNode(), $a[0]->getFromNode());
        $this->assertEquals($enfa->getFinalNode(), $a[0]->getToNode());
        $alpha = $a[0]->getAlphaSet();
        $this->assertTrue($a[0]->getAlphaSet()->test(10));
        //
        $this->assertNFAMatch($enfa, chr(10));
        $this->assertNoNFAMatch($enfa, "");
        $this->assertNoNFAMatch($enfa, chr(13));
    }

    public function testNoneOrOne(): void {
        $alpha = new AlphaSet();
        $alpha->set(10);
        $enfa = (new EpsilonNFA($alpha))->NoneOrOne();
        $this->assertNFAMatch($enfa, "");
        $this->assertNFAMatch($enfa, chr(10));
        $this->assertNoNFAMatch($enfa, chr(13));
        $this->assertNoNFAMatch($enfa, chr(10) . chr(10));
    }

    public function testMultiple(): void {
        $alpha = new AlphaSet();
        $alpha->set(10);
        $enfa = (new EpsilonNFA($alpha))->MultipleTimes();
        $this->assertNFAMatch($enfa, "");
        $this->assertNFAMatch($enfa, chr(10));
        $this->assertNFAMatch($enfa, chr(10) . chr(10));
        $this->assertNoNFAMatch($enfa, chr(13));
    }

    public function testAtLeastOne(): void {
        $alpha = new AlphaSet();
        $alpha->set(10);
        $enfa = (new EpsilonNFA($alpha))->AtLeastOne();
        $this->assertNoNFAMatch($enfa, "");
        $this->assertNFAMatch($enfa, chr(10));
        $this->assertNFAMatch($enfa, chr(10) . chr(10));
        $this->assertNoNFAMatch($enfa, chr(13));
    }

    public function testConcat(): void {
        $alpha1 = new AlphaSet();
        $alpha1->set(10);
        $alpha2 = new AlphaSet();
        $alpha2->set(13);
        $enfa = (new EpsilonNFA($alpha1))->Concat(new EpsilonNFA($alpha2));
        $this->assertNoNFAMatch($enfa, "");
        $this->assertNoNFAMatch($enfa, chr(10));
        $this->assertNoNFAMatch($enfa, chr(13));
        $this->assertNFAMatch($enfa, chr(10) . chr(13));
    }

    public function testChoice(): void {
        $alpha1 = new AlphaSet();
        $alpha1->set(10);
        $alpha2 = new AlphaSet();
        $alpha2->set(13);
        $enfa = (new EpsilonNFA($alpha1))->Choice(new EpsilonNFA($alpha2));
        $this->assertNoNFAMatch($enfa, "");
        $this->assertNFAMatch($enfa, chr(10));
        $this->assertNFAMatch($enfa, chr(13));
        $this->assertNoNFAMatch($enfa, chr(10) . chr(13));
        $this->assertNoNFAMatch($enfa, chr(13) . chr(10));
    }
}