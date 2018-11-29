<?php

namespace Drupal\layout_builder\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionListInterface;
use Drupal\layout_builder\SectionStorage\SectionStorageTrait;

/**
 * Defines a item list class for layout section fields.
 *
 * @internal
 *
 * @see \Drupal\layout_builder\Plugin\Field\FieldType\LayoutSectionItem
 */
class LayoutSectionItemList extends FieldItemList implements SectionListInterface {

  use SectionStorageTrait;

  /**
   * {@inheritdoc}
   */
  public function getSections() {
    $sections = [];
    /** @var \Drupal\layout_builder\Plugin\Field\FieldType\LayoutSectionItem $item */
    foreach ($this->list as $delta => $item) {
      $sections[$delta] = $item->section;
    }
    return $sections;
  }

  /**
   * {@inheritdoc}
   */
  protected function setSections(array $sections) {
    $this->list = [];
    $sections = array_values($sections);
    /** @var \Drupal\layout_builder\Plugin\Field\FieldType\LayoutSectionItem $item */
    foreach ($sections as $section) {
      $item = $this->appendItem();
      $item->section = $section;
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    $entity = parent::getEntity();

    // Ensure the entity is updated with the latest value.
    $entity->set($this->getName(), $this->getValue());
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function equals(FieldItemListInterface $list_to_compare) {
    if (!$list_to_compare instanceof LayoutSectionItemList) {
      return FALSE;
    }

    // Convert arrays of section objects to array values for comparison.
    $convert = function (LayoutSectionItemList $list) {
      return array_map(function (Section $section) {
        return $section->toArray();
      }, $list->getSections());
    };
    return $convert($this) === $convert($list_to_compare);
  }

}
