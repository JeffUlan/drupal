<?php

namespace Drupal\Tests\system\Kernel\Scripts;

use Drupal\Core\Command\DbDumpCommand;
use Drupal\Core\Database\Database;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Test that the DbDumpCommand works correctly.
 *
 * @group console
 */
class DbDumpCommandTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Determine what database backend is running, and set the skip flag.
    if (Database::getConnection()->databaseType() !== 'mysql') {
      $this->markTestSkipped("Skipping test since the DbDumpCommand is currently only compatible with MySQL");
    }

    // Rebuild the router to ensure a routing table.
    \Drupal::service('router.builder')->rebuild();

    /** @var \Drupal\Core\Database\Connection $connection */
    $connection = $this->container->get('database');
    $connection->insert('router')->fields(['name', 'path', 'pattern_outline'])->values(['test', 'test', 'test'])->execute();
  }

  /**
   * Test the command directly.
   */
  public function testDbDumpCommand() {
    $command = new DbDumpCommand();
    $command_tester = new CommandTester($command);
    $command_tester->execute([]);

    // Assert that insert exists and that some expected fields exist.
    $output = $command_tester->getDisplay();
    $this->assertStringContainsString("createTable('router", $output, 'Table router found');
    $this->assertStringContainsString("insert('router", $output, 'Insert found');
    $this->assertStringContainsString("'name' => 'test", $output, 'Insert name field found');
    $this->assertStringContainsString("'path' => 'test", $output, 'Insert path field found');
    $this->assertStringContainsString("'pattern_outline' => 'test", $output, 'Insert pattern_outline field found');
    $this->assertStringContainsString("// @codingStandardsIgnoreFile", $output);
  }

  /**
   * Test schema only option.
   */
  public function testSchemaOnly() {
    $command = new DbDumpCommand();
    $command_tester = new CommandTester($command);
    $command_tester->execute(['--schema-only' => 'router']);

    // Assert that insert statement doesn't exist for schema only table.
    $output = $command_tester->getDisplay();
    $this->assertStringContainsString("createTable('router", $output, 'Table router found');
    $this->assertStringNotContainsString("insert('router", $output, 'Insert not found');
    $this->assertStringNotContainsString("'name' => 'test", $output, 'Insert name field not found');
    $this->assertStringNotContainsString("'path' => 'test", $output, 'Insert path field not found');
    $this->assertStringNotContainsString("'pattern_outline' => 'test", $output, 'Insert pattern_outline field not found');

    // Assert that insert statement doesn't exist for wildcard schema only match.
    $command_tester->execute(['--schema-only' => 'route.*']);
    $output = $command_tester->getDisplay();
    $this->assertStringContainsString("createTable('router", $output, 'Table router found');
    $this->assertStringNotContainsString("insert('router", $output, 'Insert not found');
    $this->assertStringNotContainsString("'name' => 'test", $output, 'Insert name field not found');
    $this->assertStringNotContainsString("'path' => 'test", $output, 'Insert path field not found');
    $this->assertStringNotContainsString("'pattern_outline' => 'test", $output, 'Insert pattern_outline field not found');
  }

}
