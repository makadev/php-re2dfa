<?php


namespace makadev\RE2DFA;


use makadev\RE2DFA\FiniteAutomaton\EpsilonNFA;
use makadev\RE2DFA\RegEx\RegExParser;
use makadev\RE2DFA\RegEx\RegExParserException;
use PHPUnit\Framework\TestCase;

class RegExParserTest extends TestCase {

    public function assertNFAMatch(EpsilonNFA $enfa, string $match): void {
        $this->assertTrue($enfa->simulateMatch($match), "eNFA Simulation did not match '" . $match . "', expected it should");
    }

    public function assertNoNFAMatch(EpsilonNFA $enfa, string $match): void {
        $this->assertFalse($enfa->simulateMatch($match), "eNFA Simulation did match '" . $match . "', expected it should not");
    }

    public function testEmptyMatchRegex(): void {
        $rp = new RegExParser("");
        $enfa = $rp->build();
        $this->assertNotNull($enfa);
        $this->assertNFAMatch($enfa, "");
    }

    public function testTrivialStringRegex(): void {
        $rp = new RegExParser("abcdef");
        $enfa = $rp->build();
        $this->assertNotNull($enfa);
        $this->assertNFAMatch($enfa, "abcdef");
        $this->assertNoNFAMatch($enfa, "");
        $this->assertNoNFAMatch($enfa, "abcde");
        $this->assertNoNFAMatch($enfa, "abcdeff");
    }

    public function testBracesRegex(): void {
        $rp = new RegExParser("(a)((b)((c)))");
        $enfa = $rp->build();
        $this->assertNotNull($enfa);
        $this->assertNFAMatch($enfa, "abc");
        $this->assertNoNFAMatch($enfa, "");
        $this->assertNoNFAMatch($enfa, "ab");
        $this->assertNoNFAMatch($enfa, "abcc");
    }

    public function testTrivialChoiceRegex(): void {
        $rp = new RegExParser("a|b");
        $enfa = $rp->build();
        $this->assertNotNull($enfa);
        $this->assertNFAMatch($enfa, "a");
        $this->assertNFAMatch($enfa, "b");
        $this->assertNoNFAMatch($enfa, "");
        $this->assertNoNFAMatch($enfa, "ab");
        $this->assertNoNFAMatch($enfa, "ba");
        $this->assertNoNFAMatch($enfa, "aa");
        $this->assertNoNFAMatch($enfa, "bb");
    }

    public function testEmptyMatchChoiceRegex(): void {
        // actual alternate syntax for (a?)
        $rp = new RegExParser("(|a)");
        $enfa = $rp->build();
        $this->assertNotNull($enfa);
        $this->assertNFAMatch($enfa, "");
        $this->assertNFAMatch($enfa, "a");
    }

    public function testEmptyMatchChoiceEmptynessRegex(): void {
        // actual alternate syntax for ""...
        $rp = new RegExParser("(|)");
        $enfa = $rp->build();
        $this->assertNotNull($enfa);
        $this->assertNFAMatch($enfa, "");
        $this->assertNoNFAMatch($enfa, "|");
    }

    public function testTrivialNoneOrOneRegex(): void {
        $rp = new RegExParser("a?");
        $enfa = $rp->build();
        $this->assertNotNull($enfa);
        $this->assertNFAMatch($enfa, "");
        $this->assertNFAMatch($enfa, "a");
        $this->assertNoNFAMatch($enfa, "aa");
    }

    public function testTrivialStarRegex(): void {
        $rp = new RegExParser("a*");
        $enfa = $rp->build();
        $this->assertNotNull($enfa);
        $this->assertNFAMatch($enfa, "");
        $this->assertNFAMatch($enfa, "a");
        $this->assertNFAMatch($enfa, "aa");
    }

    public function testTrivialPlusRegex(): void {
        $rp = new RegExParser("a+");
        $enfa = $rp->build();
        $this->assertNotNull($enfa);
        $this->assertNoNFAMatch($enfa, "");
        $this->assertNFAMatch($enfa, "a");
        $this->assertNFAMatch($enfa, "aa");
    }

