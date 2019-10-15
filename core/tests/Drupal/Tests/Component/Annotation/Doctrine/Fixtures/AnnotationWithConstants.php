<?php
// @codingStandardsIgnoreFile

namespace Drupal\Tests\Component\Annotation\Doctrine\Fixtures;

/**
 * @Annotation
 * @Target("ALL")
 */
final class AnnotationWithConstants
{

    const INTEGER = 1;
    const FLOAT   = 1.2;
    const STRING  = '1.2.3';

    /**
     * @var mixed
     */
    public $value;
}
