<?php

namespace Drupal\Tests\Core\Field;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;
use Drupal\Tests\UnitTestCase;

/**
 * Defines a test for the StringItem field-type.
 *
 * @group Field
 * @coversDefaultClass \Drupal\Core\Field\Plugin\Field\FieldType\StringItem
 */
class StringItemTest extends UnitTestCase {

  /**
   * Tests generating sample values.
   *
   * @param int $max_length
   *   Maximum field length.
   *
   * @covers ::generateSampleValue
   * @dataProvider providerMaxLength
   */
  public function testGenerateSampleValue(int $max_length): void {
    $definition = $this->prophesize(FieldDefinitionInterface::class);
    $definition->getSetting('max_length')->willReturn($max_length);
    $sample_value = StringItem::generateSampleValue($definition->reveal());
    $this->assertLessThanOrEqual($max_length, mb_strlen($sample_value['value']));
  }

  /**
   * Data provider for maximum-lengths.
   *
   * @return array
   *   Test cases.
   */
  public function providerMaxLength(): array {
    return [
      '32' => [32],
      '255' => [255],
      '500' => [500],
      '15' => [15],
      '4' => [4],
      '64' => [64],
    ];
  }

}
