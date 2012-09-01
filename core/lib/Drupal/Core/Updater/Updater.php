<?php

/**
 * @file
 * Definition of Drupal\Core\Updater\Updater.
 */

namespace Drupal\Core\Updater;

use Drupal\Core\FileTransfer\FileTransferException;

/**
 * Defines the base class for Updaters used in Drupal.
 */
class Updater {

  /**
   * Directory to install from.
   *
   * @var string
   */
  public $source;

  /**
   * Constructs a new updater.
   *
   * @param string $source
   *   Directory to install from.
   */
  public function __construct($source) {
    $this->source = $source;
    $this->name = self::getProjectName($source);
    $this->title = self::getProjectTitle($source);
  }

  /**
   * Returns an Updater of the appropriate type depending on the source.
   *
   * If a directory is provided which contains a module, will return a
   * ModuleUpdater.
   *
   * @param string $source
   *   Directory of a Drupal project.
   *
   * @return Drupal\Core\Updater\Updater
   *   A new Drupal\Core\Updater\Updater object.
   *
   * @throws Drupal\Core\Updater\UpdaterException
   */
  public static function factory($source) {
    if (is_dir($source)) {
      $updater = self::getUpdaterFromDirectory($source);
    }
    else {
      throw new UpdaterException(t('Unable to determine the type of the source directory.'));
    }
    return new $updater($source);
  }

  /**
   * Determines which Updater class can operate on the given directory.
   *
   * @param string $directory
   *   Extracted Drupal project.
   *
   * @return string
   *   The class name which can work with this project type.
   *
   * @throws Drupal\Core\Updater\UpdaterException
   */
  public static function getUpdaterFromDirectory($directory) {
    // Gets a list of possible implementing classes.
    $updaters = drupal_get_updaters();
    foreach ($updaters as $updater) {
      $class = $updater['class'];
      if (call_user_func(array($class, 'canUpdateDirectory'), $directory)) {
        return $class;
      }
    }
    throw new UpdaterException(t('Cannot determine the type of project.'));
  }

  /**
   * Determines what the most important (or only) info file is in a directory.
   *
   * Since there is no enforcement of which info file is the project's "main"
   * info file, this will get one with the same name as the directory, or the
   * first one it finds.  Not ideal, but needs a larger solution.
   *
   * @param string $directory
   *   Directory to search in.
   *
   * @return string
   *   Path to the info file.
   */
  public static function findInfoFile($directory) {
    $info_files = file_scan_directory($directory, '/.*\.info$/');
    if (!$info_files) {
      return FALSE;
    }
    foreach ($info_files as $info_file) {
      if (drupal_substr($info_file->filename, 0, -5) == drupal_basename($directory)) {
        // Info file Has the same name as the directory, return it.
        return $info_file->uri;
      }
    }
    // Otherwise, return the first one.
    $info_file = array_shift($info_files);
    return $info_file->uri;
  }

  /**
   * Gets the name of the project directory (basename).
   *
   * @todo It would be nice, if projects contained an info file which could
   *   provide their canonical name.
   *
   * @param string $directory
   *
   * @return string
   *   The name of the project.
   */
  public static function getProjectName($directory) {
    return drupal_basename($directory);
  }

  /**
   * Returns the project name from a Drupal info file.
   *
   * @param string $directory
   *   Directory to search for the info file.
   *
   * @return string
   *   The title of the project.
   *
   * @throws Drupal\Core\Updater\UpdaterException
   */
  public static function getProjectTitle($directory) {
    $info_file = self::findInfoFile($directory);
    $info = drupal_parse_info_file($info_file);
    if (empty($info)) {
      throw new UpdaterException(t('Unable to parse info file: %info_file.', array('%info_file' => $info_file)));
    }
    if (empty($info['name'])) {
      throw new UpdaterException(t("The info file (%info_file) does not define a 'name' attribute.", array('%info_file' => $info_file)));
    }
    return $info['name'];
  }

  /**
   * Stores the default parameters for the Updater.
   *
   * @param array $overrides
   *   An array of overrides for the default parameters.
   *
   * @return array
   *   An array of configuration parameters for an update or install operation.
   */
  protected function getInstallArgs($overrides = array()) {
    $args = array(
      'make_backup' => FALSE,
      'install_dir' => $this->getInstallDirectory(),
      'backup_dir'  => $this->getBackupDir(),
    );
    return array_merge($args, $overrides);
  }

  /**
   * Updates a Drupal project and returns a list of next actions.
   *
   * @param Drupal\Core\FileTransfer\FileTransferInterface $filetransfer
   *   Object that is a child of FileTransfer. Used for moving files
   *   to the server.
   * @param array $overrides
   *   An array of settings to override defaults; see self::getInstallArgs().
   *
   * @return array
   *   An array of links which the user may need to complete the update
   *
   * @throws Drupal\Core\Updater\UpdaterException
   * @throws Drupal\Core\Updater\UpdaterFileTransferException
   */
  public function update(&$filetransfer, $overrides = array()) {
    try {
      // Establish arguments with possible overrides.
      $args = $this->getInstallArgs($overrides);

      // Take a Backup.
      if ($args['make_backup']) {
        $this->makeBackup($args['install_dir'], $args['backup_dir']);
      }

      if (!$this->name) {
        // This is bad, don't want to delete the install directory.
        throw new UpdaterException(t('Fatal error in update, cowardly refusing to wipe out the install directory.'));
      }

      // Make sure the installation parent directory exists and is writable.
      $this->prepareInstallDirectory($filetransfer, $args['install_dir']);

      // Note: If the project is installed in the top-level, it will not be
      // deleted. It will be installed in sites/default as that will override
      // the top-level reference and not break other sites which are using it.
      if (is_dir($args['install_dir'] . '/' . $this->name)) {
        // Remove the existing installed file.
        $filetransfer->removeDirectory($args['install_dir'] . '/' . $this->name);
      }

      // Copy the directory in place.
      $filetransfer->copyDirectory($this->source, $args['install_dir']);

      // Make sure what we just installed is readable by the web server.
      $this->makeWorldReadable($filetransfer, $args['install_dir'] . '/' . $this->name);

      // Run the updates.
      // @todo Decide if we want to implement this.
      $this->postUpdate();

      // For now, just return a list of links of things to do.
      return $this->postUpdateTasks();
    }
    catch (FileTransferException $e) {
      throw new UpdaterFileTransferException(t('File Transfer failed, reason: !reason', array('!reason' => strtr($e->getMessage(), $e->arguments))));
    }
  }