    public function testNoneOrOneRegex(): void {
        $rp = new RegExParser("a(b?)c");
        $enfa = $rp->build();
        $this->assertNotNull($enfa);
        $this->assertNFAMatch($enfa, "abc");
        $this->assertNFAMatch($enfa, "ac");
        $this->assertNoNFAMatch($enfa, "");
        $this->assertNoNFAMatch($enfa, "c");
        $this->assertNoNFAMatch($enfa, "bc");
        $this->assertNoNFAMatch($enfa, "a");
        $this->assertNoNFAMatch($enfa, "ab");
    }

    public function testEmptyMatchSetRegex(): void {
        $rp = new RegExParser("[]");
        $enfa = $rp->build();
        $this->assertNotNull($enfa);
        $this->assertNFAMatch($enfa, "");
    }

    public function testCharSet(): void {
        $rp = new RegExParser("[abco]");
        $enfa = $rp->build();
        $this->assertNotNull($enfa);
        $this->assertNoNFAMatch($enfa, "");
        $this->assertNFAMatch($enfa, "a");
        $this->assertNFAMatch($enfa, "b");
        $this->assertNFAMatch($enfa, "c");
        $this->assertNoNFAMatch($enfa, "d");
        $this->assertNFAMatch($enfa, "o");
        $this->assertNoNFAMatch($enfa, "ab");
    }

    public function testCharSetInverted(): void {
        $rp = new RegExParser("[^abco]");
        $enfa = $rp->build();
        $this->assertNotNull($enfa);
        $this->assertNoNFAMatch($enfa, "");
        $this->assertNoNFAMatch($enfa, "a");
        $this->assertNoNFAMatch($enfa, "b");
        $this->assertNoNFAMatch($enfa, "c");
        $this->assertNFAMatch($enfa, "d");
        $this->assertNoNFAMatch($enfa, "o");
        $this->assertNoNFAMatch($enfa, "ab");
    }

    public function testCharRange(): void {
        $rp = new RegExParser("[a-c]");
        $enfa = $rp->build();
        $this->assertNotNull($enfa);
        $this->assertNoNFAMatch($enfa, "");
        $this->assertNFAMatch($enfa, "a");
        $this->assertNFAMatch($enfa, "b");
        $this->assertNFAMatch($enfa, "c");
        $this->assertNoNFAMatch($enfa, "d");
        $this->assertNoNFAMatch($enfa, "ab");
    }

    public function testCharSetSpecialCaseFullComplement(): void {
        $rp = new RegExParser("[^]");
        $enfa = $rp->build();
        $this->assertNotNull($enfa);
        $this->assertNoNFAMatch($enfa, "");
        $this->assertNFAMatch($enfa, "a");
    }

    public function testCharSetSpecialCaseNoRangeHyphen(): void {
        $rp = new RegExParser("[a-]");
        $enfa = $rp->build();
        $this->assertNotNull($enfa);
        $this->assertNoNFAMatch($enfa, "");
        $this->assertNFAMatch($enfa, "a");
        $this->assertNFAMatch($enfa, "-");
    }

    public function testCharSetOpsNotInterpreted(): void {
        $rp = new RegExParser("[()+|*?]");
        $enfa = $rp->build();
        $this->assertNotNull($enfa);
        $this->assertNoNFAMatch($enfa, "");
        $this->assertNFAMatch($enfa, "(");
        $this->assertNFAMatch($enfa, ")");
        $this->assertNFAMatch($enfa, "+");
        $this->assertNFAMatch($enfa, "|");
        $this->assertNFAMatch($enfa, "*");
        $this->assertNFAMatch($enfa, "?");
    }

    public function testCharCode(): void {
        $rp = new RegExParser("#" . str_pad((string)ord('a'), 3, "0", STR_PAD_LEFT));
        $enfa = $rp->build();
        $this->assertNotNull($enfa);
        $this->assertNoNFAMatch($enfa, "");
        $this->assertNFAMatch($enfa, "a");
        $this->assertNoNFAMatch($enfa, "b");
        $this->assertNoNFAMatch($enfa, "aa");
    }

