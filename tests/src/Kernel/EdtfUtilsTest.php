<?php

namespace Drupal\Tests\controlled_access_terms\Kernel;

use Drupal\controlled_access_terms\EDTFUtils;
use Drupal\KernelTests\KernelTestBase;

/**
 * Class EdtfUtilsTest.
 *
 * @package Drupal\Tests\controlled_access_terms\Kernel
 * @group controlled_access_terms
 * @coversDefaultClass \Drupal\controlled_access_terms\EDTFUtils
 */
class EdtfUtilsTest extends KernelTestBase {

  /**
   * Array of test inputs and expected outputs. Empty array means valid input.
   *
   * @var array
   */
  private $testCases = [
    '1900' => [],
    '1900-01' => [],
    '1900-01-02' => [],
    '190X' => [],
    '1900-XX' => [],
    '1900-91' => ['Provided month value \'91\' is not valid.'],
    '1900-91-01' => ['Provided month value \'91\' is not valid.'],
    // No validation for months with X.
    '1900-3X' => [],
    // Month 31 without a day matches summer so it's valid.
    '1900-31' => [],
    '1900-31-01' => ['Provided month value \'31\' is not valid.'],
    '190X-5X-8X' => [],
    '19000' => ['Years longer than 4 digits must be prefixed with a \'Y\'.'],
    'Y19000' => [],
    '190u' => ['Could not parse the date \'190u\'.'],
    '190' => ['Years must be at least 4 characters long.'],
    '190-99-52' => ['Years must be at least 4 characters long.',
      'Provided month value \'99\' is not valid.',
      'Provided day value \'52\' is not valid.',
    ],
    '1900-01-02T' => ['Time not provided with time seperator (T).'],
    '1900-01-02T1:1:1' => ['The date/time \'1900-01-02T1:1:1\' is invalid.'],
    '1900-01-02T01:22:33' => [],
    '1900-01-02T01:22:33Z' => [],
    '1900-01-02T01:22:33+' => ['The date/time \'1900-01-02T01:22:33+\' is invalid.'],
    '1900-01-02T01:22:33+05:00' => [],
  ];

  /**
   * @covers ::validate
   */
  public function testEdtfValidate() {
    foreach ($this->testCases as $input => $expected) {
      $this->assertEquals($expected, EDTFUtils::validate($input, FALSE, FALSE, FALSE));
    }
  }

}
