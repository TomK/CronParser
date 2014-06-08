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

    // zero day is not valid
    $this->assertFalse(CronParser::isDue('0 0 0 * *', $testTime));
  }

  public function testExceptions()
  {
    try
    {
      CronParser::isDue('');
      $this->fail('Failed to throw InvalidArgumentException');
    }
    catch(InvalidArgumentException $e)
    {
    }

    try
    {
      CronParser::isDue('not valid');
      $this->fail('Failed to throw InvalidArgumentException');
    }
    catch(InvalidArgumentException $e)
    {
    }

    try
    {
      CronParser::isDue('* * * * *', new stdClass());
      $this->fail('Failed to throw InvalidArgumentException');
    }
    catch(InvalidArgumentException $e)
    {
    }
  }
}