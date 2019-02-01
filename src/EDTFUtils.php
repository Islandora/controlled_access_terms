<?

namespace Drupal\controlled_access_terms;

use Datetime;

/**
 * Utility functions for working with Extended Date Time Format.
 */
class EDTFUtils {

  // EDTF Date Parse REGEX Array Positions.
  const DATE_PARSE_REGEX = '([%\?~])?(-?Y?-?([\dX]+)(E\d)?(S\d)?)([%\?~])?-?([%\?~])?([\dX]{2})?([%\?~])?-?([%\?~])?([\dX]{2})?([%\?~])?';
  const FULL_MATCH             =  0;
  const QUALIFIER_YEAR_ONLY    =  1;
  const YEAR_FULL              =  2;
  const YEAR_BASE              =  3;
  const YEAR_EXPONENT          =  4;
  const YEAR_SIGNIFICANT_DIGIT =  5;
  const QUALIFIER_YEAR         =  6;
  const QUALIFIER_MONTH_ONLY   =  7;
  const MONTH                  =  8;
  const QUALIFIER_MONTH        =  9;
  const QUALIFIER_DAY_ONLY     = 10;
  const DAY                    = 11;
  const QUALIFIER_DAY          = 12;

  /**
   * Month/Season to text map.
   *
   * @var array
   */
  const MONTHS_MAP = [
    '01' => ['mmm' => 'Jan', 'mmmm' => 'January'],
    '02' => ['mmm' => 'Feb', 'mmmm' => 'February'],
    '03' => ['mmm' => 'Mar', 'mmmm' => 'March'],
    '04' => ['mmm' => 'Apr', 'mmmm' => 'April'],
    '05' => ['mmm' => 'May', 'mmmm' => 'May'],
    '06' => ['mmm' => 'Jun', 'mmmm' => 'June'],
    '07' => ['mmm' => 'Jul', 'mmmm' => 'July'],
    '08' => ['mmm' => 'Aug', 'mmmm' => 'August'],
    '09' => ['mmm' => 'Sep', 'mmmm' => 'September'],
    '10' => ['mmm' => 'Oct', 'mmmm' => 'October'],
    '11' => ['mmm' => 'Nov', 'mmmm' => 'November'],
    '12' => ['mmm' => 'Dec', 'mmmm' => 'December'],
    '21' => ['mmm' => 'Spr', 'mmmm' => 'Spring'],
    '22' => ['mmm' => 'Sum', 'mmmm' => 'Summer'],
    '23' => ['mmm' => 'Aut', 'mmmm' => 'Autumn'],
    '24' => ['mmm' => 'Win', 'mmmm' => 'Winter'],
    '25' => ['mmm' => 'Spr', 'mmmm' => 'Spring - Northern Hemisphere'],
    '26' => ['mmm' => 'Sum', 'mmmm' => 'Summer - Northern Hemisphere'],
    '27' => ['mmm' => 'Aut', 'mmmm' => 'Autumn - Northern Hemisphere'],
    '28' => ['mmm' => 'Win', 'mmmm' => 'Winter - Northern Hemisphere'],
    '29' => ['mmm' => 'Spr', 'mmmm' => 'Spring - Southern Hemisphere'],
    '30' => ['mmm' => 'Sum', 'mmmm' => 'Summer - Southern Hemisphere'],
    '31' => ['mmm' => 'Aut', 'mmmm' => 'Autumn - Southern Hemisphere'],
    '32' => ['mmm' => 'Win', 'mmmm' => 'Winter - Southern Hemisphere'],
    '33' => ['mmm' => 'Q1', 'mmmm' => 'Quarter 1'],
    '34' => ['mmm' => 'Q2', 'mmmm' => 'Quarter 2'],
    '35' => ['mmm' => 'Q3', 'mmmm' => 'Quarter 3'],
    '36' => ['mmm' => 'Q4', 'mmmm' => 'Quarter 4'],
    // I'm making up the rest of these abbreviations
    // because I can't find standardized ones.
    '37' => ['mmm' => 'Quad1', 'mmmm' => 'Quadrimester 1'],
    '38' => ['mmm' => 'Quad2', 'mmmm' => 'Quadrimester 2'],
    '39' => ['mmm' => 'Quad3', 'mmmm' => 'Quadrimester 3'],
    '40' => ['mmm' => 'Sem1', 'mmmm' => 'Semestral 1'],
    '41' => ['mmm' => 'Sem2', 'mmmm' => 'Semestral 2'],
  ];


  /**
   * Validate an EDTF expression.
   *
   * @param string $edtf_text
   *   The datetime string.
   *
   * @return array
   *   Array of error messages. Valid if empty.
   */
  public static function validate($edtf_text, $intervals = TRUE, $sets = TRUE, $strict = FALSE) {
    $msgs = [];
    // Sets.
    if ($sets) {
      if (strpos($edtf_text, '[') !== FALSE || strpos($edtf_text, '{') !== FALSE) {
        // Test for valid enclosing characters and valid characters inside.
        $match = preg_match('/^([\[,\{])[\d,\-,X,Y,E,S,.]*([\],\}])$/', $edtf_text);
        if (!$match || $match[1] !== $match[2]) {
          $msgs[] = "The set is improperly encoded.";
        }
        // Test each date in set.
        foreach (preg_split('/(,|\.\.)/', trim($edtf_text, '{}[]')) as $date) {
          $msgs = array_merge($msgs, self::validate_date($date, $strict));
        }
        return $msgs;
      }
    }
    // Intervals.
    if ($intervals) {
      if (strpos($edtf_text, 'T') !== FALSE) {
        $msgs[] = "Date intervals cannot include times.";
      }
      foreach (explode('/', $$edtf_text) as $date) {
        if (!empty($date) && !$date === '..') {
          $msgs = array_merge($msgs, self::validate_date($date, $strict));
        }
      }
      return $msgs;
    }
    // Single date (we assume at this point).
    return self::validate_date($edtf_text, $strict);
  }

