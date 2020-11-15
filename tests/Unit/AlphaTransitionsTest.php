<?php


use makadev\RE2DFA\CharacterSet\AlphaSet;
use makadev\RE2DFA\FiniteAutomaton\AlphaTransition;
use makadev\RE2DFA\FiniteAutomaton\AlphaTransitionList;
use PHPUnit\Framework\TestCase;

class AlphaTransitionsTest extends TestCase {

    public function assertEdgeLike(int $fromNode, int $toNode, AlphaSet $alphaSet, AlphaTransition $transition): void {
        $this->assertEquals($fromNode, $transition->getFromNode());
        $this->assertEquals($toNode, $transition->getToNode());
        $this->assertNotNull($transition->getPayload());
        $this->assertTrue($transition->getAlphaSet()->equals($alphaSet));
    }

    public function testEmptyListIsEmpty(): void {
        $new = new AlphaTransitionList();
        $this->assertEquals(0, $new->count());
        $transitions = iterator_to_array($new->enumerator());
        $this->assertCount(0, $transitions);
    }

    public function testListAdd(): void {
        $alphaSet = new AlphaSet();
        $alphaSet->set(ord('a'));
        $alphaSet->set(ord('b'));
        $new = new AlphaTransitionList();
        $this->assertEquals(0, $new->count());
        $new->addTransition(1, 2, $alphaSet);
        $transitions = iterator_to_array($new->enumerator());
        $this->assertCount(1, $transitions);
        $this->assertEdgeLike(1, 2, $alphaSet, $transitions[0]);
        // add duplicate, implicit ignore
        $new->addTransition(1, 2, $alphaSet);
        $transitions = iterator_to_array($new->enumerator());
        $this->assertCount(1, $transitions);
        $this->assertEdgeLike(1, 2, $alphaSet, $transitions[0]);
        // add duplicate explicit
        $new->addTransition(1, 2, $alphaSet, true);
        $transitions = iterator_to_array($new->enumerator());
        $this->assertCount(2, $transitions);
        $this->assertEdgeLike(1, 2, $alphaSet, $transitions[0]);
        $this->assertEdgeLike(1, 2, $alphaSet, $transitions[1]);
    }
}