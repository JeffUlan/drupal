<?php

/**
 * @file
 * Contains \Drupal\image_test\Plugin\ImageToolkit\TestToolkit.
 */

namespace Drupal\image_test\Plugin\ImageToolkit;

use Drupal\Core\Image\ImageInterface;
use Drupal\Core\ImageToolkit\ImageToolkitBase;

/**
 * Defines a Test toolkit for image manipulation within Drupal.
 *
 * @ImageToolkit(
 *   id = "test",
 *   title = @Translation("A dummy toolkit that works")
 * )
 */
class TestToolkit extends ImageToolkitBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {
    $this->logCall('settings', array());
    $form['test_parameter'] = array(
      '#type' => 'number',
      '#title' => $this->t('Test toolkit parameter'),
      '#description' => $this->t('A toolkit parameter for testing purposes.'),
      '#min' => 0,
      '#max' => 100,
      '#default_value' => \Drupal::config('system.image.test_toolkit')->get('test_parameter'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormSubmit($form, &$form_state) {
    \Drupal::config('system.image.test_toolkit')
      ->set('test_parameter', $form_state['values']['test']['test_parameter'])
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo(ImageInterface $image) {
    $this->logCall('get_info', array($image));

    $details = FALSE;
    $data = getimagesize($image->getSource());

    if (isset($data) && is_array($data)) {
      $details = array(
        'width'     => $data[0],
        'height'    => $data[1],
        'type'      => $data[2],
        'mime_type' => $data['mime'],
      );
    }

    return $details;
  }

  /**
   * {@inheritdoc}
   */
  public function load(ImageInterface $image) {
    $this->logCall('load', array($image));
    return $image;
  }

  /**
   * {@inheritdoc}
   */
  public function save(ImageInterface $image, $destination) {
    $this->logCall('save', array($image, $destination));
    // Return false so that image_save() doesn't try to chmod the destination
    // file that we didn't bother to create.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function crop(ImageInterface $image, $x, $y, $width, $height) {
    $this->logCall('crop', array($image, $x, $y, $width, $height));
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function resize(ImageInterface $image, $width, $height) {
    $this->logCall('resize', array($image, $width, $height));
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function rotate(ImageInterface $image, $degrees, $background = NULL) {
    $this->logCall('rotate', array($image, $degrees, $background));
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function desaturate(ImageInterface $image) {
    $this->logCall('desaturate', array($image));
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function scale(ImageInterface $image, $width = NULL, $height = NULL, $upscale = FALSE) {
    $this->logCall('scale', array($image, $width, $height, $upscale));
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function scaleAndCrop(ImageInterface $image, $width, $height) {
    $this->logCall('scaleAndCrop', array($image, $width, $height));
    return TRUE;
  }

  /**
   * Stores the values passed to a toolkit call.
   *
   * @param string $op
   *   One of the image toolkit operations: 'get_info', 'load', 'save',
   *   'settings', 'resize', 'rotate', 'crop', 'desaturate'.
   * @param array $args
   *   Values passed to hook.
   *
   * @see \Drupal\system\Tests\Image\ToolkitTestBase::imageTestReset()
   * @see \Drupal\system\Tests\Image\ToolkitTestBase::imageTestGetAllCalls()
   */
  protected function logCall($op, $args) {
    $results = \Drupal::state()->get('image_test.results') ?: array();
    $results[$op][] = $args;
    \Drupal::state()->set('image_test.results', $results);
  }

  /**
   * {@inheritdoc}
   */
  public static function isAvailable() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function supportedTypes() {
    return array(IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF);
  }

}
