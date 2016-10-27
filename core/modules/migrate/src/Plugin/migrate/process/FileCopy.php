<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StreamWrapper\LocalStream;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Copy a file from one place into another.
 *
 * @MigrateProcessPlugin(
 *   id = "file_copy"
 * )
 */
class FileCopy extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The stream wrapper manager service.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * An instance of the download process plugin.
   *
   * @var \Drupal\migrate\Plugin\MigrateProcessInterface
   */
  protected $downloadPlugin;

  /**
   * Constructs a file_copy process plugin.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrappers
   *   The stream wrapper manager service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\migrate\Plugin\MigrateProcessInterface $download_plugin
   *   An instance of the download plugin for handling remote URIs.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, StreamWrapperManagerInterface $stream_wrappers, FileSystemInterface $file_system, MigrateProcessInterface $download_plugin) {
    $configuration += array(
      'move' => FALSE,
      'rename' => FALSE,
      'reuse' => FALSE,
    );
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->streamWrapperManager = $stream_wrappers;
    $this->fileSystem = $file_system;
    $this->downloadPlugin = $download_plugin;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('stream_wrapper_manager'),
      $container->get('file_system'),
      $container->get('plugin.manager.migrate.process')->createInstance('download')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // If we're stubbing a file entity, return a URI of NULL so it will get
    // stubbed by the general process.
    if ($row->isStub()) {
      return NULL;
    }
    list($source, $destination) = $value;

    // If the source path or URI represents a remote resource, delegate to the
    // download plugin.
    if (!$this->isLocalUri($source)) {
      return $this->downloadPlugin->transform($value, $migrate_executable, $row, $destination_property);
    }

    // Ensure the source file exists, if it's a local URI or path.
    if (!file_exists($source)) {
      throw new MigrateException("File '$source' does not exist");
    }

    // If the start and end file is exactly the same, there is nothing to do.
    if ($this->isLocationUnchanged($source, $destination)) {
      return $destination;
    }

    $replace = $this->getOverwriteMode();
    // We attempt the copy/move first to avoid calling file_prepare_directory()
    // any more than absolutely necessary.
    $final_destination = $this->writeFile($source, $destination, $replace);
    if ($final_destination) {
      return $final_destination;
    }
    // If writeFile didn't work, make sure there's a writable directory in
    // place.
    $dir = $this->getDirectory($destination);
    if (!file_prepare_directory($dir, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
      throw new MigrateException("Could not create or write to directory '$dir'");
    }
    $final_destination = $this->writeFile($source, $destination, $replace);
    if ($final_destination) {
      return $final_destination;
    }
    throw new MigrateException("File $source could not be copied to $destination");
  }

  /**
   * Tries to move or copy a file.
   *
   * @param string $source
   *   The source path or URI.
   * @param string $destination
   *   The destination path or URI.
   * @param int $replace
   *   (optional) FILE_EXISTS_REPLACE (default) or FILE_EXISTS_RENAME.
   *
   * @return string|bool
   *   File destination on success, FALSE on failure.
   */
  protected function writeFile($source, $destination, $replace = FILE_EXISTS_REPLACE) {
    // Check if there is a destination available for copying. If there isn't,
    // it already exists at the destination and the replace flag tells us to not
    // replace it. In that case, return the original destination.
    if (!($final_destination = file_destination($destination, $replace))) {
      return $destination;
    }
    $function = 'file_unmanaged_' . ($this->configuration['move'] ? 'move' : 'copy');
    return $function($source, $destination, $replace);
  }

  /**
   * Determines how to handle file conflicts.
   *
   * @return int
   *   FILE_EXISTS_REPLACE (default), FILE_EXISTS_RENAME, or FILE_EXISTS_ERROR
   *   depending on the current configuration.
   */
  protected function getOverwriteMode() {
    if (!empty($this->configuration['rename'])) {
      return FILE_EXISTS_RENAME;
    }
    if (!empty($this->configuration['reuse'])) {
      return FILE_EXISTS_ERROR;
    }

    return FILE_EXISTS_REPLACE;
  }

  /**
   * Returns the directory component of a URI or path.
   *
   * For URIs like public://foo.txt, the full physical path of public://
   * will be returned, since a scheme by itself will trip up certain file
   * API functions (such as file_prepare_directory()).
   *
   * @param string $uri
   *   The URI or path.
   *
   * @return string|false
   *   The directory component of the path or URI, or FALSE if it could not
   *   be determined.
   */
  protected function getDirectory($uri) {
    $dir = $this->fileSystem->dirname($uri);
    if (substr($dir, -3) == '://') {
      return $this->fileSystem->realpath($dir);
    }
    return $dir;
  }

  /**
   * Determines if the source and destination URIs represent identical paths.
   *
   * @param string $source
   *   The source URI.
   * @param string $destination
   *   The destination URI.
   *
   * @return bool
   *   TRUE if the source and destination URIs refer to the same physical path,
   *   otherwise FALSE.
   */
  protected function isLocationUnchanged($source, $destination) {
    return $this->fileSystem->realpath($source) === $this->fileSystem->realpath($destination);
  }

  /**
   * Determines if the given URI or path is considered local.
   *
   * A URI or path is considered local if it either has no scheme component,
   * or the scheme is implemented by a stream wrapper which extends
   * \Drupal\Core\StreamWrapper\LocalStream.
   *
   * @param string $uri
   *   The URI or path to test.
   *
   * @return bool
   */
  protected function isLocalUri($uri) {
    $scheme = $this->fileSystem->uriScheme($uri);

    // The vfs scheme is vfsStream, which is used in testing. vfsStream is a
    // simulated file system that exists only in memory, but should be treated
    // as a local resource.
    if ($scheme == 'vfs') {
      $scheme = FALSE;
    }
    return $scheme === FALSE || $this->streamWrapperManager->getViaScheme($scheme) instanceof LocalStream;
  }

}
