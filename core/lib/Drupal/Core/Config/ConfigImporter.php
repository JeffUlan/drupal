<?php

/**
 * @file
 * Contains \Drupal\Core\Config\ConfigImporter.
 */

namespace Drupal\Core\Config;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Component\Utility\String;
use Drupal\Core\Config\Entity\ImportableEntityStorageInterface;
use Drupal\Core\DependencyInjection\DependencySerialization;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines a configuration importer.
 *
 * A config importer imports the changes into the configuration system. To
 * determine which changes to import a StorageComparer in used.
 *
 * @see \Drupal\Core\Config\StorageComparerInterface
 *
 * The ConfigImporter has a identifier which is used to construct event names.
 * The events fired during an import are:
 * - ConfigEvents::IMPORT_VALIDATE: Events listening can throw a
 *   \Drupal\Core\Config\ConfigImporterException to prevent an import from
 *   occurring.
 *   @see \Drupal\Core\EventSubscriber\ConfigImportSubscriber
 * - ConfigEvents::IMPORT: Events listening can react to a successful import.
 *   @see \Drupal\Core\EventSubscriber\ConfigSnapshotSubscriber
 *
 * @see \Drupal\Core\Config\ConfigImporterEvent
 */
class ConfigImporter extends DependencySerialization {

  /**
   * The name used to identify the lock.
   */
  const LOCK_ID = 'config_importer';

  /**
   * The storage comparer used to discover configuration changes.
   *
   * @var \Drupal\Core\Config\StorageComparerInterface
   */
  protected $storageComparer;

  /**
   * The event dispatcher used to notify subscribers.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * The used lock backend instance.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * List of configuration file changes processed by the import().
   *
   * @var array
   */
  protected $processedConfiguration;

  /**
   * List of extension changes processed by the import().
   *
   * @var array
   */
  protected $processedExtensions;

  /**
   * List of extension changes to be processed by the import().
   *
   * @var array
   */
  protected $extensionChangelist;

  /**
   * Indicates changes to import have been validated.
   *
   * @var bool
   */
  protected $validated;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $translationManager;

  /**
   * Flag set to import system.theme during processing theme enable and disables.
   *
   * @var bool
   */
  protected $processedSystemTheme = FALSE;

  /**
   * A log of any errors encountered.
   *
   * If errors are logged during the validation event the configuration
   * synchronization will not occur. If errors occur during an import then best
   * efforts are made to complete the synchronization.
   *
   * @var array
   */
  protected $errors = array();

  /**
   * The total number of extensions to process.
   *
   * @var int
   */
  protected $totalExtensionsToProcess = 0;

  /**
   * The total number of configuration objects to process.
   *
   * @var int
   */
  protected $totalConfigurationToProcess = 0;

  /**
   * Constructs a configuration import object.
   *
   * @param \Drupal\Core\Config\StorageComparerInterface $storage_comparer
   *   A storage comparer object used to determin configuration changes and
   *   access the source and target storage objects.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher used to notify subscribers of config import events.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The configuration manager.
   * @param \Drupal\Core\Lock\LockBackendInterface
   *   The lock backend to ensure multiple imports do not occur at the same time.
   * @param \Drupal\Core\Config\TypedConfigManager $typed_config
   *   The typed configuration manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler
   * @param \Drupal\Core\StringTranslation\TranslationManager $translation_manager
   *   The string translation service.
   */
  public function __construct(StorageComparerInterface $storage_comparer, EventDispatcherInterface $event_dispatcher, ConfigManagerInterface $config_manager, LockBackendInterface $lock, TypedConfigManagerInterface $typed_config, ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler, TranslationManager $translation_manager) {
    $this->storageComparer = $storage_comparer;
    $this->eventDispatcher = $event_dispatcher;
    $this->configManager = $config_manager;
    $this->lock = $lock;
    $this->typedConfigManager = $typed_config;
    $this->moduleHandler = $module_handler;
    $this->themeHandler = $theme_handler;
    $this->translationManager = $translation_manager;
    $this->processedConfiguration = $this->storageComparer->getEmptyChangelist();
    $this->processedExtensions = $this->getEmptyExtensionsProcessedList();
  }

