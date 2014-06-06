<?php
use TomK\CronParser\CronParser;

class CronTest extends PHPUnit_Framework_TestCase
{
  public function testCrons()
  {
    $this->assertTrue(CronParser::isDue('* * * * *'));

    $testTime = strtotime('midnight');
    $this->assertTrue(CronParser::isDue('0 * * * *', $testTime));
    $this->assertTrue(CronParser::isDue('0 0 * * *', $testTime));
    $this->assertFalse(CronParser::isDue('0 0 0 * *', $testTime));
  }
}