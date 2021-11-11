<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * The fundamental compatibility constraint.
 *
 * @Constraint(
 *   id = "CKEditor5FundamentalCompatibility",
 *   label = @Translation("CKEditor 5 fundamental text format compatibility", context = "Validation"),
 * )
 *
 * @internal
 */
class FundamentalCompatibilityConstraint extends Constraint {

  /**
   * The violation message when no markup filters are enabled.
   *
   * @var string
   */
  public $noMarkupFiltersMessage = 'CKEditor 5 only works with HTML-based text formats. The "%filter_label" (%filter_plugin_id) filter implies this text format is not HTML anymore.';

  /**
   * The violation message when fundamental HTML elements are forbidden.
   *
   * @var string
   */
  public $forbiddenElementsMessage = 'CKEditor 5 needs at least the &lt;p&gt; and &lt;br&gt; tags to be allowed to be able to function. They are forbidden by the "%filter_label" (%filter_plugin_id) filter.';

  /**
   * The violation message when fundamental HTML elements are not allowed.
   *
   * @var string
   */
  public $nonAllowedElementsMessage = 'CKEditor 5 needs at least the &lt;p&gt; and &lt;br&gt; tags to be allowed to be able to function. They are not allowed by the "%filter_label" (%filter_plugin_id) filter.';

  /**
   * The violation message when HTML elements cannot be generated by CKE5.
   *
   * @var string
   */
  public $notSupportedElementsMessage = 'The current CKEditor 5 build requires the following elements and attributes: <br><code>@list</code><br>The following elements are not supported: <br><code>@diff</code>';

  /**
   * The violation message when CKE5 can generate disallowed HTML elements.
   *
   * @var string
   */
  public $missingElementsMessage = 'The current CKEditor 5 build requires the following elements and attributes: <br><code>@list</code><br>The following elements are missing: <br><code>@diff</code>';

}
