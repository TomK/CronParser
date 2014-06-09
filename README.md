CronParser
==========

PHP class to parse CRON format and check for time matches, or determine next and previous matching times.

Installation
------------

Add this package to your composer.json required section

```
"required": {
  "tomk/cronparser":"*"
}
```

Usage
-----

The following public methods are available.

```php
// Returns true if pattern is valid format, false otherwise
CronParser::isValid(string $pattern);

// Returns true if the time matches the pattern supplied.
CronParser::isDue(string $pattern [, $time = time()]);

// Returns a DateTime object of the next matching time from $time.
// If $now is true, will accept $time as a match. Otherwise finds the next match in the future.
CronParser::nextRun(string $pattern [, $time = time() [, $now = false]]);

// Returns a DateTime object of the previous matching time from $time.
// If $now is true, will accept $time as a match. Otherwise finds the next match in the past.
CronParser::prevRun(string $pattern [, $time = time() [, $now = false]]);
```

CRON expression
---------------

Please see CRON format on wikipedia for reference: [http://en.wikipedia.org/wiki/Cron#CRON_expression]
