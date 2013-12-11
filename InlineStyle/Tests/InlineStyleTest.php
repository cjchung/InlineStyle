<?php

namespace InlineStyle\Tests;

/**
 * Test class for InlineStyle.
 * Generated by PHPUnit on 2010-03-10 at 21:52:44.
 */
use InlineStyle\InlineStyle;

class InlineStyleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \InlineStyle\InlineStyle
     */
    protected $object;
    protected $basedir;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->basedir = __DIR__."/testfiles";
        $this->object = new InlineStyle($this->basedir."/test.html");
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        unset($this->object);
    }

    public function testGetHTML()
    {
    	$this->assertEquals(
           	file_get_contents($this->basedir."/testGetHTML.html"),
            $this->object->getHTML());
    }

    public function testApplyStyleSheet()
    {
        $this->object->applyStyleSheet("p:not(.p2) { color: red }");
        $this->assertEquals(
            file_get_contents($this->basedir."/testApplyStylesheet.html"),
            $this->object->getHTML());
    }

    public function testApplyRule()
    {
        $this->object->applyRule("p:not(.p2)", "color: red");
        $this->assertEquals(
            file_get_contents($this->basedir."/testApplyStylesheet.html"),
            $this->object->getHTML());
    }

    public function testExtractStylesheets()
    {
        $stylesheets = $this->object->extractStylesheets(null, $this->basedir);
        $this->assertEquals(include $this->basedir."/testExtractStylesheets.php",$stylesheets);
    }

    public function testApplyExtractedStylesheet()
    {
        $stylesheets = $this->object->extractStylesheets(null, $this->basedir);
        $this->object->applyStylesheet($stylesheets);

        $this->assertEquals(
            file_get_contents($this->basedir."/testApplyExtractedStylesheet.html"),
            $this->object->getHTML());
    }

    public function testParseStyleSheet()
    {
        $parsed = $this->object->parseStylesheet("p:not(.p2) { color: red }");
        $this->assertEquals(
            array(array("p:not(.p2)", "color: red")),
            $parsed);
    }

    public function testParseStyleSheetWithComments()
    {
        $parsed = $this->object->parseStylesheet("p:not(.p2) { /* blah */ color: red }");
        $this->assertEquals(
            array(array("p:not(.p2)", "color: red")),
            $parsed);
    }

    public function testIllegalXmlUtf8Chars()
    {
        // check an exception is not thrown when loading up illegal XML UTF8 chars
        new InlineStyle("<html><body>".chr(2).chr(3).chr(4).chr(5)."</body></html>");
    }

    public function testGetScoreForSelector()
    {
        $this->assertEquals(
            array(1,1,3),
            $this->object->getScoreForSelector('ul#nav li.active a'),
            'ul#nav li.active a'
        );

        $this->assertEquals(
            array(0,2,3),
            $this->object->getScoreForSelector('body.ie7 .col_3 h2 ~ h2'),
            'body.ie7 .col_3 h2 ~ h2'
        );

        $this->assertEquals(
            array(1,0,2),
            $this->object->getScoreForSelector('#footer *:not(nav) li'),
            '#footer *:not(nav) li'
        );

        $this->assertEquals(
            array(0,0,7),
            $this->object->getScoreForSelector('ul > li ul li ol li:first-letter'),
            'ul > li ul li ol li:first-letter'
        );
    }

    function testSortingParsedStylesheet()
    {
        $parsed = $this->object->parseStylesheet(<<<CSS
ul#nav li.active a, body.ie7 .col_3 h2 ~ h2 {
    color: blue;
}

ul > li ul li ol li:first-letter {
    color: red;
}
CSS
);
        $this->assertEquals(array (
            array (
                'ul#nav li.active a',
                'color: blue',
            ),
            array (
                'body.ie7 .col_3 h2 ~ h2',
                'color: blue',
            ),
            array (
                'ul > li ul li ol li:first-letter',
                'color: red',
            ),
        ), $parsed);

        $parsed = $this->object->sortSelectorsOnSpecificity($parsed);

        $this->assertEquals(array (
            array (
                'ul > li ul li ol li:first-letter',
                'color: red',
            ),
            array (
                'body.ie7 .col_3 h2 ~ h2',
                'color: blue',
            ),
            array (
                'ul#nav li.active a',
                'color: blue',
            ),
        ), $parsed);
    }

    function testApplyStylesheetObeysSpecificity()
    {
        $this->object->applyStylesheet(<<<CSS
p {
    color: red;
}

.p2 {
    color: blue;
}

p.p2 {
    color: green;
}

CSS
);
        $this->assertEquals(
            file_get_contents($this->basedir."/testApplyStylesheetObeysSpecificity.html"),
            $this->object->getHTML());
    }

    function testDocDocumentDirectly()
    {
        $dom = new \DOMDocument();
        $dom->formatOutput = false;
        $dom->loadHTML('<!doctype html><html><body><div></div></body></html>');

        $this->object->loadDomDocument($dom);

        $this->object->applyRule('div', 'color: red');

        $this->assertEquals('<!DOCTYPE html>
<html><body><div style="color: red"></div></body></html>
', $dom->saveHTML());
    }


    function testNonWorkingPseudoSelectors()
    {
        // Regressiontest for #5
        $this->object->applyStylesheet(<<<CSS
ul#nav li.active a:link, body.ie7 .col_3:visited h2 ~ h2 {
    color: blue;
}

ul > li ul li:active ol li:first-letter {
    color: red;
}
CSS
        );
    }

    /**
     * Regression tests for #10 _styleToArray crashes when presented with an invalid property name
     */
    function testInvalidCssProperties()
    {
        $this->object->applyStylesheet(<<<CSS
ul {
    asohdtoairet;
    garbage: )&%)*(%);
}
CSS
);
    }
}