    public function testCharCodeRange(): void {
        $start = "#" . str_pad((string)ord('a'), 3, "0", STR_PAD_LEFT);
        $end = "#" . str_pad((string)ord('y'), 3, "0", STR_PAD_LEFT);
        $rp = new RegExParser("[$start-$end]");
        $enfa = $rp->build();
        $this->assertNotNull($enfa);
        $this->assertNoNFAMatch($enfa, "");
        $this->assertNFAMatch($enfa, "a");
        $this->assertNFAMatch($enfa, "b");
        $this->assertNFAMatch($enfa, "x");
        $this->assertNoNFAMatch($enfa, "z");
    }

    public function testCharCodeNoCode(): void {
        $rp = new RegExParser("##");
        $enfa = $rp->build();
        $this->assertNotNull($enfa);
        $this->assertNoNFAMatch($enfa, "");
        $this->assertNFAMatch($enfa, "##");
        $this->assertNoNFAMatch($enfa, "#");
        $this->assertNoNFAMatch($enfa, "###");
    }

    public function testComplexRegex(): void {
        $rp = new RegExParser('([-+]?)([0-9]*)([.]?)([0-9]+)(([eE]([-+]?)([0-9]+))?)');
        $enfa = $rp->build();
        $this->assertNotNull($enfa);
        $this->assertNoNFAMatch($enfa, "");
        $this->assertNFAMatch($enfa, "10");
        $this->assertNFAMatch($enfa, "+213");
        $this->assertNFAMatch($enfa, "-34");
        $this->assertNFAMatch($enfa, "3434.4E-2344");
        $this->assertNFAMatch($enfa, "234.4E+24");
        $this->assertNFAMatch($enfa, "1123E2");
        $this->assertNFAMatch($enfa, "1213.4");
    }

    public function testFaultyUnaryOpsWithoutArgAMO(): void {
        $this->expectException(RegExParserException::class);
        $rp = new RegExParser('a(?)');
        $enfa = $rp->build();
        $this->assertNull($enfa);
    }

    public function testFaultyUnaryOpsWithoutArgALN(): void {
        $this->expectException(RegExParserException::class);
        $rp = new RegExParser('a(*)');
        $enfa = $rp->build();
        $this->assertNull($enfa);
    }

    public function testFaultyUnaryOpsWithoutArgALO(): void {
        $this->expectException(RegExParserException::class);
        $rp = new RegExParser('a(+)');
        $enfa = $rp->build();
        $this->assertNull($enfa);
    }

    public function testFaultyStackOpsCHCB(): void {
        $this->expectException(RegExParserException::class);
        $rp = new RegExParser('|)');
        $enfa = $rp->build();
        $this->assertNull($enfa);
    }

    public function testFaultyStackOpsRECB(): void {
        $this->expectException(RegExParserException::class);
        $rp = new RegExParser('a)');
        $enfa = $rp->build();
        $this->assertNull($enfa);
    }

    public function testFaultyStackOpsCBRE(): void {
        $this->expectException(RegExParserException::class);
        $rp = new RegExParser(')a');
        $enfa = $rp->build();
        $this->assertNull($enfa);
    }

    public function testFaultyStackOpsOBRE(): void {
        $this->expectException(RegExParserException::class);
        $rp = new RegExParser('(a');
        $enfa = $rp->build();
        $this->assertNull($enfa);
    }

    public function testMissingClosingBraceSet(): void {
        $this->expectException(RegExParserException::class);
        $rp = new RegExParser('[a-z');
        $enfa = $rp->build();
        $this->assertNull($enfa);
    }

    public function testPrecedenceConcatBeforeChoice(): void {
        $rp = new RegExParser("this|th(at|em)");
        $enfa = $rp->build();
        $this->assertNotNull($enfa);
        $this->assertNFAMatch($enfa, "this");
        $this->assertNFAMatch($enfa, "that");
        $this->assertNFAMatch($enfa, "them");
    }

    public function testPrecedencePostFixBeforeConcat(): void {
        $rp = new RegExParser("aa?(bc*)de+f");
        $enfa = $rp->build();
        $this->assertNotNull($enfa);
        $this->assertNFAMatch($enfa, "abdef");
        $this->assertNFAMatch($enfa, "aabcccdeef");
        $this->assertNoNFAMatch($enfa, "aabcbcdef");
        $this->assertNoNFAMatch($enfa, "aabcdedef");
    }
}
