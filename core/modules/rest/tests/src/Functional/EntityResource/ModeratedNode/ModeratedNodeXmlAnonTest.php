<?php

namespace Drupal\Tests\rest\Functional\EntityResource\ModeratedNode;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\XmlEntityNormalizationQuirksTrait;

/**
 * @group rest
 */
class ModeratedNodeXmlAnonTest extends ModeratedNodeResourceTestBase {

  use AnonResourceTestTrait;
  use XmlEntityNormalizationQuirksTrait;

  /**
   * {@inheritdoc}
   */
  protected static $format = 'xml';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'text/xml; charset=UTF-8';

  /**
   * {@inheritdoc}
   */
  public function testPatchPath() {
    // Deserialization of the XML format is not supported.
    $this->markTestSkipped();
  }

}
