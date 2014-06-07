<?php

/**
 * @file
 * Definition of Drupal\Core\Annotation\Translation.
 */

namespace Drupal\Core\Annotation;

use Drupal\Component\Annotation\AnnotationBase;
use Drupal\Core\StringTranslation\TranslationWrapper;

/**
 * @defgroup plugin_translatable Translatable plugin metadata
 *
 * @{
 * When providing plugin annotation, properties whose values are displayed in
 * the user interface should be made translatable. Much the same as how user
 * interface text elsewhere is wrapped in t() to make it translatable, in plugin
 * annotation, wrap translatable strings in the @ Translation() annotation.
 * For example:
 * @code
 *   title = @ Translation("Title of the plugin"),
 * @endcode
 * Remove spaces after @ in your actual plugin - these are put into this sample
 * code so that it is not recognized as annotation.
 *
 * To provide replacement values for placeholders, use the "arguments" array:
 * @code
 *   title = @ Translation("Bundle !title", arguments = {"!title" = "Foo"}),
 * @endcode
 *
 * It is also possible to provide a context with the text, similar to t():
 * @code
 *   title = @ Translation("Bundle", context = "Validation"),
 * @endcode
 * Other t() arguments like language code are not valid to pass in. Only
 * context is supported.
 * @}
 */

/**
 * Defines a translatable annotation object.
 *
 * Some metadata within an annotation needs to be translatable. This class
 * supports that need by allowing both the translatable string and, if
 * specified, a context for that string. The string (with optional context)
 * is passed into t().
 *
 * @Annotation
 *
 * @ingroup plugin_translatable
 */
class Translation extends AnnotationBase {

  /**
   * The string translation object.
   *
   * @var \Drupal\Core\StringTranslation\TranslationWrapper
   */
  protected $translation;

  /**
   * Constructs a new class instance.
   *
   * Parses values passed into this class through the t() function in Drupal and
   * handles an optional context for the string.
   *
   * @param array $values
   *   Possible array keys:
   *   - value (required): the string that is to be translated.
   *   - arguments (optional): an array with placeholder replacements, keyed by
   *     placeholder.
   *   - context (optional): a string that describes the context of "value";
   */
  public function __construct(array $values) {
    $string = $values['value'];
    $arguments = isset($values['arguments']) ? $values['arguments'] : array();
    $options = array();
    if (!empty($values['context'])) {
      $options = array(
        'context' => $values['context'],
      );
    }
    $this->translation = new TranslationWrapper($string, $arguments, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function get() {
    return $this->translation;
  }

}