  /**
   * Logs an error message.
   *
   * @param string $message
   *   The message to log.
   */
  public function logError($message) {
    $this->errors[] = $message;
  }

  /**
   * Returns error messages created while running the import.
   *
   * @return array
   *   List of messages.
   */
  public function getErrors() {
    return $this->errors;
  }

  /**
   * Gets the configuration storage comparer.
   *
   * @return \Drupal\Core\Config\StorageComparerInterface
   *   Storage comparer object used to calculate configuration changes.
   */
  public function getStorageComparer() {
    return $this->storageComparer;
  }

  /**
   * Resets the storage comparer and processed list.
   *
   * @return \Drupal\Core\Config\ConfigImporter
   *   The ConfigImporter instance.
   */
  public function reset() {
    $this->storageComparer->reset();
    $this->processedConfiguration = $this->storageComparer->getEmptyChangelist();
    $this->processedExtensions = $this->getEmptyExtensionsProcessedList();
    $this->createExtensionChangelist();
    $this->validated = FALSE;
    $this->processedSystemTheme = FALSE;
    return $this;
  }

  /**
   * Gets an empty list of extensions to process.
   *
   * @return array
   *   An empty list of extensions to process.
   */
  protected function getEmptyExtensionsProcessedList() {
    return array(
      'module' => array(
        'install' => array(),
        'uninstall' => array(),
      ),
      'theme' => array(
        'enable' => array(),
        'disable' => array(),
      ),
    );
  }

