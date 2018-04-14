<?php

namespace Fast\Tests\Support;

use Fast\Support\Pluralizer;
use PHPUnit\Framework\TestCase;

class PluralizerTest extends TestCase
{
    public function testBasicSingular()
    {
        $this->assertEquals('child', Pluralizer::singular('children'));
    }

    public function testBasicPlural()
    {
        $this->assertEquals('children', Pluralizer::plural('child'));
        $this->assertEquals('cod', Pluralizer::plural('cod'));
    }

    public function testBasicPluralWithCountOne()
    {
        $this->assertEquals('child', Pluralizer::plural('child', 1));
        $this->assertEquals('audio', Pluralizer::plural('audio'));
        $this->assertEquals('children', Pluralizer::plural('chilD'));
    }

    public function testCaseSensitiveSingularUsage()
    {
        $this->assertEquals('Child', Pluralizer::singular('Children'));
        $this->assertEquals('CHILD', Pluralizer::singular('CHILDREN'));
        $this->assertEquals('Test', Pluralizer::singular('Tests'));
    }

    public function testCaseSensitiveSingularPlural()
    {
        $this->assertEquals('Children', Pluralizer::plural('Child'));
        $this->assertEquals('CHILDREN', Pluralizer::plural('CHILD'));
        $this->assertEquals('Tests', Pluralizer::plural('Test'));
    }

    public function testIfEndOfWordPlural()
    {
        $this->assertEquals('VortexFields', Pluralizer::plural('VortexField'));
        $this->assertEquals('MatrixFields', Pluralizer::plural('MatrixField'));
        $this->assertEquals('IndexFields', Pluralizer::plural('IndexField'));
        $this->assertEquals('VertexFields', Pluralizer::plural('VertexField'));
    }
}
