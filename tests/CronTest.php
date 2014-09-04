<?php
use TomK\CronParser\CronParser;

class CronTest extends PHPUnit_Framework_TestCase
{
  public function testCrons()
  {
    CronParser::nextRun('0 0 ' . date('j') . ' * *');

    $this->assertTrue(CronParser::isDue('* * * * *'));

    $testTime = new DateTime('2014-01-01 00:00');
    $this->assertTrue(CronParser::isDue('0 * * * *', $testTime));
    $this->assertTrue(CronParser::isDue('0 0 * * *', $testTime));

    // zero day is not valid
    $this->assertFalse(CronParser::isDue('0 0 0 * *', $testTime));

    // tuesday
    $nextTuesday = CronParser::nextRun('0 0 * * 2', $testTime);
    $this->assertEquals(2, $nextTuesday->format('N'));

    // next year
    $farFuture = CronParser::nextRun('* * * * * 2099', $testTime);
    $this->assertEquals(2099, $farFuture->format('Y'));

    // next tuesday 15th
    $tuesdayFifteenth = CronParser::nextRun('* * 15 * 2', $testTime);
    $this->assertEquals(2099, $tuesdayFifteenth->format('Y'));
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testNoPattern()
  {
    CronParser::isDue('');
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testInvalidPattern()
  {
    CronParser::isDue('not valid');
    $this->fail('Failed to throw InvalidArgumentException');
  }
}