  /**
   * Checks if there are any unprocessed configuration changes.
   *
   * @param array $ops
   *   The operations to check for changes. Defaults to all operations, i.e.
   *   array('delete', 'create', 'update').
   *
   * @return bool
   *   TRUE if there are changes to process and FALSE if not.
   */
  public function hasUnprocessedConfigurationChanges($ops = array('delete', 'create', 'update')) {
    foreach ($ops as $op) {
      if (count($this->getUnprocessedConfiguration($op))) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Gets list of processed changes.
   *
   * @return array
   *   An array containing a list of processed changes.
   */
  public function getProcessedConfiguration() {
    return $this->processedConfiguration;
  }

  /**
   * Sets a change as processed.
   *
   * @param string $op
   *   The change operation performed, either delete, create or update.
   * @param string $name
   *   The name of the configuration processed.
   */
  protected function setProcessedConfiguration($op, $name) {
    $this->processedConfiguration[$op][] = $name;
  }

  /**
   * Gets a list of unprocessed changes for a given operation.
   *
   * @param string $op
   *   The change operation to get the unprocessed list for, either delete,
   *   create or update.
   *
   * @return array
   *   An array of configuration names.
   */
  public function getUnprocessedConfiguration($op) {
    return array_diff($this->storageComparer->getChangelist($op), $this->processedConfiguration[$op]);
  }

  /**
   * Gets list of processed extension changes.
   *
   * @return array
   *   An array containing a list of processed extension changes.
   */
  public function getProcessedExtensions() {
    return $this->processedExtensions;
  }

  /**
   * Determines if the current import has processed extensions.
   *
   * @return bool
   *   TRUE if the ConfigImporter has processed extensions.
   */
  protected function hasProcessedExtensions() {
    $compare = array_diff($this->processedExtensions, getEmptyExtensionsProcessedList());
    return !empty($compare);
  }

  /**
   * Sets an extension change as processed.
   *
   * @param string $type
   *   The type of extension, either 'theme' or 'module'.
   * @param string $op
   *   The change operation performed, either install or uninstall.
   * @param string $name
   *   The name of the extension processed.
   */
  protected function setProcessedExtension($type, $op, $name) {
    $this->processedExtensions[$type][$op][] = $name;
  }

  /**
   * Populates the extension change list.
   */
  protected function createExtensionChangelist() {
    // Read the extensions information to determine changes.
    $current_extensions = $this->storageComparer->getTargetStorage()->read('core.extension');
    $new_extensions = $this->storageComparer->getSourceStorage()->read('core.extension');

    // If there is no extension information in staging then exit. This is
    // probably due to an empty staging directory.
    if (!$new_extensions) {
      return;
    }

    // Get a list of modules with dependency weights as values.
    $module_data = system_rebuild_module_data();
    // Set the actual module weights.
    $module_list = array_combine(array_keys($module_data), array_keys($module_data));
    $module_list = array_map(function ($module) use ($module_data) {
      return $module_data[$module]->sort;
    }, $module_list);

    // Work out what modules to install and uninstall.
    $uninstall = array_diff(array_keys($current_extensions['module']), array_keys($new_extensions['module']));
    $install = array_diff(array_keys($new_extensions['module']), array_keys($current_extensions['module']));
    // Sort the module list by their weights. So that dependencies
    // are uninstalled last.
    asort($module_list);
    $uninstall = array_intersect(array_keys($module_list), $uninstall);
    // Sort the module list by their weights (reverse). So that dependencies
    // are installed first.
    arsort($module_list);
    $install = array_intersect(array_keys($module_list), $install);

    // Work out what themes to enable and to disable.
    $enable = array_diff(array_keys($new_extensions['theme']), array_keys($current_extensions['theme']));
    $disable = array_diff(array_keys($current_extensions['theme']), array_keys($new_extensions['theme']));

    $this->extensionChangelist = array(
      'module' => array(
        'uninstall' => $uninstall,
        'install' => $install,
      ),
      'theme' => array(
        'enable' => $enable,
        'disable' => $disable,
      ),
    );
  }

  /**
   * Gets a list changes for extensions.
   *
   * @param string $type
   *   The type of extension, either 'theme' or 'module'.
   * @param string $op
   *   The change operation to get the unprocessed list for, either install
   *   or uninstall.
   *
   * @return array
   *   An array of extension names.
   */
  protected function getExtensionChangelist($type, $op = NULL) {
    if ($op) {
      return $this->extensionChangelist[$type][$op];
    }
    return $this->extensionChangelist[$type];
  }

  /**
   * Gets a list of unprocessed changes for extensions.
   *
   * @param string $type
   *   The type of extension, either 'theme' or 'module'.
   *
   * @return array
   *   An array of extension names.
   */
  public function getUnprocessedExtensions($type) {
    $changelist = $this->getExtensionChangelist($type);

    if ($type == 'theme') {
      $unprocessed = array(
        'enable' => array_diff($changelist['enable'], $this->processedExtensions[$type]['enable']),
        'disable' => array_diff($changelist['disable'], $this->processedExtensions[$type]['disable']),
      );
    }
    else {
      $unprocessed = array(
        'install' => array_diff($changelist['install'], $this->processedExtensions[$type]['install']),
        'uninstall' => array_diff($changelist['uninstall'], $this->processedExtensions[$type]['uninstall']),
      );
    }
    return $unprocessed;
  }

  /**
   * Imports the changelist to the target storage.
   *
   * @throws \Drupal\Core\Config\ConfigException
   *
   * @return \Drupal\Core\Config\ConfigImporter
   *   The ConfigImporter instance.
   */
  public function import() {
    if ($this->hasUnprocessedConfigurationChanges()) {
      $sync_steps = $this->initialize();

      foreach ($sync_steps as $step) {
        $context = array();
        do {
          $this->doSyncStep($step, $context);
        } while ($context['finished'] < 1);
      }
    }
    return $this;
  }

  /**
   * Calls a config import step.
   *
   * @param string|callable $sync_step
   *   The step to do. Either a method on the ConfigImporter class or a
   *   callable.
   * @param array $context
   *   A batch context array. If the config importer is not running in a batch
   *   the only array key that is used is $context['finished']. A process needs
   *   to set $context['finished'] = 1 when it is done.
   *
   * @throws \InvalidArgumentException
   *   Exception thrown if the $sync_step can not be called.
   */
  public function doSyncStep($sync_step, &$context) {
    if (method_exists($this, $sync_step)) {
      $this->$sync_step($context);
    }
    elseif (is_callable($sync_step)) {
      call_user_func_array($sync_step, array(&$context));
    }
    else {
      throw new \InvalidArgumentException('Invalid configuration synchronization step');
    }
  }

  /**
   * Initializes the config importer in preparation for processing a batch.
   *
   * @return array
   *   An array of \Drupal\Core\Config\ConfigImporter method names and callables
   *   that are invoked to complete the import. If there are modules or themes
   *   to process then an extra step is added.
   *
   * @throws \Drupal\Core\Config\ConfigImporterException
   *   If the configuration is already importing.
   */
  public function initialize() {
    $this->createExtensionChangelist();

    // Ensure that the changes have been validated.
    $this->validate();

    if (!$this->lock->acquire(static::LOCK_ID)) {
      // Another process is synchronizing configuration.
      throw new ConfigImporterException(sprintf('%s is already importing', static::LOCK_ID));
    }

    $sync_steps = array();
    $modules = $this->getUnprocessedExtensions('module');
    foreach (array('install', 'uninstall') as $op) {
      $this->totalExtensionsToProcess += count($modules[$op]);
    }
    $themes = $this->getUnprocessedExtensions('theme');
    foreach (array('enable', 'disable') as $op) {
      $this->totalExtensionsToProcess += count($themes[$op]);
    }

    // We have extensions to process.
    if ($this->totalExtensionsToProcess > 0) {
      $sync_steps[] = 'processExtensions';
    }
    $sync_steps[] = 'processConfigurations';

    // Allow modules to add new steps to configuration synchronization.
    $this->moduleHandler->alter('config_import_steps', $sync_steps);
    $sync_steps[] = 'finish';
    return $sync_steps;
  }

  /**
   * Processes extensions as a batch operation.
   *
   * @param array $context.
   *   The batch context.
   */
  public function processExtensions(array &$context) {
    $operation = $this->getNextExtensionOperation();
    if (!empty($operation)) {
      $this->processExtension($operation['type'], $operation['op'], $operation['name']);
      $context['message'] = t('Synchronising extensions: @op @name.', array('@op' => $operation['op'], '@name' => $operation['name']));
      $processed_count = count($this->processedExtensions['module']['install']) + count($this->processedExtensions['module']['uninstall']);
      $processed_count += count($this->processedExtensions['theme']['disable']) + count($this->processedExtensions['theme']['enable']);
      $context['finished'] = $processed_count / $this->totalExtensionsToProcess;
    }
    else {
      $context['finished'] = 1;
    }
  }

  /**
   * Processes configuration as a batch operation.
   *
   * @param array $context.
   *   The batch context.
   */
  public function processConfigurations(array &$context) {
    // The first time this is called we need to calculate the total to process.
    // This involves recalculating the changelist which will ensure that if
    // extensions have been processed any configuration affected will be taken
    // into account.
    if ($this->totalConfigurationToProcess == 0) {
      $this->storageComparer->reset();
      foreach (array('delete', 'create', 'update') as $op) {
        foreach ($this->getUnprocessedConfiguration($op) as $name) {
          $this->totalConfigurationToProcess += count($this->getUnprocessedConfiguration($op));
        }
      }
    }
    $operation = $this->getNextConfigurationOperation();
    if (!empty($operation)) {
      if ($this->checkOp($operation['op'], $operation['name'])) {
        $this->processConfiguration($operation['op'], $operation['name']);
      }
      $context['message'] = t('Synchronizing configuration: @op @name.', array('@op' => $operation['op'], '@name' => $operation['name']));
      $processed_count = count($this->processedConfiguration['create']) + count($this->processedConfiguration['delete']) + count($this->processedConfiguration['update']);
      $context['finished'] = $processed_count / $this->totalConfigurationToProcess;
    }
    else {
      $context['finished'] = 1;
    }
  }

  /**
   * Finishes the batch.
   *
   * @param array $context.
   *   The batch context.
   */
  public function finish(array &$context) {
    $this->eventDispatcher->dispatch(ConfigEvents::IMPORT, new ConfigImporterEvent($this));
    // The import is now complete.
    $this->lock->release(static::LOCK_ID);
    $this->reset();
    $context['message'] = t('Finalising configuration synchronisation.');
    $context['finished'] = 1;
  }

  /**
   * Gets the next extension operation to perform.
   *
   * @return array|bool
   *   An array containing the next operation and extension name to perform it
   *   on. If there is nothing left to do returns FALSE;
   */
  protected function getNextExtensionOperation() {
    foreach (array('install', 'uninstall') as $op) {
      $modules = $this->getUnprocessedExtensions('module');
      if (!empty($modules[$op])) {
        return array(
          'op' => $op,
          'type' => 'module',
          'name' => array_shift($modules[$op]),
        );
      }
    }
    foreach (array('enable', 'disable') as $op) {
      $themes = $this->getUnprocessedExtensions('theme');
      if (!empty($themes[$op])) {
        return array(
          'op' => $op,
          'type' => 'theme',
          'name' => array_shift($themes[$op]),
        );
      }
    }
    return FALSE;
  }

  /**
   * Gets the next configuration operation to perform.
   *
   * @return array|bool
   *   An array containing the next operation and configuration name to perform
   *   it on. If there is nothing left to do returns FALSE;
   */
  protected function getNextConfigurationOperation() {
    // The order configuration operations is processed is important. Deletes
    // have to come first so that recreates can work.
    foreach (array('delete', 'create', 'update') as $op) {
      $config_names = $this->getUnprocessedConfiguration($op);
      if (!empty($config_names)) {
        return array(
          'op' => $op,
          'name' => array_shift($config_names),
        );
      }
    }
    return FALSE;
  }

  /**
   * Dispatches validate event for a ConfigImporter object.
   *
   * Events should throw a \Drupal\Core\Config\ConfigImporterException to
   * prevent an import from occurring.
   *
   * @throws \Drupal\Core\Config\ConfigImporterException
   *   Exception thrown if the validate event logged any errors.
   */
  public function validate() {
    if (!$this->validated) {
      $this->eventDispatcher->dispatch(ConfigEvents::IMPORT_VALIDATE, new ConfigImporterEvent($this));
      if (count($this->getErrors())) {
        throw new ConfigImporterException('There were errors validating the config synchronization.');
      }
      else {
        $this->validated = TRUE;
      }
    }
    return $this;
  }

  /**
   * Processes a configuration change.
   *
   * @param string $op
   *   The change operation.
   * @param string $name
   *   The name of the configuration to process.
   *
   * @throws \Exception
   *   Thrown when the import process fails, only thrown when no importer log is
   *   set, otherwise the exception message is logged and the configuration
   *   is skipped.
   */
  protected function processConfiguration($op, $name) {
    try {
      if (!$this->importInvokeOwner($op, $name)) {
        $this->importConfig($op, $name);
      }
    }
    catch (\Exception $e) {
      $this->logError($this->t('Unexpected error during import with operation @op for @name: @message', array('@op' => $op, '@name' => $name, '@message' => $e->getMessage())));
      // Error for that operation was logged, mark it as processed so that
      // the import can continue.
      $this->setProcessedConfiguration($op, $name);
    }
  }

  /**
   * Processes an extension change.
   *
   * @param string $type
   *   The type of extension, either 'module' or 'theme'.
   * @param string $op
   *   The change operation.
   * @param string $name
   *   The name of the extension to process.
   */
  protected function processExtension($type, $op, $name) {
    // Set the config installer to use the staging directory instead of the
    // extensions own default config directories.
    \Drupal::service('config.installer')
      ->setSyncing(TRUE)
      ->setSourceStorage($this->storageComparer->getSourceStorage());
    if ($type == 'module') {
      $this->moduleHandler->$op(array($name), FALSE);
      // Installing a module can cause a kernel boot therefore reinject all the
      // services.
      $this->reInjectMe();
      // During a module install or uninstall the container is rebuilt and the
      // module handler is called from drupal_get_complete_schema(). This causes
      // the container's instance of the module handler not to have loaded all
      // the enabled modules.
      $this->moduleHandler->loadAll();
    }
    if ($type == 'theme') {
      // Theme disables possible remove default or admin themes therefore we
      // need to import this before doing any. If there are no disables and
      // the default or admin theme is change this will be picked up whilst
      // processing configuration.
      if ($op == 'disable' && $this->processedSystemTheme === FALSE) {
        $this->importConfig('update', 'system.theme');
        $this->configManager->getConfigFactory()->reset('system.theme');
        $this->processedSystemTheme = TRUE;
      }
      $this->themeHandler->$op(array($name));
    }

    $this->setProcessedExtension($type, $op, $name);
    \Drupal::service('config.installer')
      ->setSyncing(FALSE)
      ->resetSourceStorage();
  }

  /**
   * Checks that the operation is still valid.
   *
   * During a configuration import secondary writes and deletes are possible.
   * This method checks that the operation is still valid before processing a
   * configuration change.
   *
   * @param string $op
   *   The change operation.
   * @param string $name
   *   The name of the configuration to process.
   *
   * @throws \Drupal\Core\Config\ConfigImporterException
   *
   * @return bool
   *   TRUE is to continue processing, FALSE otherwise.
   */
  protected function checkOp($op, $name) {
    $target_exists = $this->storageComparer->getTargetStorage()->exists($name);
    switch ($op) {
      case 'delete':
        if (!$target_exists) {
          // The configuration has already been deleted. For example, a field
          // is automatically deleted if all the instances are.
          $this->setProcessedConfiguration($op, $name);
          return FALSE;
        }
        break;

      case 'create':
        if ($target_exists) {
          // If the target already exists, use the entity storage to delete it
          // again, if is a simple config, delete it directly.
          if ($entity_type_id = $this->configManager->getEntityTypeIdByName($name)) {
            $entity_storage = $this->configManager->getEntityManager()->getStorage($entity_type_id);
            $entity_type = $this->configManager->getEntityManager()->getDefinition($entity_type_id);
            $entity = $entity_storage->load($entity_storage->getIDFromConfigName($name, $entity_type->getConfigPrefix()));
            $entity->delete();
            $this->logError($this->translationManager->translate('Deleted and replaced configuration entity "@name"', array('@name' => $name)));
          }
          else {
            $this->storageComparer->getTargetStorage()->delete($name);
            $this->logError($this->t('Deleted and replaced configuration "@name"', array('@name' => $name)));
          }
          return TRUE;
        }
        break;

      case 'update':
        if (!$target_exists) {
          $this->logError($this->t('Update target "@name" is missing.', array('@name' => $name)));
          // Mark as processed so that the synchronisation continues. Once the
          // the current synchronisation is complete it will show up as a
          // create.
          $this->setProcessedConfiguration($op, $name);
          return FALSE;
        }
        break;
    }
    return TRUE;
  }

  /**
   * Writes a configuration change from the source to the target storage.
   *
   * @param string $op
   *   The change operation.
   * @param string $name
   *   The name of the configuration to process.
   */
  protected function importConfig($op, $name) {
    $config = new Config($name, $this->storageComparer->getTargetStorage(), $this->eventDispatcher, $this->typedConfigManager);
    if ($op == 'delete') {
      $config->delete();
    }
    else {
      $data = $this->storageComparer->getSourceStorage()->read($name);
      $config->setData($data ? $data : array());
      $config->save();
    }
    $this->setProcessedConfiguration($op, $name);
  }

  /**
   * Invokes import* methods on configuration entity storage.
   *
   * Allow modules to take over configuration change operations for higher-level
   * configuration data.
   *
   * @todo Add support for other extension types; e.g., themes etc.
   *
   * @param string $op
   *   The change operation to get the unprocessed list for, either delete,
   *   create or update.
   * @param string $name
   *   The name of the configuration to process.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown if the data is owned by an entity type, but the entity storage
   *   does not support imports.
   *
   * @return bool
   *   TRUE if the configuration was imported as a configuration entity. FALSE
   *   otherwise.
   */
  protected function importInvokeOwner($op, $name) {
    // Validate the configuration object name before importing it.
    // Config::validateName($name);
    if ($entity_type = $this->configManager->getEntityTypeIdByName($name)) {
      $old_config = new Config($name, $this->storageComparer->getTargetStorage(), $this->eventDispatcher, $this->typedConfigManager);
      if ($old_data = $this->storageComparer->getTargetStorage()->read($name)) {
        $old_config->initWithData($old_data);
      }

      $data = $this->storageComparer->getSourceStorage()->read($name);
      $new_config = new Config($name, $this->storageComparer->getTargetStorage(), $this->eventDispatcher, $this->typedConfigManager);
      if ($data !== FALSE) {
        $new_config->setData($data);
      }

      $method = 'import' . ucfirst($op);
      $entity_storage = $this->configManager->getEntityManager()->getStorage($entity_type);
      // Call to the configuration entity's storage to handle the configuration
      // change.
      if (!($entity_storage instanceof ImportableEntityStorageInterface)) {
        throw new EntityStorageException(String::format('The entity storage "@storage" for the "@entity_type" entity type does not support imports', array('@storage' => get_class($entity_storage), '@entity_type' => $entity_type)));
      }
      $entity_storage->$method($name, $new_config, $old_config);
      $this->setProcessedConfiguration($op, $name);
      return TRUE;
    }
  }

  /**
   * Determines if a import is already running.
   *
   * @return bool
   *   TRUE if an import is already running, FALSE if not.
   */
  public function alreadyImporting() {
    return !$this->lock->lockMayBeAvailable(static::LOCK_ID);
  }

  /**
   * Returns the identifier for events and locks.
   *
   * @return string
   *   The identifier for events and locks.
   */
  public function getId() {
    return static::LOCK_ID;
  }

  /**
   * Gets all the service dependencies from \Drupal.
   *
   * Since the ConfigImporter handles module installation the kernel and the
   * container can be rebuilt and altered during processing. It is necessary to
   * keep the services used by the importer in sync.
   */
  protected function reInjectMe() {
    $this->eventDispatcher = \Drupal::service('event_dispatcher');
    $this->configFactory = \Drupal::configFactory();
    $this->entityManager = \Drupal::entityManager();
    $this->lock = \Drupal::lock();
    $this->typedConfigManager = \Drupal::service('config.typed');
    $this->moduleHandler = \Drupal::moduleHandler();
    $this->themeHandler = \Drupal::service('theme_handler');
    $this->translationManager = \Drupal::service('string_translation');
  }

  /**
   * Translates a string to the current language or to a given language.
   *
   * @param string $string
   *   A string containing the English string to translate.
   * @param array $args
   *   An associative array of replacements to make after translation. Based
   *   on the first character of the key, the value is escaped and/or themed.
   *   See \Drupal\Component\Utility\String::format() for details.
   * @param array $options
   *   An associative array of additional options, with the following elements:
   *   - 'langcode': The language code to translate to a language other than
   *      what is used to display the page.
   *   - 'context': The context the source string belongs to.
   *
   * @return string
   *   The translated string.
   *
   * @see \Drupal\Core\StringTranslation\TranslationManager::translate()
   */
  protected function t($string, array $args = array(), array $options = array()) {
    return $this->translationManager->translate($string, $args, $options);
  }

}
