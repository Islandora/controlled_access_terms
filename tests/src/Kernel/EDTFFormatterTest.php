<?php

namespace Drupal\Tests\controlled_access_terms\Kernel\Plugin\Field\FieldFormatter;

use Drupal\KernelTests\KernelTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\node\Entity\Node;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Tests the EDTFFormatter field formatter.
 *
 * @group controlled_access_terms
 */
class EDTFFormatterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'node',
    'field',
    'text',
    'user',
    'controlled_access_terms',
  ];

  /**
   * The node type for testing.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $nodeType;

  /**
   * The field storage configuration for the test field.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The field configuration for the test field.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * The formatter being tested.
   *
   * @var \Drupal\controlled_access_terms\Plugin\Field\FieldFormatter\EDTFFormatter
   */
  protected $formatter;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Install necessary schemas.
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('field_storage_config');
    $this->installEntitySchema('field_config');
    $this->installSchema('node', ['node_access']);

    // Create a content type.
    $this->nodeType = NodeType::create([
      'type' => 'test_content',
      'name' => 'Test Content',
    ]);
    $this->nodeType->save();

    // Create EDTF field storage.
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => 'field_edtf',
      'entity_type' => 'node',
      'type' => 'edtf',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ]);
    $this->fieldStorage->save();

    // Attach field to content type.
    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => $this->nodeType->id(),
      'label' => 'EDTF Field',
    ]);
    $this->field->save();

    // Configure display.
    \Drupal::service('entity_display.repository')->getViewDisplay('node', $this->nodeType->id(), 'default')
      ->setComponent('field_edtf', [
        'type' => 'edtf_default',
      ])
      ->save();
  }

  /**
   * Test formatter output returns a render array with various EDTF dates.
   */
  public function testFormatterOutput() {
    $node = Node::create([
      'type' => $this->nodeType->id(),
      'title' => 'Test EDTF Node',
      'field_edtf' => [
    // Single date.
        ['value' => '2023-10-15'],
    // Year interval.
        ['value' => '2023/2024'],
    // Date interval.
        ['value' => '2022-06-01/2023-12-31'],
      ],
    ]);
    $node->save();

    $build = $node->get('field_edtf')->view();
    $this->assertNotEmpty($build, 'Render array is not empty');
  }

  /**
   * Test EDTFFormatter date formatting method.
   *
   * @dataProvider edtfDateFormatProvider
   */
  public function testDateFormatting(string $input, string $expected) : void {
    // Create the formatter.
    $formatter = $this->container->get('plugin.manager.field.formatter')
      ->createInstance('edtf_default', [
        'field_definition' => $this->field,
        'settings' => [],
        'label' => '',
        'view_mode' => 'default',
        'third_party_settings' => [],
      ]);

    // Use reflection to access the protected formatDate method.
    $reflectionMethod = new \ReflectionMethod($formatter, 'formatDate');
    $reflectionMethod->setAccessible(TRUE);

    // Perform the formatting.
    $result = $reflectionMethod->invoke($formatter, $input);

    // Assert the result.
    $this->assertEquals($expected, $result,
      "Failed formatting for input: $input. Expected: $expected, Got: $result");
  }

  /**
   * Data provider for EDTF date formatting tests.
   *
   * @return array
   *   Array of test inputs.
   */
  public function edtfDateFormatProvider(): array {
    return [
      // EDTF Level 0.
      'Date - complete' => [
        'input' => '1985-04-12',
        'expected' => '1985-04-12',
      ],
      'Date - reduced precision - year and month' => [
        'input' => '1985-04',
        'expected' => '1985-04',
      ],
      'Date - reduced precision - year' => [
        'input' => '1985',
        'expected' => '1985',
      ],
      'Date and time - local' => [
        'original' => '2024-10-15T12:00:00',
        'formatted' => '2024-10-15 12:00:00',
      ],
      'Date and time - Z' => [
        'original' => '1985-04-12T23:20:30Z',
        'formatted' => '1985-04-12 23:20:30Z',
      ],
      'Date and time - shift in hours' => [
        'original' => '1985-04-12T23:20:30+04',
        'formatted' => '1985-04-12 23:20:30+04',
      ],
      'Date and time - shift in hours and minutes' => [
        'original' => '1985-04-12T23:20:30+04:30',
        'formatted' => '1985-04-12 23:20:30+04:30',
      ],

      // EDTF level 1.
      'Uncertain year' => [
        'input' => '1984?',
        'expected' => '1984 (year uncertain)',
      ],
      'Uncertain year and month' => [
        'original' => '2024-10%',
        'formatted' => '2024-10 (year and month uncertain; year and month approximate)',
      ],
      'Approximate year' => [
        'input' => '1984~',
        'expected' => '1984 (year approximate)',
      ],
      'Approximate year and month' => [
        'original' => '2024-10~',
        'formatted' => '2024-10 (year and month approximate)',
      ],
      'Year and month' => [
        'original' => '2024-10',
        'formatted' => '2024-10',
      ],
      'Unspecified digits from right' => [
        'input' => '198X',
        'expected' => 'Unknown year in the decade of the 1980s',
      ],

      // Invalid/edge cases.
      'Empty input' => [
        'input' => '',
        'expected' => '',
      ],
      'Invalid date format' => [
        'input' => 'invalid-date',
        'expected' => '',
      ],
      'Invalid (date-like)' => [
        'original' => '1900s',
        'formatted' => '',
      ],
    ];
  }

}
