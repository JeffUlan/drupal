<?php

/**
 * @file
 * Default theme implementation to wrap aggregator content.
 *
 * Available variables:
 * - $content: All aggregator content.
 * - $page: Pager links rendered through theme_pager().
 *
 * @see template_preprocess()
 * @see template_preprocess_aggregator_wrapper()
 */
?>
<div class="<?php print $classes; ?>">
  <?php print $content; ?>
  <?php print $pager; ?>
</div>
