<?php

/**
 * @file
 * Definition of Drupal\taxonomy\TermStorage.
 */

namespace Drupal\taxonomy;

use Drupal\Core\Entity\ContentEntityDatabaseStorage;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Defines a Controller class for taxonomy terms.
 */
class TermStorage extends ContentEntityDatabaseStorage implements TermStorageInterface {

  /**
   * {@inheritdoc}
   *
   * @param array $values
   *   An array of values to set, keyed by property name. A value for the
   *   vocabulary ID ('vid') is required.
   */
  public function create(array $values = array()) {
    // Save new terms with no parents by default.
    if (empty($values['parent'])) {
      $values['parent'] = array(0);
    }
    $entity = parent::create($values);
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildPropertyQuery(QueryInterface $entity_query, array $values) {
    if (isset($values['name'])) {
      $entity_query->condition('name', $values['name'], 'LIKE');
      unset($values['name']);
    }
    parent::buildPropertyQuery($entity_query, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $ids = NULL) {
    drupal_static_reset('taxonomy_term_count_nodes');
    drupal_static_reset('taxonomy_get_tree');
    drupal_static_reset('taxonomy_get_tree:parents');
    drupal_static_reset('taxonomy_get_tree:terms');
    drupal_static_reset('taxonomy_term_load_parents');
    drupal_static_reset('taxonomy_term_load_parents_all');
    drupal_static_reset('taxonomy_term_load_children');
    parent::resetCache($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteTermHierarchy($tids) {
    $this->database->delete('taxonomy_term_hierarchy')
      ->condition('tid', $tids)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function updateTermHierarchy(EntityInterface $term) {
    $query = $this->database->insert('taxonomy_term_hierarchy')
      ->fields(array('tid', 'parent'));

    foreach ($term->parent as $parent) {
      $query->values(array(
        'tid' => $term->id(),
        'parent' => (int) $parent->value,
      ));
    }
    $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function loadParents($tid) {
    $query = $this->database->select('taxonomy_term_field_data', 't');
    $query->join('taxonomy_term_hierarchy', 'h', 'h.parent = t.tid');
    $query->addField('t', 'tid');
    $query->condition('h.tid', $tid);
    $query->condition('t.default_langcode', 1);
    $query->addTag('term_access');
    $query->orderBy('t.weight');
    $query->orderBy('t.name');
    return $query->execute()->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function loadChildren($tid, $vid = NULL) {
    $query = $this->database->select('taxonomy_term_field_data', 't');
    $query->join('taxonomy_term_hierarchy', 'h', 'h.tid = t.tid');
    $query->addField('t', 'tid');
    $query->condition('h.parent', $tid);
    if ($vid) {
      $query->condition('t.vid', $vid);
    }
    $query->condition('t.default_langcode', 1);
    $query->addTag('term_access');
    $query->orderBy('t.weight');
    $query->orderBy('t.name');
    return $query->execute()->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function loadTree($vid) {
    $query = $this->database->select('taxonomy_term_field_data', 't');
    $query->join('taxonomy_term_hierarchy', 'h', 'h.tid = t.tid');
    return $query
      ->addTag('term_access')
      ->fields('t')
      ->fields('h', array('parent'))
      ->condition('t.vid', $vid)
      ->condition('t.default_langcode', 1)
      ->orderBy('t.weight')
      ->orderBy('t.name')
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function nodeCount($vid) {
    $query = $this->database->select('taxonomy_index', 'ti');
    $query->addExpression('COUNT(DISTINCT ti.nid)');
    $query->leftJoin('taxonomy_term_data', 'td', 'ti.tid = td.tid');
    $query->condition('td.vid', $vid);
    $query->addTag('vocabulary_node_count');
    return $query->execute()->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function resetWeights($vid) {
    $this->database->update('taxonomy_term_field_data')
      ->fields(array('weight' => 0))
      ->condition('vid', $vid)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getNodeTerms($nids, $vocabs = array(), $langcode = NULL) {
    $query = db_select('taxonomy_term_field_data', 'td');
    $query->innerJoin('taxonomy_index', 'tn', 'td.tid = tn.tid');
    $query->fields('td', array('tid'));
    $query->addField('tn', 'nid', 'node_nid');
    $query->orderby('td.weight');
    $query->orderby('td.name');
    $query->condition('tn.nid', $nids);
    $query->addTag('term_access');
    if (!empty($vocabs)) {
      $query->condition('td.vid', $vocabs);
    }
    if (!empty($langcode)) {
      $query->condition('td.langcode', $langcode);
    }

    $results = array();
    foreach ($query->execute() as $term_record) {
      $results[$term_record->node_nid][] = $term_record->tid;
      $all_tids[] = $term_record->tid;
    }

    $all_terms = $this->loadMultiple($all_tids);
    $terms = array();
    foreach ($results as $nid => $tids) {
      foreach ($tids as $tid) {
        $terms[$nid][$tid] = $all_terms[$term_record->tid];
      }
    }
    return $terms;
  }

}
