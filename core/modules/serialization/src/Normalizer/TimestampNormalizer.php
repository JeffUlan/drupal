<?php

namespace Drupal\serialization\Normalizer;

use Drupal\Core\TypedData\Plugin\DataType\Timestamp;

/**
 * Converts values for the Timestamp data type to and from common formats.
 *
 * @internal
 *
 * Note that \Drupal\Core\TypedData\Plugin\DataType\Timestamp::getDateTime()
 * explicitly sets a default timezone of UTC. This ensures the string
 * representation generated by DateTimeNormalizer::normalize() is also in UTC.
 */
class TimestampNormalizer extends DateTimeNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $allowedFormats = [
    'UNIX timestamp' => 'U',
    'ISO 8601' => \DateTime::ISO8601,
    'RFC 3339' => \DateTime::RFC3339,
  ];

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = Timestamp::class;

  /**
   * {@inheritdoc}
   */
  protected function getNormalizationTimezone() {
    return new \DateTimeZone('UTC');
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []): mixed {
    $denormalized = parent::denormalize($data, $class, $format, $context);
    return $denormalized->getTimestamp();
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      Timestamp::class => TRUE,
    ];
  }

}
