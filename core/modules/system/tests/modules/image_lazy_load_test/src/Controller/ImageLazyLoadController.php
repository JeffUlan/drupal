<?php

namespace Drupal\image_lazy_load_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * The ImageLazyLoadController class.
 */
class ImageLazyLoadController extends ControllerBase {

  /**
   * Render an image using image theme.
   *
   * @return array
   *   The render array.
   */
  public function renderImage() {
    $images['with-dimensions'] = [
      '#theme' => 'image',
      '#uri' => '/core/themes/bartik/logo.svg',
      '#alt' => 'Image lazy load testing image',
      '#prefix' => '<div id="with-dimensions">',
      '#suffix' => '</div>',
      '#width' => '50%',
      '#height' => '50%',
    ];

    $images['without-dimensions'] = [
      '#theme' => 'image',
      '#uri' => '/core/themes/bartik/logo.svg',
      '#alt' => 'Image lazy load testing image without dimensions',
      '#prefix' => '<div id="without-dimensions">',
      '#suffix' => '</div>',
    ];

    return $images;
  }

}
