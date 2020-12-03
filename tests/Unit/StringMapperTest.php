<?php


use makadev\RE2DFA\StringSet\StringMapper;
use PHPUnit\Framework\TestCase;

class StringMapperTest extends TestCase {

    public function testEmptyMappingIsEmpty(): void {
        $empty = new StringMapper();
        $this->assertEquals(0, $empty->count());
    }

    public function testInsertLookup(): void {
        $sm = new StringMapper();
        $this->assertFalse($sm->has('test'));
        $id = $sm->lookupAdd('test');
        $this->assertTrue($sm->has('test'));
        $id2 = $sm->lookupAdd('test');
        $this->assertTrue($sm->has('test'));
        $this->assertEquals($id, $id2);
    }
}