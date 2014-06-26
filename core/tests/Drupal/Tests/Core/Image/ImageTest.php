<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Image\ImageTest.
 */

namespace Drupal\Tests\Core\Image;

use Drupal\Core\Image\Image;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the image class.
 */
class ImageTest extends UnitTestCase {

  /**
   * Path to the image file.
   *
   * @var string
   */
  protected $source;

  /**
   * Image object.
   *
   * @var \Drupal\Core\Image\Image
   */
  protected $image;

  /**
   * Image toolkit.
   *
   * @var \Drupal\Core\ImageToolkit\ImageToolkitInterface
   */
  protected $toolkit;

  public static function getInfo() {
    return array(
      'name' => 'Image class functionality',
      'description' => 'Tests the Image class.',
      'group' => 'Image',
    );
  }

  protected function setUp() {
    // Use the Druplicon image.
    $this->source = __DIR__ . '/../../../../../misc/druplicon.png';
    $this->toolkit = $this->getToolkitMock();

    $this->toolkit->expects($this->any())
      ->method('getPluginId')
      ->will($this->returnValue('gd'));

    $this->image = new Image($this->toolkit, $this->source);
  }

  /**
   * Mocks a toolkit.
   *
   * @param array $stubs
   *   (optional) Array containing methods to be replaced with stubs.
   *
   * @return \PHPUnit_Framework_MockObject_MockObject
   */
  protected function getToolkitMock(array $stubs = array()) {
    $mock_builder = $this->getMockBuilder('Drupal\system\Plugin\ImageToolkit\GDToolkit');
    $stubs += array('getPluginId', 'save');
    return $mock_builder
      ->disableOriginalConstructor()
      ->setMethods($stubs)
      ->getMock();
  }

  /**
   * Tests \Drupal\Core\Image\Image::getHeight().
   */
  public function testGetHeight() {
    $this->assertEquals($this->image->getHeight(), 100);
  }

  /**
   * Tests \Drupal\Core\Image\Image::getWidth().
   */
  public function testGetWidth() {
    $this->assertEquals($this->image->getWidth(), 88);
  }

  /**
   * Tests \Drupal\Core\Image\Image::getFileSize
   */
  public function testGetFileSize() {
    $this->assertEquals($this->image->getFileSize(), 3905);
  }

  /**
   * Tests \Drupal\Core\Image\Image::getToolkit()->getType().
   */
  public function testGetType() {
    $this->assertEquals($this->image->getToolkit()->getType(), IMAGETYPE_PNG);
  }

  /**
   * Tests \Drupal\Core\Image\Image::getMimeType().
   */
  public function testGetMimeType() {
    $this->assertEquals($this->image->getMimeType(), 'image/png');
  }

  /**
   * Tests \Drupal\Core\Image\Image::isValid().
   */
  public function testIsValid() {
    $this->assertTrue($this->image->isValid());
    $this->assertTrue(is_readable($this->image->getSource()));
  }

  /**
   * Tests \Drupal\Core\Image\Image::getToolkitId().
   */
  public function testGetToolkitId() {
    $this->assertEquals($this->image->getToolkitId(), 'gd');
  }

  /**
   * Tests \Drupal\Core\Image\Image::save().
   */
  public function testSave() {
    // This will fail if save() method isn't called on the toolkit.
    $toolkit = $this->getToolkitMock();
    $toolkit->expects($this->once())
      ->method('save')
      ->will($this->returnValue(TRUE));

    $image = $this->getMock('Drupal\Core\Image\Image', array('chmod'), array($toolkit, $this->image->getSource()));
    $image->expects($this->any())
      ->method('chmod')
      ->will($this->returnValue(TRUE));

    $image->save();
  }

  /**
   * Tests \Drupal\Core\Image\Image::save().
   */
  public function testSaveFails() {
    // This will fail if save() method isn't called on the toolkit.
    $this->toolkit->expects($this->once())
      ->method('save')
      ->will($this->returnValue(FALSE));

    $this->assertFalse($this->image->save());
  }

  /**
   * Tests \Drupal\Core\Image\Image::save().
   */
  public function testChmodFails() {
    // This will fail if save() method isn't called on the toolkit.
    $toolkit = $this->getToolkitMock();
    $toolkit->expects($this->once())
      ->method('save')
      ->will($this->returnValue(TRUE));

    $image = $this->getMock('Drupal\Core\Image\Image', array('chmod'), array($toolkit, $this->image->getSource()));
    $image->expects($this->any())
      ->method('chmod')
      ->will($this->returnValue(FALSE));

    $this->assertFalse($image->save());
  }

  /**
   * Tests \Drupal\Core\Image\Image::parseFile().
   */
  public function testParseFileFails() {
    $toolkit = $this->getToolkitMock();
    $image = new Image($toolkit, 'magic-foobars.png');

    $this->assertFalse($image->isValid());
    $this->assertFalse($image->save());
  }