  /**
   * Installs a Drupal project, returns a list of next actions.
   *
   * @param Drupal\Core\FileTransfer\FileTransferInterface $filetransfer
   *   Object that is a child of FileTransfer.
   * @param array $overrides
   *   An array of settings to override defaults; see self::getInstallArgs().
   *
   * @return array
   *   An array of links which the user may need to complete the install.
   *
   * @throws Drupal\Core\Updater\UpdaterFileTransferException
   */
  public function install(&$filetransfer, $overrides = array()) {
    try {
      // Establish arguments with possible overrides.
      $args = $this->getInstallArgs($overrides);

      // Make sure the installation parent directory exists and is writable.
      $this->prepareInstallDirectory($filetransfer, $args['install_dir']);

      // Copy the directory in place.
      $filetransfer->copyDirectory($this->source, $args['install_dir']);

      // Make sure what we just installed is readable by the web server.
      $this->makeWorldReadable($filetransfer, $args['install_dir'] . '/' . $this->name);

      // Potentially enable something?
      // @todo Decide if we want to implement this.
      $this->postInstall();
      // For now, just return a list of links of things to do.
      return $this->postInstallTasks();
    }
    catch (FileTransferException $e) {
      throw new UpdaterFileTransferException(t('File Transfer failed, reason: !reason', array('!reason' => strtr($e->getMessage(), $e->arguments))));
    }
  }

  /**
   * Makes sure the installation parent directory exists and is writable.
   *
   * @param Drupal\Core\FileTransfer\FileTransferInterface $filetransfer
   *   Object which is a child of FileTransfer.
   * @param string $directory
   *   The installation directory to prepare.
   *
   * @throws Drupal\Core\Updater\UpdaterException
   */
  public function prepareInstallDirectory(&$filetransfer, $directory) {
    // Make the parent dir writable if need be and create the dir.
    if (!is_dir($directory)) {
      $parent_dir = dirname($directory);
      if (!is_writable($parent_dir)) {
        @chmod($parent_dir, 0755);
        // It is expected that this will fail if the directory is owned by the
        // FTP user. If the FTP user == web server, it will succeed.
        try {
          $filetransfer->createDirectory($directory);
          $this->makeWorldReadable($filetransfer, $directory);
        }
        catch (FileTransferException $e) {
          // Probably still not writable. Try to chmod and do it again.
          // @todo Make a new exception class so we can catch it differently.
          try {
            $old_perms = substr(sprintf('%o', fileperms($parent_dir)), -4);
            $filetransfer->chmod($parent_dir, 0755);
            $filetransfer->createDirectory($directory);
            $this->makeWorldReadable($filetransfer, $directory);
            // Put the permissions back.
            $filetransfer->chmod($parent_dir, intval($old_perms, 8));
          }
          catch (FileTransferException $e) {
            $message = t($e->getMessage(), $e->arguments);
            $throw_message = t('Unable to create %directory due to the following: %reason', array('%directory' => $directory, '%reason' => $message));
            throw new UpdaterException($throw_message);
          }
        }
        // Put the parent directory back.
        @chmod($parent_dir, 0555);
      }
    }
  }

  /**
   * Ensures that a given directory is world readable.
   *
   * @param Drupal\Core\FileTransfer\FileTransferInterface $filetransfer
   *   Object which is a child of FileTransfer.
   * @param string $path
   *   The file path to make world readable.
   * @param bool $recursive
   *   If the chmod should be applied recursively.
   */
  public function makeWorldReadable(&$filetransfer, $path, $recursive = TRUE) {
    if (!is_executable($path)) {
      // Set it to read + execute.
      $new_perms = substr(sprintf('%o', fileperms($path)), -4, -1) . "5";
      $filetransfer->chmod($path, intval($new_perms, 8), $recursive);
    }
  }

  /**
   * Performs a backup.
   *
   * @todo Not implemented.
   */
  public function makeBackup(&$filetransfer, $from, $to) {
  }

  /**
   * Returns the full path to a directory where backups should be written.
   */
  public function getBackupDir() {
    return file_stream_wrapper_get_instance_by_scheme('temporary')->getDirectoryPath();
  }

  /**
   * Performs actions after new code is updated.
   */
  public function postUpdate() {
  }

  /**
   * Performs actions after installation.
   */
  public function postInstall() {
  }

  /**
   * Returns an array of links to pages that should be visited post operation.
   *
   * @return array
   *   Links which provide actions to take after the install is finished.
   */
  public function postInstallTasks() {
    return array();
  }

  /**
   * Returns an array of links to pages that should be visited post operation.
   *
   * @return array
   *   Links which provide actions to take after the update is finished.
   */
  public function postUpdateTasks() {
    return array();
  }
}
