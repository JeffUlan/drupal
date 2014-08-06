<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Plugin\Block\ViewsBlockTest.
 */

namespace Drupal\views\Tests\Plugin\Block {

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\Block\ViewsBlock;
use Drupal\block\Plugin\views\display\Block;

// @todo Remove this once the constant got converted.
if (!defined('BLOCK_LABEL_VISIBLE')) {
  define('BLOCK_LABEL_VISIBLE', 'visible');
}

/**
 * @coversDefaultClass \Drupal\views\Plugin\block\ViewsBlock
 * @group views
 */
class ViewsBlockTest extends UnitTestCase {

  /**
   * The view executable.
   *
   * @var \Drupal\views\ViewExecutable|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $executable;

  /**
   * The view executable factory.
   *
   * @var \Drupal\views\ViewExecutableFactory|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $executableFactory;

  /**
   * The view entity.
   *
   * @var \Drupal\views\ViewStorageInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $view;

  /**
   * The view storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $storage;

  /**
   * The mocked user account.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp(); // TODO: Change the autogenerated stub
    $condition_plugin_manager = $this->getMock('Drupal\Core\Executable\ExecutableManagerInterface');
    $condition_plugin_manager->expects($this->any())
      ->method('getDefinitions')
      ->will($this->returnValue(array()));
    $container = new ContainerBuilder();
    $container->set('plugin.manager.condition', $condition_plugin_manager);
    \Drupal::setContainer($container);

    $this->executable = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->setMethods(array('executeDisplay', 'setDisplay', 'setItemsPerPage'))
      ->getMock();
    $this->executable->expects($this->any())
      ->method('setDisplay')
      ->with('block_1')
      ->will($this->returnValue(TRUE));

    $this->executable->display_handler = $this->getMockBuilder('Drupal\block\Plugin\views\display\Block')
      ->disableOriginalConstructor()
      ->setMethods(NULL)
      ->getMock();

    $this->view = $this->getMockBuilder('Drupal\views\Entity\View')
      ->disableOriginalConstructor()
      ->getMock();

    $this->executableFactory = $this->getMockBuilder('Drupal\views\ViewExecutableFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $this->executableFactory->expects($this->any())
      ->method('get')
      ->with($this->view)
      ->will($this->returnValue($this->executable));

    $this->storage = $this->getMockBuilder('Drupal\Core\Config\Entity\ConfigEntityStorage')
      ->disableOriginalConstructor()
      ->getMock();

    $this->storage->expects($this->any())
      ->method('load')
      ->with('test_view')
      ->will($this->returnValue($this->view));
    $this->account = $this->getMock('Drupal\Core\Session\AccountInterface');
  }

  /**
   * Tests the build method.
   *
   * @see \Drupal\views\Plugin\block\ViewsBlock::build()
   */
  public function testBuild() {
    $output = $this->randomMachineName(100);
    $build = array('#markup' => $output);
    $this->executable->expects($this->once())
      ->method('executeDisplay')
      ->with($this->equalTo('block_1'))
      ->will($this->returnValue($build));

    $block_id = 'views_block:test_view-block_1';
    $config = array();
    $definition = array();

    $definition['provider'] = 'views';
    $plugin = new ViewsBlock($config, $block_id, $definition, $this->executableFactory, $this->storage, $this->account);
    $reflector = new \ReflectionClass($plugin);
    $property = $reflector->getProperty('conditionPluginManager');
    $property->setAccessible(TRUE);
    $property->setValue($plugin, $this->getMock('Drupal\Core\Executable\ExecutableManagerInterface'));

    $this->assertEquals($build, $plugin->build());
  }

  /**
   * Tests the build method with a failed execution.
   *
   * @see \Drupal\views\Plugin\block\ViewsBlock::build()
   */
  public function testBuildFailed() {
    $output = FALSE;
    $this->executable->expects($this->once())
      ->method('executeDisplay')
      ->with($this->equalTo('block_1'))
      ->will($this->returnValue($output));

    $block_id = 'views_block:test_view-block_1';
    $config = array();
    $definition = array();

    $definition['provider'] = 'views';
    $plugin = new ViewsBlock($config, $block_id, $definition, $this->executableFactory, $this->storage, $this->account);
    $reflector = new \ReflectionClass($plugin);
    $property = $reflector->getProperty('conditionPluginManager');
    $property->setAccessible(TRUE);
    $property->setValue($plugin, $this->getMock('Drupal\Core\Executable\ExecutableManagerInterface'));

    $this->assertEquals(array(), $plugin->build());
  }

}

}

namespace {
  // @todo replace views_add_contextual_links()
  if (!function_exists('views_add_contextual_links')) {
    function views_add_contextual_links() {
    }
  }
}
