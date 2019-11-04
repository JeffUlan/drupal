<?php

/**
 * @file
 * Post update functions for the Workspaces module.
 */

use Drupal\Core\Entity\ContentEntityNullStorage;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Site\Settings;

/**
 * Clear caches due to access changes.
 */
function workspaces_post_update_access_clear_caches() {
}

/**
 * Remove the default workspace.
 */
function workspaces_post_update_remove_default_workspace() {
  if ($workspace = \Drupal::entityTypeManager()->getStorage('workspace')->load('live')) {
    $workspace->delete();
  }
}

/**
 * Move the workspace association data to an entity field and a custom table.
 */
function workspaces_post_update_move_association_data(&$sandbox) {
  $database = \Drupal::database();
  $entity_definition_update_manager = \Drupal::entityDefinitionUpdateManager();
  $entity_type_manager = \Drupal::entityTypeManager();
  $entity_type = $entity_definition_update_manager->getEntityType('workspace_association');

  // We can't migrate the workspace association data if the entity type is not
  // using its default storage.
  if ($entity_type->getHandlerClasses()['storage'] !== 'Drupal\workspaces\WorkspaceAssociationStorage') {
    return;
  }

  // Since the custom storage class doesn't exist anymore, we have to use core's
  // default storage.
  $entity_type->setStorageClass(SqlContentEntityStorage::class);

  // If 'progress' is not set, this will be the first run of the batch.
  if (!isset($sandbox['progress'])) {
    $sandbox['progress'] = 0;
    $sandbox['current_id'] = -1;

    // Create a temporary table for the new workspace_association index.
    $schema = [
      'description' => 'Stores the association between entity revisions and their workspace.',
      'fields' => [
        'workspace' => [
          'type' => 'varchar_ascii',
          'length' => 128,
          'not null' => TRUE,
          'default' => '',
          'description' => 'The workspace ID.',
        ],
        'target_entity_type_id' => [
          'type' => 'varchar_ascii',
          'length' => EntityTypeInterface::ID_MAX_LENGTH,
          'not null' => TRUE,
          'default' => '',
          'description' => 'The ID of the associated entity type.',
        ],
        'target_entity_id' => [
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'description' => 'The ID of the associated entity.',
        ],
        'target_entity_revision_id' => [
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'description' => 'The revision ID of the associated entity.',
        ],
      ],
      'indexes' => [
        'target_entity_revision_id' => ['target_entity_revision_id'],
      ],
      'primary key' => ['workspace', 'target_entity_type_id', 'target_entity_id'],
    ];
    if ($database->schema()->tableExists('tmp_workspace_association')) {
      $database->schema()->dropTable('tmp_workspace_association');
    }
    $database->schema()->createTable('tmp_workspace_association', $schema);

    // Copy all the data from the base table of the 'workspace_association'
    // entity type to the temporary association table.
    $select = $database->select($entity_type->getBaseTable())
      ->fields($entity_type->getBaseTable(), ['workspace', 'target_entity_type_id', 'target_entity_id', 'target_entity_revision_id']);
    $database->insert('tmp_workspace_association')->from($select)->execute();
  }

  $table_name = $entity_type->getRevisionTable();
  $revision_field_name = 'revision_id';

  // Get the next entity association revision records to migrate.
  $step_size = Settings::get('entity_update_batch_size', 50);
  $workspace_association_records = $database->select($table_name, 't')
    ->condition("t.$revision_field_name", $sandbox['current_id'], '>')
    ->fields('t')
    ->orderBy($revision_field_name, 'ASC')
    ->range(0, $step_size)
    ->execute()
    ->fetchAll();

  foreach ($workspace_association_records as $record) {
    // Set the workspace reference on the tracked entity revision.
    /** @var \Drupal\Core\Entity\ContentEntityInterface $revision */
    $revision = $entity_type_manager->getStorage($record->target_entity_type_id)->loadRevision($record->target_entity_revision_id);
    $revision->set('workspace', $record->workspace);
    $revision->setSyncing(TRUE);
    $revision->save();

    $sandbox['progress']++;
    $sandbox['current_id'] = $record->{$revision_field_name};
  }

  // Get an updated count of workspace_association revisions that still need to
  // be migrated to the new storage.
  $missing = $database->select($table_name, 't')
    ->condition("t.$revision_field_name", $sandbox['current_id'], '>')
    ->orderBy($revision_field_name, 'ASC')
    ->countQuery()
    ->execute()
    ->fetchField();
  $sandbox['#finished'] = $missing ? $sandbox['progress'] / ($sandbox['progress'] + (int) $missing) : 1;

  // Uninstall the 'workspace_association' entity type and rename the temporary
  // table.
  if ($sandbox['#finished'] == 1) {
    $entity_type->setStorageClass(ContentEntityNullStorage::class);
    $entity_definition_update_manager->uninstallEntityType($entity_type);
    $database->schema()->dropTable('workspace_association');
    $database->schema()->dropTable('workspace_association_revision');

    $database->schema()->renameTable('tmp_workspace_association', 'workspace_association');
  }
}

/**
 * Add the workspace 'parent' field to the 'deploy' form display.
 */
function workspaces_post_update_update_deploy_form_display() {
  if ($form_display = EntityFormDisplay::load('workspace.workspace.deploy')) {
    $form_display->removeComponent('parent')->save();
  }
}
