<?php
namespace TomK\CronParser;

class CronParser
{
  const MINUTE = 0;
  const HOUR = 1;
  const DAY = 2;
  const MONTH = 3;
  const WEEKDAY = 4;
  const YEAR = 5;
  protected static $_formats = array('i', 'H', 'd', 'm', 'w', 'Y');
  protected static $_min = array(0, 0, 1, 1, 0, 1970);
  protected static $_max = array(59, 23, 31, 12, 6, 2099);
  protected static $_intervalIds = array('M', 'H', 'D', 'M', 'D', 'Y');
  protected static $_groupings = array(
    self::YEAR   => array(self::YEAR),
    self::MONTH  => array(self::MONTH),
    self::DAY    => array(self::DAY, self::WEEKDAY),
    self::HOUR   => array(self::HOUR),
    self::MINUTE => array(self::MINUTE)
  );

  protected static function _getInterval($type, $interval)
  {
    return new \DateInterval(
      'P' . ($type <= 1 ? 'T' : '') . $interval . self::$_intervalIds[$type]
    );
  }

  protected static function _getPartDiff(
    $pattern, $type, \DateTime $time, $reverse = false
  )
  {
    $pattern = Expression::createFromPattern($pattern);
    if(!isset($pattern[$type]))
    {
      return 0;
    }
    $fmt  = self::$_formats[$type];
    $ret  = clone $time;
    $curr = intval($ret->format($fmt));

    $possibles = array();
    foreach($pattern[$type] as $part)
    {
      if($part['full'] == '*')
      {
        return false;
      }

      // start at $curr, increase to max, then start back at min
      // until this part matches the range or the val AND mod
      if(!$reverse)
      {
        $checks = array_merge(
          range($curr, self::$_max[$type]),
          range(
            self::$_min[$type],
            max($curr - 1, self::$_min[$type])
          )
        );
      }
      else
      {
        $checks = array_merge(
          range(
            max($curr, self::$_min[$type]),
            self::$_min[$type]
          ),
          range(self::$_max[$type], $curr - 1)
        );
      }
      $checks = array_unique($checks);

      $cv = -1;
      foreach($checks as $check)
      {
        $cv++;
        if(isset($part['mod']) && ($check % $part['mod']))
        {
          continue;
        }
        if(
          (isset($part['val']) && ($part['val'] == $check || $part['val'] == '*')) ||
          (isset($part['min']) && $check >= $part['min'] && $check <= $part['max'])
        )
        {
          $possibles[] = $cv;
          break;
        }
      }
    }

    return min($possibles);
  }

  /**
   * Returns true if pattern is valid format, false otherwise
   *
   * @param $pattern
   *
   * @return bool
   */
  public static function isValid($pattern)
  {
    try
    {
      Expression::createFromPattern($pattern);
    }
    catch(\Exception $e)
    {
      return false;
    }
    return true;
  }

  /**
   * Returns true if the time matches the pattern supplied.
   * Time defaults to current time.
   *
   * @param           $pattern
   * @param \DateTime $time
   *
   * @return bool
   */
  public static function isDue($pattern, \DateTime $time = null)
  {
    if($time === null)
    {
      $time = new \DateTime();
    }
    // trim time back to last round minute
    $time->setTime($time->format('H'), $time->format('i'));

    $expression = Expression::createFromPattern($pattern);

    foreach(self::$_formats as $pos => $fmt)
    {
      if($pos > 4 && !isset($expression[$pos]))
      {
        continue;
      }

      $cmp = intval($time->format($fmt));

      $posSuccess = false;
      foreach($expression[$pos] as $part)
      {
        if($part['full'] == '*' || $part['full'] == $cmp || $posSuccess)
        {
          $posSuccess = true;
          break;
        }

        // process order: range, modulus

        // doesn't match val
        if(isset($part['val']) && $part['val'] != '*' && $cmp != $part['val'])
        {
          continue;
        }

        // out of range
        if(isset($part['min']) && isset($part['max']) && ($cmp < $part['min'] || $cmp > $part['max']))
        {
          continue;
        }

        $offset = isset($part['min']) ? $part['min'] : 0;
        if(isset($part['mod']) && (($cmp - $offset) % $part['mod']) != 0)
        {
          continue;
        }

        $posSuccess = true;
      }
      if(!$posSuccess)
      {
        return false;
      }
    }
    return true;
  }

  /**
   * Returns a DateTime object of the next matching time from $time.
   * If $now is true, will accept $time as a match. Otherwise finds the next match in the future.
   *
   * @param           $pattern
   * @param \DateTime $time
   * @param bool      $now
   *
   * @return \DateTime
   * @throws \Exception
   */
  public static function nextRun($pattern, \DateTime $time = null, $now = false)
  {
    if($time === null)
    {
      $time = new \DateTime();
    }
    // trim time back to last round minute
    $time->setTime($time->format('H'), $time->format('i'));

    if(!$now)
    {
      // skip current minute
      $time->add(new \DateInterval('PT60S'));
    }

    $expression = Expression::createFromPattern($pattern);

    $orig = clone $time;
    $ret  = clone $time;

    foreach(self::$_groupings as $pos => $grp)
    {
      $values = array();
      foreach($grp as $type)
      {
        $v = self::_getPartDiff($expression, $type, $ret);
        if($v !== false)
        {
          $values[] = $v;
        }
      }
      if(!$values)
      {
        continue;
      }
      $diff = min($values);

      if(!$diff)
      {
        continue;
      }

      $fmt = self::$_formats[$pos];
      $ret->add(self::_getInterval($pos, $diff));
      // reset each previous segment to its minimum
      // (next hour should be at zero minutes)
      if($orig->format($fmt) != $ret->format($fmt))
      {
        for($i = $pos - 1; $i >= 0; $i--)
        {
          $ret->sub(
            self::_getInterval(
              $i,
              ($ret->format(self::$_formats[$i]) - self::$_min[$i])
            )
          );
        }
      }
    }

    if(!self::isDue($expression, $ret))
    {
      throw new \Exception(
        'Cron Error: Calculated nextRun is not due. '
        . $pattern . ' ' . $time->format(DATE_RFC822)
      );
    }

    return $ret;
  }

  /**
   * Returns a DateTime object of the previous matching time from $time.
   * If $now is true, will accept $time as a match. Otherwise finds the next match in the past.
   *
   * @param           $pattern
   * @param \DateTime $time
   * @param bool      $now
   *
   * @return \DateTime
   * @throws \Exception
   */
  public static function prevRun($pattern, \DateTime $time = null, $now = false)
  {
    if($time === null)
    {
      $time = new \DateTime();
    }
    // trim time back to last round minute
    $time->setTime($time->format('H'), $time->format('i'));

    if(!$now)
    {
      // skip current minute
      $time->sub(new \DateInterval('PT60S'));
    }
    $expression = Expression::createFromPattern($pattern);

    $ret = clone $time;

    foreach(self::$_groupings as $pos => $grp)
    {
      $values = array();
      foreach($grp as $type)
      {
        $v = self::_getPartDiff($expression, $type, $ret, true);
        if($v !== false)
        {
          $values[] = $v;
        }
      }
      if(!$values)
      {
        continue;
      }
      $diff = min($values);

      if(!$diff)
      {
        continue;
      }

      $ret->sub(self::_getInterval($pos, $diff));
    }

    if(!self::isDue($expression, $ret))
    {
      throw new \Exception(
        'Cron Error: Calculated nextRun is not due. '
        . $pattern . ' ' . $time->format(DATE_RFC822)
      );
    }

    return $ret;
  }
}
