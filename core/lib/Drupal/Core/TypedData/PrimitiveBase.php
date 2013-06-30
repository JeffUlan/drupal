<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\PrimitiveBase.
 */

namespace Drupal\Core\TypedData;

/**
 * Base class for primitive data types.
 */
abstract class PrimitiveBase extends TypedData implements PrimitiveInterface {

  /**
   * The data value.
   *
   * @var mixed
   */
  protected $value;

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    // Notify the parent of any changes to be made.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
    $this->value = $value;
  }
}
