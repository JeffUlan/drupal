<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Plugin\Block\ViewsBlockTest.
 */

namespace Drupal\views\Tests\Plugin\Block {

use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\Block\ViewsBlock;
use Drupal\block\Plugin\views\display\Block;

// @todo Remove this once the constant got converted.
if (!defined('BLOCK_LABEL_VISIBLE')) {
  define('BLOCK_LABEL_VISIBLE', 'visible');
}
if (!defined('DRUPAL_NO_CACHE')) {
  define('DRUPAL_NO_CACHE', -1);
}

/**
 * Tests the views block plugin.
 *
 * @see \Drupal\views\Plugin\block\ViewsBlock
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
   * The view storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $storageController;

  public static function getInfo() {
    return array(
      'name' => ' Block: Views block',
      'description' => 'Tests the views block plugin.',
      'group' => 'Views module integration',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp(); // TODO: Change the autogenerated stub

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
      ->getMock();
    $this->executableFactory->staticExpects($this->any())
      ->method('get')
      ->with($this->view)
      ->will($this->returnValue($this->executable));

    $this->storageController = $this->getMockBuilder('Drupal\views\ViewStorageController')
      ->disableOriginalConstructor()
      ->getMock();

    $this->storageController->expects($this->any())
      ->method('load')
      ->with('test_view')
      ->will($this->returnValue($this->view));
  }

  /**
   * Tests the build method.
   *
   * @see \Drupal\views\Plugin\block\ViewsBlock::build()
   */
  public function testBuild() {
    $output = $this->randomName(100);
    $build = array('#markup' => $output);
    $this->executable->expects($this->once())
      ->method('executeDisplay')
      ->with($this->equalTo('block_1'))
      ->will($this->returnValue($build));

    $block_id = 'views_block:test_view-block_1';
    $config = array();
    $definition = array();
    $definition['module'] = 'views';
    $plugin = new ViewsBlock($config, $block_id, $definition, $this->executableFactory, $this->storageController);

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
    $definition['module'] = 'views';
    $plugin = new ViewsBlock($config, $block_id, $definition, $this->executableFactory, $this->storageController);

    $this->assertEquals(array(), $plugin->build());
  }

}

}

// @todo Remove this once https://drupal.org/node/2018411 is in.
namespace {
  if (!function_exists('t')) {
    function t($string) {
      return $string;
    }
  }
  // @todo replace views_add_contextual_links()
  if (!function_exists('views_add_contextual_links')) {
    function views_add_contextual_links() {
    }
  }
}
