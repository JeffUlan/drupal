<?php
/**
* @file
* Definition of Drupal\Core\Plugin\Exception\InvalidDecoratedMethod.
*/

namespace Drupal\Component\Plugin\Exception;

use Drupal\Component\Plugin\Exception\ExceptionInterface;
use \BadMethodCallException;

/**
 * Exception thrown when a decorator's _call() method is triggered, but the
 * decorated object does not contain the requested method.
 *
 */
class InvalidDecoratedMethod extends BadMethodCallException implements ExceptionInterface { }
