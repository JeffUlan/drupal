<?php

/**
 * @file
 * Definition of Drupal\Core\Template\TwigEnvironment.
 */

namespace Drupal\Core\Template;

/**
 * A class that defines a Twig environment for Drupal.
 *
 * Instances of this class are used to store the configuration and extensions,
 * and are used to load templates from the file system or other locations.
 *
 * @see core\vendor\twig\twig\lib\Twig\Enviornment.php
 */
class TwigEnvironment extends \Twig_Environment {
  protected $cache_object = NULL;
  protected $storage = NULL;

  /**
   * Constructs a TwigEnvironment object and stores cache and storage
   * internally.
   */
  public function __construct(\Twig_LoaderInterface $loader = NULL, $options = array()) {
    // @todo Pass as arguments from the DIC?
    $this->cache_object = cache();
    $this->storage = drupal_php_storage('twig');

    parent::__construct($loader, $options);
  }

  /**
   * Checks if the compiled template needs an update.
   */
  public function needsUpdate($cache_filename, $name) {
     $cid = 'twig:' . $cache_filename;
     $obj = $this->cache_object->get($cid);
     $mtime = isset($obj->data) ? $obj->data : FALSE;
     return $mtime !== FALSE && !$this->isTemplateFresh($name, $mtime);
  }

  /**
   * Compile the source and write the compiled template to disk.
   */
  public function updateCompiledTemplate($cache_filename, $name) {
    $source = $this->loader->getSource($name);
    $compiled_source = $this->compileSource($source, $name);
    $this->storage->save($cache_filename, $compiled_source);
    // Save the last modification time
    $cid = 'twig:' . $cache_filename;
    $this->cache_object->set($cid, REQUEST_TIME);
  }

  /**
   * Implements Twig_Environment::loadTemplate().
   *
   * We need to overwrite this function to integrate with drupal_php_storage().
   *
   * This is a straight copy from loadTemplate() changed to use
   * drupal_php_storage().
   */
  public function loadTemplate($name, $index = NULL) {
    $cls = $this->getTemplateClass($name, $index);

    if (isset($this->loadedTemplates[$cls])) {
      return $this->loadedTemplates[$cls];
    }

    if (!class_exists($cls, FALSE)) {
      $cache_filename = $this->getCacheFilename($name);

      if ($cache_filename === FALSE) {
        $source = $this->loader->getSource($name);
        $compiled_source = $this->compileSource($source, $name);
        eval('?' . '>' . $compiled_source);
      } else {

        // If autoreload is on, check that the template has not been
        // modified since the last compilation.
        if ($this->isAutoReload() && $this->needsUpdate($cache_filename, $name)) {
          $this->updateCompiledTemplate($cache_filename, $name);
        }

        if (!$this->storage->load($cache_filename)) {
          $this->updateCompiledTemplate($cache_filename, $name);
          $this->storage->load($cache_filename);
        }
      }
    }

    if (!$this->runtimeInitialized) {
        $this->initRuntime();
    }

    return $this->loadedTemplates[$cls] = new $cls($this);
  }

}
