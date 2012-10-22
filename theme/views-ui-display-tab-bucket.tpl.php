<?php

/**
 * @file
 * Template for each "box" on the display query edit screen.
 */
?>
<div <?php print $attributes; ?>>
  <?php if (!empty($actions)) : ?>
    <?php print $actions; ?>
  <?php endif; ?>
  <?php if (!empty($title)) : ?>
    <h3><?php print $title; ?></h3>
  <?php endif; ?>
  <?php print $content; ?>
</div>