  /**
   * Tests \Drupal\Core\Image\Image::scale().
   */
  public function testScaleWidth() {
    $toolkit = $this->getToolkitMock(array('resize'));
    $image = new Image($toolkit, $this->source);

    $toolkit->expects($this->any())
      ->method('resize')
      ->will($this->returnArgument(1));
    $height = $image->scale(44);
    $this->assertEquals($height, 50);
  }

  /**
   * Tests \Drupal\Core\Image\Image::scale().
   */
  public function testScaleHeight() {
    $toolkit = $this->getToolkitMock(array('resize'));
    $image = new Image($toolkit, $this->source);

    $toolkit->expects($this->any())
      ->method('resize')
      ->will($this->returnArgument(0));
    $width = $image->scale(NULL, 50);
    $this->assertEquals($width, 44);
  }

  /**
   * Tests \Drupal\Core\Image\Image::scale().
   */
  public function testScaleSame() {
    $toolkit = $this->getToolkitMock(array('resize'));
    $image = new Image($toolkit, $this->source);

    // Dimensions are the same, resize should not be called.
    $toolkit->expects($this->never())
      ->method('resize')
      ->will($this->returnArgument(0));

    $width = $image->scale(88, 100);
    $this->assertEquals($width, 88);
  }

  /**
   * Tests \Drupal\Core\Image\Image::scaleAndCrop().
   */
  public function testScaleAndCropWidth() {
    $toolkit = $this->getToolkitMock(array('resize', 'crop'));
    $image = new Image($toolkit, $this->source);

    $toolkit->expects($this->once())
      ->method('resize')
      ->will($this->returnValue(TRUE));

    $toolkit->expects($this->once())
      ->method('crop')
      ->will($this->returnArgument(0));

    $x = $image->scaleAndCrop(34, 50);
    $this->assertEquals($x, 5);
  }

  /**
   * Tests \Drupal\Core\Image\Image::scaleAndCrop().
   */
  public function testScaleAndCropHeight() {
    $toolkit = $this->getToolkitMock(array('resize', 'crop'));
    $image = new Image($toolkit, $this->source);

    $toolkit->expects($this->once())
      ->method('resize')
      ->will($this->returnValue(TRUE));

    $toolkit->expects($this->once())
      ->method('crop')
      ->will($this->returnArgument(1));

    $y = $image->scaleAndCrop(44, 40);
    $this->assertEquals($y, 5);
  }

  /**
   * Tests \Drupal\Core\Image\Image::scaleAndCrop().
   */
  public function testScaleAndCropFails() {
    $toolkit = $this->getToolkitMock(array('resize', 'crop'));
    $image = new Image($toolkit, $this->source);

    $toolkit->expects($this->once())
      ->method('resize')
      ->will($this->returnValue(FALSE));

    $toolkit->expects($this->never())
      ->method('crop');
    $image->scaleAndCrop(44, 40);
  }

  /**
   * Tests \Drupal\Core\Image\Image::crop().
   *
   * @todo Because \Drupal\Tests\Core\Image\ImageTest::testCropWidth() tests
   *   image geometry conversions (like dimensions, coordinates, etc) and has
   *   lost its scope in https://drupal.org/node/2103635, it was temporarily
   *   removed. The test will be added back when implementing the dedicated
   *   functionality from https://drupal.org/node/2108307.
   */

  /**
   * Tests \Drupal\Core\Image\Image::crop().
   *
   * @todo Because \Drupal\Tests\Core\Image\ImageTest::testCropHeight() tests
   *   image geometry conversions (like dimensions, coordinates, etc) and has
   *   lost its scope in https://drupal.org/node/2103635, it was temporarily
   *   removed. The test will be added back when implementing the dedicated
   *   functionality from https://drupal.org/node/2108307.
   */

  /**
   * Tests \Drupal\Core\Image\Image::crop().
   */
  public function testCrop() {
    $toolkit = $this->getToolkitMock(array('crop'));
    $image = new Image($toolkit, $this->source);

    $toolkit->expects($this->once())
      ->method('crop')
      ->will($this->returnArgument(2));
    $width = $image->crop(0, 0, 44, 50);
    $this->assertEquals($width, 44);
  }

  /**
   * Tests \Drupal\Core\Image\Image::resize().
   *
   * @todo Because \Drupal\Tests\Core\Image\ImageTest::testResize() tests image
   *   geometry conversions (like dimensions, coordinates, etc) and has lost its
   *   scope in https://drupal.org/node/2103635, it was temporarily removed. The
   *   test will be added back when implementing the dedicated functionality
   *   from https://drupal.org/node/2108307.
   */

  /**
   * Tests \Drupal\Core\Image\Image::desaturate().
   */
  public function testDesaturate() {
    $toolkit = $this->getToolkitMock(array('desaturate'));
    $image = new Image($toolkit, $this->source);

    $toolkit->expects($this->once())
      ->method('desaturate');
    $image->desaturate();
  }

  /**
   * Tests \Drupal\Core\Image\Image::rotate().
   */
  public function testRotate() {
    $toolkit = $this->getToolkitMock(array('rotate'));
    $image = new Image($toolkit, $this->source);

    $toolkit->expects($this->once())
      ->method('rotate');
    $image->rotate(90);
  }

}
