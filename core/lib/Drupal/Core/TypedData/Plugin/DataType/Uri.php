<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Plugin\DataType\Uri.
 */

namespace Drupal\Core\TypedData\Plugin\DataType;

use Drupal\Core\TypedData\Annotation\DataType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\TypedData\TypedData;

/**
 * The URI data type.
 *
 * The plain value of a URI is an absolute URI represented as PHP string.
 *
 * @DataType(
 *   id = "uri",
 *   label = @Translation("URI"),
 *   primitive_type = 7
 * )
 */
class Uri extends TypedData {

  /**
   * The data value.
   *
   * @var string
   */
  protected $value;
}
