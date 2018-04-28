<?php
namespace Fast\Tests\Support;

use Fast\Support\InteractsWithTime;
use PHPUnit\Framework\TestCase;
use \DateTimeImmutable;
use \DateInterval;

class InteractsWithTimeInstance {
    use InteractsWithTime;

    /**
     * Get the number of seconds until the given DateTime.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @return int
     */
    public function callSecondsUntil($delay) {
        return $this->secondsUntil($delay);
    }

    /**
     * Get the "available at" UNIX timestamp.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @return int
     */
    public function callAvailableAt($delay) {
        return $this->availableAt($delay);
    }

    /**
     * If the given value is an interval, convert it to a DateTime instance.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @return \DateTimeInterface|int
     */
    public function callParseDateInterval($delay) {
        return $this->parseDateInterval($delay);
    }

    /**
     * Get the current system time as a UNIX timestamp.
     *
     * @return int
     */
    public function callCurrentTime() {
        return $this->currentTime();
    }
}

class InteractsWithTimeTest extends TestCase
{
    /**
     * @var InteractsWithTimeInstance
     */
    protected $instance = null;

    protected function setUp()
    {
        parent::setUp();
        $this->instance = new InteractsWithTimeInstance();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @throws \Exception
     */
    public function testSecondsUntil() {
        $delay = new DateTimeImmutable();
        $result = $this->instance->callSecondsUntil($delay);
        $this->assertTrue($result < 10);

        $result = $this->instance->callSecondsUntil(5);
        $this->assertEquals(5, $result);

        $delay = new DateInterval('PT2S');
        $result = $this->instance->callSecondsUntil($delay);
        $this->assertEquals(2, $result);
    }

    /**
     * @throws \Exception
     */
    public function testAvailableAt() {
        $delay = new DateTimeImmutable();
        $result = $this->instance->callAvailableAt($delay);
        $this->assertEquals($delay->getTimestamp(), $result);

        $result = $this->instance->callAvailableAt(100);
        $this->assertTrue(abs($delay->getTimestamp() + 100 - $result) < 10);
    }

    /**
     * @throws \Exception
     */
    public function testCurrentTime() {
        $delay = new DateTimeImmutable();
        $result = $this->instance->callCurrentTime();
        $this->assertTrue(abs($delay->getTimestamp() - $result) < 10);
    }
}
