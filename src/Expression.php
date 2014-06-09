<?php
/**
 * Created by PhpStorm.
 * User: Oridan
 * Date: 08/06/2014
 * Time: 18:30
 */

namespace TomK\CronParser;

class Expression implements \ArrayAccess
{
  protected static $_patternCache = [];

  protected static $_templates = array(
    '@yearly'  => '0 0 1 1 *',
    '@monthly' => '0 0 1 * *',
    '@weekly'  => '0 0 * * 0',
    '@daily'   => '0 0 * * *',
    '@hourly'  => '0 * * * *'
  );

  public static function createFromPattern($pattern)
  {
    if(!$pattern)
    {
      throw new \InvalidArgumentException('Invalid cron pattern');
    }
    if($pattern instanceof Expression)
    {
      return $pattern;
    }

    //cache statically
    if(isset(self::$_patternCache[$pattern]))
    {
      return self::$_patternCache[$pattern];
    }

    if(is_string($pattern))
    {
      if(isset(self::$_templates[$pattern]))
      {
        $pattern = self::$_templates[$pattern];
      }
      $split = preg_split('/\s+/', $pattern, 5);
    }
    else
    {
      throw new \InvalidArgumentException('Invalid cron pattern');
    }

    if(count($split) < 5)
    {
      throw new \InvalidArgumentException('Invalid cron pattern');
    }

    foreach($split as $k => $segment)
    {
      $split[$k] = explode(',', $segment);

      foreach($split[$k] as $kk => $part)
      {
        preg_match(
          '/(((?<min>[0-9]+)\-(?<max>[0-9]+))|(?<val>[*0-9]+))(\/(?<mod>[0-9]+))?/',
          $part,
          $matches
        );

        $matches['full'] = $matches[0];
        foreach($matches as $mk => $mv)
        {
          if(is_numeric($mk) || $mv === '')
          {
            unset($matches[$mk]);
          }
        }
        $split[$k][$kk] = $matches;
      }
    }

    self::$_patternCache[$pattern] = new static($split);
    return self::$_patternCache[$pattern];
  }

  protected $_schema;

  protected function __construct($schema)
  {
    $this->_schema = $schema;
  }

  public function offsetExists($offset)
  {
    return isset($this->_schema[$offset]);
  }

  public function offsetGet($offset)
  {
    return $this->_schema[$offset];
  }

  public function offsetSet($offset, $value)
  {
    throw new \Exception('Cannot set Expression values directly');
  }

  /**
   * (PHP 5 &gt;= 5.0.0)<br/>
   * Offset to unset
   * @link http://php.net/manual/en/arrayaccess.offsetunset.php
   *
   * @param mixed $offset <p>
   *                      The offset to unset.
   *                      </p>
   *
   * @return void
   */
  public function offsetUnset($offset)
  {
    unset($this->_schema[$offset]);
  }
}