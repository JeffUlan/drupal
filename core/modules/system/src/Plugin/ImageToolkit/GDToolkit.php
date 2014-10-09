<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\ImageToolkit\GDToolkit.
 */

namespace Drupal\system\Plugin\ImageToolkit;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\ImageToolkit\ImageToolkitBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the GD2 toolkit for image manipulation within Drupal.
 *
 * @ImageToolkit(
 *   id = "gd",
 *   title = @Translation("GD2 image manipulation toolkit")
 * )
 */
class GDToolkit extends ImageToolkitBase {

  /**
   * A GD image resource.
   *
   * @var resource|null
   */
  protected $resource = NULL;

  /**
   * Image type represented by a PHP IMAGETYPE_* constant (e.g. IMAGETYPE_JPEG).
   *
   * @var int
   */
  protected $type;

  /**
   * Image information from a file, available prior to loading the GD resource.
   *
   * This contains a copy of the array returned by executing getimagesize()
   * on the image file when the image object is instantiated. It gets reset
   * to NULL as soon as the GD resource is loaded.
   *
   * @var array|null
   *
   * @see \Drupal\system\Plugin\ImageToolkit\GDToolkit::parseFile()
   * @see \Drupal\system\Plugin\ImageToolkit\GDToolkit::setResource()
   * @see http://php.net/manual/en/function.getimagesize.php
   */
  protected $preLoadInfo = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('image.toolkit.operation.manager'),
      $container->get('logger.channel.image'),
      $container->get('config.factory')
    );
  }

  /**
   * Sets the GD image resource.
   *
   * @param resource $resource
   *   The GD image resource.
   *
   * @return $this
   */
  public function setResource($resource) {
    $this->preLoadInfo = NULL;
    $this->resource = $resource;
    return $this;
  }

  /**
   * Retrieves the GD image resource.
   *
   * @return resource|null
   *   The GD image resource, or NULL if not available.
   */
  public function getResource() {
    if (!$this->resource) {
      $this->load();
    }
    return $this->resource;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['image_jpeg_quality'] = array(
      '#type' => 'number',
      '#title' => t('JPEG quality'),
      '#description' => t('Define the image quality for JPEG manipulations. Ranges from 0 to 100. Higher values mean better image quality but bigger files.'),
      '#min' => 0,
      '#max' => 100,
      '#default_value' => $this->configFactory->get('system.image.gd')->get('jpeg_quality'),
      '#field_suffix' => t('%'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->get('system.image.gd')
      ->set('jpeg_quality', $form_state->getValue(array('gd', 'image_jpeg_quality')))
      ->save();
  }

  /**
   * Loads a GD resource from a file.
   *
   * @return bool
   *   TRUE or FALSE, based on success.
   */
  protected function load() {
    // Return immediately if the image file is not valid.
    if (!$this->isValid()) {
      return FALSE;
    }

    $function = 'imagecreatefrom' . image_type_to_extension($this->getType(), FALSE);
    if (function_exists($function) && $resource = $function($this->getImage()->getSource())) {
      $this->setResource($resource);
      if (imageistruecolor($resource)) {
        return TRUE;
      }
      else {
        // Convert indexed images to true color, so that filters work
        // correctly and don't result in unnecessary dither.
        $new_image = $this->createTmp($this->getType(), imagesx($resource), imagesy($resource));
        if ($ret = (bool) $new_image) {
          imagecopy($new_image, $resource, 0, 0, 0, 0, imagesx($resource), imagesy($resource));
          imagedestroy($resource);
          $this->setResource($new_image);
        }
        return $ret;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isValid() {
    return ((bool) $this->preLoadInfo || (bool) $this->resource);
  }

  /**
   * {@inheritdoc}
   */
  public function save($destination) {
    $scheme = file_uri_scheme($destination);
    // Work around lack of stream wrapper support in imagejpeg() and imagepng().
    if ($scheme && file_stream_wrapper_valid_scheme($scheme)) {
      // If destination is not local, save image to temporary local file.
      $local_wrappers = file_get_stream_wrappers(STREAM_WRAPPERS_LOCAL);
      if (!isset($local_wrappers[$scheme])) {
        $permanent_destination = $destination;
        $destination = drupal_tempnam('temporary://', 'gd_');
      }
      // Convert stream wrapper URI to normal path.
      $destination = drupal_realpath($destination);
    }

    $function = 'image' . image_type_to_extension($this->getType(), FALSE);
    if (!function_exists($function)) {
      return FALSE;
    }
    if ($this->getType() == IMAGETYPE_JPEG) {
      $success = $function($this->getResource(), $destination, $this->configFactory->get('system.image.gd')->get('jpeg_quality'));
    }
    else {
      // Always save PNG images with full transparency.
      if ($this->getType() == IMAGETYPE_PNG) {
        imagealphablending($this->getResource(), FALSE);
        imagesavealpha($this->getResource(), TRUE);
      }
      $success = $function($this->getResource(), $destination);
    }
    // Move temporary local file to remote destination.
    if (isset($permanent_destination) && $success) {
      return (bool) file_unmanaged_move($destination, $permanent_destination, FILE_EXISTS_REPLACE);
    }
    return $success;
  }

  /**
   * {@inheritdoc}
   */
  public function parseFile() {
    $data = @getimagesize($this->getImage()->getSource());
    if ($data && in_array($data[2], static::supportedTypes())) {
      $this->setType($data[2]);
      $this->preLoadInfo = $data;
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Creates a truecolor image preserving transparency from a provided image.
   *
   * @param int $type
   *   An image type represented by a PHP IMAGETYPE_* constant (e.g.
   *   IMAGETYPE_JPEG, IMAGETYPE_PNG, etc.).
   * @param int $width
   *   The new width of the new image, in pixels.
   * @param int $height
   *   The new height of the new image, in pixels.
   *
   * @return resource
   *   A GD image handle.
   */
  public function createTmp($type, $width, $height) {
    $res = imagecreatetruecolor($width, $height);

    if ($type == IMAGETYPE_GIF) {
      // Find out if a transparent color is set, will return -1 if no
      // transparent color has been defined in the image.
      $transparent = imagecolortransparent($this->getResource());
      if ($transparent >= 0) {
        // Find out the number of colors in the image palette. It will be 0 for
        // truecolor images.
        $palette_size = imagecolorstotal($this->getResource());
        if ($palette_size == 0 || $transparent < $palette_size) {
          // Set the transparent color in the new resource, either if it is a
          // truecolor image or if the transparent color is part of the palette.
          // Since the index of the transparency color is a property of the
          // image rather than of the palette, it is possible that an image
          // could be created with this index set outside the palette size (see
          // http://stackoverflow.com/a/3898007).
          $transparent_color = imagecolorsforindex($this->getResource(), $transparent);
          $transparent = imagecolorallocate($res, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);

          // Flood with our new transparent color.
          imagefill($res, 0, 0, $transparent);
          imagecolortransparent($res, $transparent);
        }
      }
    }
    elseif ($type == IMAGETYPE_PNG) {
      imagealphablending($res, FALSE);
      $transparency = imagecolorallocatealpha($res, 0, 0, 0, 127);
      imagefill($res, 0, 0, $transparency);
      imagealphablending($res, TRUE);
      imagesavealpha($res, TRUE);
    }
    else {
      imagefill($res, 0, 0, imagecolorallocate($res, 255, 255, 255));
    }

    return $res;
  }

  /**
   * {@inheritdoc}
   */
  public function getWidth() {
    if ($this->preLoadInfo) {
      return $this->preLoadInfo[0];
    }
    elseif ($res = $this->getResource()) {
      return imagesx($res);
    }
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getHeight() {
    if ($this->preLoadInfo) {
      return $this->preLoadInfo[1];
    }
    elseif ($res = $this->getResource()) {
      return imagesy($res);
    }
    else {
      return NULL;
    }
  }

  /**
   * Gets the PHP type of the image.
   *
   * @return int
   *   The image type represented by a PHP IMAGETYPE_* constant (e.g.
   *   IMAGETYPE_JPEG).
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Sets the PHP type of the image.
   *
   * @param int $type
   *   The image type represented by a PHP IMAGETYPE_* constant (e.g.
   *   IMAGETYPE_JPEG).
   *
   * @return $this
   */
  public function setType($type) {
    if (in_array($type, static::supportedTypes())) {
      $this->type = $type;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMimeType() {
    return $this->getType() ? image_type_to_mime_type($this->getType()) : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequirements() {
    $requirements = array();

    $info = gd_info();
    $requirements['version'] = array(
      'title' => t('GD library'),
      'value' => $info['GD Version'],
    );

    // Check for filter and rotate support.
    if (!function_exists('imagefilter') || !function_exists('imagerotate')) {
      $requirements['version']['severity'] = REQUIREMENT_WARNING;
      $requirements['version']['description'] = t('The GD Library for PHP is enabled, but was compiled without support for functions used by the rotate and desaturate effects. It was probably compiled using the official GD libraries from http://www.libgd.org instead of the GD library bundled with PHP. You should recompile PHP --with-gd using the bundled GD library. See <a href="@url">the PHP manual</a>.', array('@url' => 'http://www.php.net/manual/book.image.php'));
    }

    return $requirements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isAvailable() {
    // GD2 support is available.
    return function_exists('imagegd2');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSupportedExtensions() {
    $extensions = array();
    foreach (static::supportedTypes() as $image_type) {
      $extensions[] = Unicode::strtolower(image_type_to_extension($image_type, FALSE));
    }
    return $extensions;
  }

  /**
   * Returns the IMAGETYPE_xxx constant for the given extension.
   *
   * This is the reverse of the image_type_to_extension() function.
   *
   * @param string $extension
   *   The extension to get the IMAGETYPE_xxx constant for.
   *
   * @return int
   *   The IMAGETYPE_xxx constant for the given extension, or IMAGETYPE_UNKNOWN
   *   for unsupported extensions.
   *
   * @see image_type_to_extension()
   */
  public function extensionToImageType($extension) {
    foreach ($this->supportedTypes() as $type) {
      if (image_type_to_extension($type, FALSE) === $extension) {
        return $type;
      }
    }
    return IMAGETYPE_UNKNOWN;
  }

  /**
   * Returns a list of image types supported by the toolkit.
   *
   * @return array
   *   An array of available image types. An image type is represented by a PHP
   *   IMAGETYPE_* constant (e.g. IMAGETYPE_JPEG, IMAGETYPE_PNG, etc.).
   */
  protected static function supportedTypes() {
    return array(IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF);
  }
}
