<?php

namespace Fast\Tests\Support;

use PHPUnit\Framework\TestCase;
use Fast\Support\HtmlString;

class HtmlStringTest extends TestCase
{
    public function testToHtml()
    {
        $str = '<h1>foo</h1>';
        $html = new HtmlString('<h1>foo</h1>');
        $this->assertEquals($str, $html->toHtml());
    }

    public function testToString()
    {
        $str = '<h1>foo</h1>';
        $html = new HtmlString('<h1>foo</h1>');
        $this->assertEquals($str, (string) $html);
    }
}
