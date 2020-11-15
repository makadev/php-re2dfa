<?php


use makadev\RE2DFA\FiniteAutomaton\EpsilonTransition;
use makadev\RE2DFA\FiniteAutomaton\EpsilonTransitionList;
use PHPUnit\Framework\TestCase;

class EpsilonTransitionsTest extends TestCase {

    public function assertEdgeLike(int $fromNode, int $toNode, EpsilonTransition $transition): void {
        $this->assertEquals($fromNode, $transition->getFromNode());
        $this->assertEquals($toNode, $transition->getToNode());
        $this->assertNull($transition->getPayload());
    }

    public function testEmptyListIsEmpty(): void {
        $new = new EpsilonTransitionList();
        $this->assertEquals(0, $new->count());
        $transitions = iterator_to_array($new->enumerator());
        $this->assertCount(0, $transitions);
    }

    public function testListAdd(): void {
        $new = new EpsilonTransitionList();
        $this->assertEquals(0, $new->count());
        $new->addTransition(1, 2);
        $transitions = iterator_to_array($new->enumerator());
        $this->assertCount(1, $transitions);
        $this->assertEdgeLike(1, 2, $transitions[0]);
        // add duplicate, implicit ignore
        $new->addTransition(1, 2);
        $transitions = iterator_to_array($new->enumerator());
        $this->assertCount(1, $transitions);
        $this->assertEdgeLike(1, 2, $transitions[0]);
        // add duplicate explicit
        $new->addTransition(1, 2, true);
        $transitions = iterator_to_array($new->enumerator());
        $this->assertCount(2, $transitions);
        $this->assertEdgeLike(1, 2, $transitions[0]);
        $this->assertEdgeLike(1, 2, $transitions[1]);
    }

    public function testCopyFrom(): void {
        $new = new EpsilonTransitionList();
        $new->addTransition(1, 2);
        $second = new EpsilonTransitionList();
        $second->addTransition(2, 3);
        $new->copyFrom($second);
        $transitions = iterator_to_array($new->enumerator());
        usort($transitions, fn($t1, $t2) => $t1->getFromNode() <=> $t2->getFromNode());
        $this->assertCount(2, $transitions);
        $this->assertEdgeLike(1, 2, $transitions[0]);
        $this->assertEdgeLike(2, 3, $transitions[1]);
    }

    public function testCopyFromRebase(): void {
        $new = new EpsilonTransitionList();
        $new->addTransition(1, 2);
        $second = new EpsilonTransitionList();
        $second->addTransition(2, 3);
        $new->copyFrom($second, 1);
        $transitions = iterator_to_array($new->enumerator());
        usort($transitions, fn($t1, $t2) => $t1->getFromNode() <=> $t2->getFromNode());
        $this->assertCount(2, $transitions);
        $this->assertEdgeLike(1, 2, $transitions[0]);
        $this->assertEdgeLike(3, 4, $transitions[1]);
    }
}