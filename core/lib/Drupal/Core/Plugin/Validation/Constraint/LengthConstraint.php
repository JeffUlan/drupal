<?php

/**
 * @file
 * Contains \Drupal\Core\Plugin\Validation\Constraint\LengthConstraint.
 */

namespace Drupal\Core\Plugin\Validation\Constraint;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Symfony\Component\Validator\Constraints\Length;

/**
 * Length constraint.
 *
 * Overrides the symfony constraint to use Drupal-style replacement patterns.
 *
 * @todo: Move this below the TypedData core component.
 *
 * @Plugin(
 *   id = "Length",
 *   label = @Translation("Length", context = "Validation"),
 *   type = { "string" }
 * )
 */
class LengthConstraint extends Length {

  public $maxMessage = 'This value is too long. It should have %limit character or less.|This value is too long. It should have %limit characters or less.';
  public $minMessage = 'This value is too short. It should have %limit character or more.|This value is too short. It should have %limit characters or more.';
  public $exactMessage = 'This value should have exactly %limit character.|This value should have exactly %limit characters.';

  /**
   * Overrides Range::validatedBy().
   */
  public function validatedBy() {
    return '\Symfony\Component\Validator\Constraints\LengthValidator';
  }
}