  /**
   * Validate a single date.
   *
   * @param string $datetime_str
   *   The datetime string.
   *
   * @return array
   *   Array of error messages. Valid if empty.
   */
  public static function validate_date($datetime_str, $strict = FALSE) {
    $msgs = [];

    list($date, $time) = explode('T', $datetime_str);

    preg_match(self::DATE_PARSE_REGEX, $date, $parsed_date);

    // "Something" is wrong with the provided date if it doesn't round-trip.
    // Includes (non-exhaustive):
    //   - no invalid characters present,
    //   - two-digit months and days, and
    //   - capturing group qualifiers.
    if ($date !== $parsed_date[self::FULL_MATCH]) {
      $msgs[] = ["Could not parse the date '$date'",];
    }

    // Year.
    if ((strpos($parsed_date[self::YEAR_FULL], 'Y') === 0) {
      if ($strict){
        $msgs[] = ["Extended years are not supported with the 'strict dates' option enabled."];
      }
      // Expand exponents.
      if (!empty($parsed_date[self::YEAR_EXPONENT])) {
        $exponent = intval(substr($parsed_date[self::YEAR_EXPONENT], 1));
        $parsed_date[self::YEAR_BASE] = strval((10 ** $exponent) * intval($parsed_date[self::YEAR_BASE]));
        $parsed_date[self::YEAR_BASE] = self::expand_year($parsed_date[self::YEAR_FULL], $parsed_date[self::YEAR_BASE], $parsed_date[self::YEAR_EXPONENT]);
      }
    } elseif (length($parsed_date[self::YEAR_BASE]) > 4) {
      $msgs[] = ["Years longer than 4 digits must be prefixed with a 'Y'."];
    } elseif (length($parsed_date[self::YEAR_BASE]) < 4) {
      $msgs[] = ["Years must be at least 4 characters long."];
    }
    $strict_pattern = 'Y'

    // Month.
    if (!array_key_exists(self::MONTH, $parsed_date) && !empty($parsed_date[self::MONTH])) {
      // Valid month values?
      if (!array_key_exists($parsed_date[self::MONTH], self::MONTHS_MAP) &&
          strpos($parsed_date[self::MONTH], 'X') === FALSE ) {
        $msgs[] = ["Provided month value '$parsed_date[self::MONTH]' is not valid."];
      }
      $strict_pattern = 'Y-m';
    }

    // Day.
    if (!array_key_exists(self::DAY) && !empty($parsed_date[self::DAY])) {
      // Valid day values?
      if (strpos($parsed_date[self::DAY], 'X') === FALSE &&
          !in_array(intval($parsed_date[self::DAY]), range(1, 31))) {
        $msgs[] = ["Provided day value '$parsed_date[self::DAY]' is not valid."];
      }
      $strict_pattern = 'Y-m-d';
    }
    // Time.
    if (strpos($datetime_str, 'T') !== FALSE && empty($time)) {
      $msgs[] = "Time not provided with time seperator (T).";
    }

    if ($time) {
      if (!preg_match('/^-?(\d{4})(-\d{2}){2}T\d{2}(:\d{2}){2}(Z|(\+|-)\d{2}:\d{2})?$/', $datetime_str, $matches)) {
        $msgs[] = "The date/time '$datetime_str' is invalid.";
      }
      $strict_pattern = 'Y-m-d\TH:i:s';
      if (count($matches) > 4) {
        if ($matches[4] === 'Z') {
          $strict_pattern .= '\Z';
        }
        else {
          $strict_pattern .= 'P';
        }
      }
    }

    if ($strict) {
      // Assemble the parts again.
      if ($time) {
        $cleaned_datetime = $datetime_str;
      } else {
        $cleaned_datetime = implode('-', [
          $parsed_date[self::YEAR_BASE],
          $parsed_date[self::MONTH],
          $parsed_date[self::DAY],
        ]);
      }
      $datetime_obj = DateTime::createFromFormat('!' . $strict_pattern, $cleaned_datetime);
      $errors = DateTime::getLastErrors();
      if (!$datetime_obj ||
          !empty($errors['warning_count']) ||
          // DateTime will create valid dates from Y-m without warning,
          // so validate we still have what it was given.
          !($cleaned_datetime === $datetime_obj->format($strict_pattern))
        ) {
        $msgs[] = "Strictly speaking, the date (and/or time) '$datetime_str' is invalid.";
      }
    }

    return $msgs;
  }

  public static function expand_year($year_full, $year_base, $year_exponent){
    $year = '';
    // Apply negative to base.
    // Note that the minus sign can be before or after the 'Y'
    // in the full date field; thus, simply check not FALSE.
    if (strpos($year_full,'-') !== FALSE) {
      $year = '-';
    }
    // Expand exponents.
    $exponent = intval(substr($year_exponent, 1));
    $year .= strval((10 ** $exponent) * intval($year_base));
  }

}
