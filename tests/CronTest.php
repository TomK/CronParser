<?php
use TomK\CronParser\CronParser;

class CronTest extends PHPUnit_Framework_TestCase
{
  public function testCrons()
  {
    $this->assertTrue(CronParser::isDue('* * * * *'));

    $testTime = new DateTime('midnight');
    $this->assertTrue(CronParser::isDue('0 * * * *', $testTime));
    $this->assertTrue(CronParser::isDue('0 0 * * *', $testTime));

    // zero day is not valid
    $this->assertFalse(CronParser::isDue('0 0 0 * *', $testTime));
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