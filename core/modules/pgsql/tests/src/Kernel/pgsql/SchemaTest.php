<?php

namespace Drupal\Tests\pgsql\Kernel\pgsql;

use Drupal\KernelTests\Core\Database\DriverSpecificSchemaTestBase;

/**
 * Tests schema API for the PostgreSQL driver.
 *
 * @group Database
 */
class SchemaTest extends DriverSpecificSchemaTestBase {

  /**
   * {@inheritdoc}
   */
  public function checkSchemaComment(string $description, string $table, string $column = NULL): void {
    $this->assertSame($description, $this->schema->getComment($table, $column), 'The comment matches the schema description.');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkSequenceRenaming(string $tableName): void {
    // For PostgreSQL, we also need to check that the sequence has been renamed.
    // The initial name of the sequence has been generated automatically by
    // PostgreSQL when the table was created, however, on subsequent table
    // renames the name is generated by Drupal and can not be easily
    // re-constructed. Hence we can only check that we still have a sequence on
    // the new table name.
    $sequenceExists = (bool) $this->connection->query("SELECT pg_get_serial_sequence('{" . $tableName . "}', 'id')")->fetchField();
    $this->assertTrue($sequenceExists, 'Sequence was renamed.');

    // Rename the table again and repeat the check.
    $anotherTableName = strtolower($this->getRandomGenerator()->name(63 - strlen($this->getDatabasePrefix())));
    $this->schema->renameTable($tableName, $anotherTableName);

    $sequenceExists = (bool) $this->connection->query("SELECT pg_get_serial_sequence('{" . $anotherTableName . "}', 'id')")->fetchField();
    $this->assertTrue($sequenceExists, 'Sequence was renamed.');
  }

  /**
   * @covers \Drupal\pgsql\Driver\Database\pgsql\Schema::introspectIndexSchema
   */
  public function testIntrospectIndexSchema(): void {
    $table_specification = [
      'fields' => [
        'id'  => [
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ],
        'test_field_1'  => [
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ],
        'test_field_2'  => [
          'type' => 'int',
          'default' => 0,
        ],
        'test_field_3'  => [
          'type' => 'int',
          'default' => 0,
        ],
        'test_field_4'  => [
          'type' => 'int',
          'default' => 0,
        ],
        'test_field_5'  => [
          'type' => 'int',
          'default' => 0,
        ],
      ],
      'primary key' => ['id', 'test_field_1'],
      'unique keys' => [
        'test_field_2' => ['test_field_2'],
        'test_field_3_test_field_4' => ['test_field_3', 'test_field_4'],
      ],
      'indexes' => [
        'test_field_4' => ['test_field_4'],
        'test_field_4_test_field_5' => ['test_field_4', 'test_field_5'],
      ],
    ];

    $table_name = strtolower($this->getRandomGenerator()->name());
    $this->schema->createTable($table_name, $table_specification);

    unset($table_specification['fields']);

    $introspect_index_schema = new \ReflectionMethod(get_class($this->schema), 'introspectIndexSchema');
    $introspect_index_schema->setAccessible(TRUE);
    $index_schema = $introspect_index_schema->invoke($this->schema, $table_name);

    // The PostgreSQL driver is using a custom naming scheme for its indexes, so
    // we need to adjust the initial table specification.
    $ensure_identifier_length = new \ReflectionMethod(get_class($this->schema), 'ensureIdentifiersLength');
    $ensure_identifier_length->setAccessible(TRUE);

    foreach ($table_specification['unique keys'] as $original_index_name => $columns) {
      unset($table_specification['unique keys'][$original_index_name]);
      $new_index_name = $ensure_identifier_length->invoke($this->schema, $table_name, $original_index_name, 'key');
      $table_specification['unique keys'][$new_index_name] = $columns;
    }

    foreach ($table_specification['indexes'] as $original_index_name => $columns) {
      unset($table_specification['indexes'][$original_index_name]);
      $new_index_name = $ensure_identifier_length->invoke($this->schema, $table_name, $original_index_name, 'idx');
      $table_specification['indexes'][$new_index_name] = $columns;
    }

    $this->assertEquals($table_specification, $index_schema);
  }

  /**
   * {@inheritdoc}
   */
  public function testReservedKeywordsForNaming(): void {
    $table_specification = [
      'description' => 'A test table with an ANSI reserved keywords for naming.',
      'fields' => [
        'primary' => [
          'description' => 'Simple unique ID.',
          'type' => 'int',
          'not null' => TRUE,
        ],
        'update' => [
          'description' => 'A column with reserved name.',
          'type' => 'varchar',
          'length' => 255,
        ],
      ],
      'primary key' => ['primary'],
      'unique keys' => [
        'having' => ['update'],
      ],
      'indexes' => [
        'in' => ['primary', 'update'],
      ],
    ];

    // Creating a table.
    $table_name = 'select';
    $this->schema->createTable($table_name, $table_specification);
    $this->assertTrue($this->schema->tableExists($table_name));

    // Finding all tables.
    $tables = $this->schema->findTables('%');
    sort($tables);
    $this->assertEquals(['config', 'select'], $tables);

    // Renaming a table.
    $table_name_new = 'from';
    $this->schema->renameTable($table_name, $table_name_new);
    $this->assertFalse($this->schema->tableExists($table_name));
    $this->assertTrue($this->schema->tableExists($table_name_new));

    // Adding a field.
    $field_name = 'delete';
    $this->schema->addField($table_name_new, $field_name, ['type' => 'int', 'not null' => TRUE]);
    $this->assertTrue($this->schema->fieldExists($table_name_new, $field_name));

    // Dropping a primary key.
    $this->schema->dropPrimaryKey($table_name_new);

    // Adding a primary key.
    $this->schema->addPrimaryKey($table_name_new, [$field_name]);

    // Check the primary key columns.
    $find_primary_key_columns = new \ReflectionMethod(get_class($this->schema), 'findPrimaryKeyColumns');
    $this->assertEquals([$field_name], $find_primary_key_columns->invoke($this->schema, $table_name_new));

    // Dropping a primary key.
    $this->schema->dropPrimaryKey($table_name_new);

    // Changing a field.
    $field_name_new = 'where';
    $this->schema->changeField($table_name_new, $field_name, $field_name_new, ['type' => 'int', 'not null' => FALSE]);
    $this->assertFalse($this->schema->fieldExists($table_name_new, $field_name));
    $this->assertTrue($this->schema->fieldExists($table_name_new, $field_name_new));

    // Adding an unique key
    $unique_key_name = $unique_key_introspect_name = 'unique';
    $this->schema->addUniqueKey($table_name_new, $unique_key_name, [$field_name_new]);

    // Check the unique key columns.
    $introspect_index_schema = new \ReflectionMethod(get_class($this->schema), 'introspectIndexSchema');
    $ensure_identifiers_length = new \ReflectionMethod(get_class($this->schema), 'ensureIdentifiersLength');
    $unique_key_introspect_name = $ensure_identifiers_length->invoke($this->schema, $table_name_new, $unique_key_name, 'key');
    $this->assertEquals([$field_name_new], $introspect_index_schema->invoke($this->schema, $table_name_new)['unique keys'][$unique_key_introspect_name]);

    // Dropping an unique key
    $this->schema->dropUniqueKey($table_name_new, $unique_key_name);

    // Dropping a field.
    $this->schema->dropField($table_name_new, $field_name_new);
    $this->assertFalse($this->schema->fieldExists($table_name_new, $field_name_new));

    // Adding an index.
    $index_name = $index_introspect_name = 'index';
    $this->schema->addIndex($table_name_new, $index_name, ['update'], $table_specification);
    $this->assertTrue($this->schema->indexExists($table_name_new, $index_name));

    // Check the index columns.
    $index_introspect_name = $ensure_identifiers_length->invoke($this->schema, $table_name_new, $index_name, 'idx');
    $this->assertEquals(['update'], $introspect_index_schema->invoke($this->schema, $table_name_new)['indexes'][$index_introspect_name]);

    // Dropping an index.
    $this->schema->dropIndex($table_name_new, $index_name);
    $this->assertFalse($this->schema->indexExists($table_name_new, $index_name));

    // Dropping a table.
    $this->schema->dropTable($table_name_new);
    $this->assertFalse($this->schema->tableExists($table_name_new));
  }

  /**
   * @covers \Drupal\Core\Database\Driver\pgsql\Schema::extensionExists
   */
  public function testPgsqlExtensionExists(): void {
    // Test the method for a non existing extension.
    $this->assertFalse($this->schema->extensionExists('non_existing_extension'));

    // Test the method for an existing extension.
    $this->assertTrue($this->schema->extensionExists('pg_trgm'));
  }

}
