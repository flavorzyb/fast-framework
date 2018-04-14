<?php

namespace Fast\Tests\Support;

use DateTime;
use DateTimeInterface;
use Fast\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Carbon\Carbon as BaseCarbon;

class CarbonTest extends TestCase
{
    /**
     * @var \Fast\Support\Carbon
     */
    protected $now;

    public function setUp()
    {
        parent::setUp();

        Carbon::setTestNow($this->now = Carbon::create(2017, 6, 27, 13, 14, 15, 'UTC'));
    }

    public function tearDown()
    {
        Carbon::setTestNow();
        Carbon::serializeUsing(null);

        parent::tearDown();
    }

    public function testInstance()
    {
        $this->assertInstanceOf(DateTime::class, $this->now);
        $this->assertInstanceOf(DateTimeInterface::class, $this->now);
        $this->assertInstanceOf(BaseCarbon::class, $this->now);
        $this->assertInstanceOf(Carbon::class, $this->now);
    }

    public function testCarbonAllowsCustomSerializer()
    {
        Carbon::serializeUsing(function (Carbon $carbon) {
            return $carbon->getTimestamp();
        });

        $result = json_decode(json_encode($this->now), true);

        $this->assertSame(1498569255, $result);
    }

    public function testCarbonCanSerializeToJson()
    {
        $this->assertSame([
            'date' => '2017-06-27 13:14:15.000000',
            'timezone_type' => 3,
            'timezone' => 'UTC',
        ], $this->now->jsonSerialize());
    }

    public function testSetStateReturnsCorrectType()
    {
        $carbon = Carbon::__set_state([
            'date' => '2017-06-27 13:14:15.000000',
            'timezone_type' => 3,
            'timezone' => 'UTC',
        ]);

        $this->assertInstanceOf(Carbon::class, $carbon);
    }

    public function testDeserializationOccursCorrectly()
    {
        $carbon = new Carbon('2017-06-27 13:14:15.000000');
        $serialized = 'return '.var_export($carbon, true).';';
        $deserialized = eval($serialized);

        $this->assertInstanceOf(Carbon::class, $deserialized);
    }
}
