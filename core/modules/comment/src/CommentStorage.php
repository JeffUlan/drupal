<?php

/**
 * @file
 * Definition of Drupal\comment\CommentStorage.
 */

namespace Drupal\comment;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\ContentEntityDatabaseStorage;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the controller class for comments.
 *
 * This extends the Drupal\Core\Entity\ContentEntityDatabaseStorage class,
 * adding required special handling for comment entities.
 */
class CommentStorage extends ContentEntityDatabaseStorage implements CommentStorageInterface {

  /**
   * The comment statistics service.
   *
   * @var \Drupal\comment\CommentStatisticsInterface
   */
  protected $statistics;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a CommentStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_info
   *   An array of entity info for the entity type.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\comment\CommentStatisticsInterface $comment_statistics
   *   The comment statistics service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityTypeInterface $entity_info, Connection $database, EntityManagerInterface $entity_manager, CommentStatisticsInterface $comment_statistics, AccountInterface $current_user) {
    parent::__construct($entity_info, $database, $entity_manager);
    $this->statistics = $comment_statistics;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_info) {
    return new static(
      $entity_info,
      $container->get('database'),
      $container->get('entity.manager'),
      $container->get('comment.statistics'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function updateEntityStatistics(CommentInterface $comment) {
    $this->statistics->update($comment);
  }

  /**
   * {@inheritdoc}
   */
  public function getMaxThread(EntityInterface $comment) {
    $query = $this->database->select('comment', 'c')
      ->condition('entity_id', $comment->getCommentedEntityId())
      ->condition('field_name', $comment->getFieldName())
      ->condition('entity_type', $comment->getCommentedEntityTypeId());
    $query->addExpression('MAX(thread)', 'thread');
    return $query->execute()
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function getMaxThreadPerThread(EntityInterface $comment) {
    $query = $this->database->select('comment', 'c')
      ->condition('entity_id', $comment->getCommentedEntityId())
      ->condition('field_name', $comment->getFieldName())
      ->condition('entity_type', $comment->getCommentedEntityTypeId())
      ->condition('thread', $comment->getParentComment()->getThread() . '.%', 'LIKE');
    $query->addExpression('MAX(thread)', 'thread');
    return $query->execute()
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayOrdinal(CommentInterface $comment, $comment_mode, $divisor = 1) {
    // Count how many comments (c1) are before $comment (c2) in display order.
    // This is the 0-based display ordinal.
    $query = $this->database->select('comment', 'c1');
    $query->innerJoin('comment', 'c2', 'c2.entity_id = c1.entity_id AND c2.entity_type = c1.entity_type AND c2.field_name = c1.field_name');
    $query->addExpression('COUNT(*)', 'count');
    $query->condition('c2.cid', $comment->id());
    if (!$this->currentUser->hasPermission('administer comments')) {
      $query->condition('c1.status', CommentInterface::PUBLISHED);
    }

    if ($comment_mode == CommentManagerInterface::COMMENT_MODE_FLAT) {
      // For rendering flat comments, cid is used for ordering comments due to
      // unpredictable behavior with timestamp, so we make the same assumption
      // here.
      $query->condition('c1.cid', $comment->id(), '<');
    }
    else {
      // For threaded comments, the c.thread column is used for ordering. We can
      // use the sorting code for comparison, but must remove the trailing
      // slash.
      $query->where('SUBSTRING(c1.thread, 1, (LENGTH(c1.thread) - 1)) < SUBSTRING(c2.thread, 1, (LENGTH(c2.thread) - 1))');
    }

    $ordinal = $query->execute()->fetchField();

    return ($divisor > 1) ? floor($ordinal / $divisor) : $ordinal;
  }

  /**
   * {@inheritdoc}
   */
  public function getChildCids(array $comments) {
    return $this->database->select('comment', 'c')
      ->fields('c', array('cid'))
      ->condition('pid', array_keys($comments))
      ->execute()
      ->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function getSchema() {
    $schema = parent::getSchema();

    // Marking the respective fields as NOT NULL makes the indexes more
    // performant.
    $schema['comment']['fields']['pid']['not null'] = TRUE;
    $schema['comment']['fields']['status']['not null'] = TRUE;
    $schema['comment']['fields']['entity_id']['not null'] = TRUE;
    $schema['comment']['fields']['created']['not null'] = TRUE;
    $schema['comment']['fields']['thread']['not null'] = TRUE;

    unset($schema['comment']['indexes']['field__pid']);
    unset($schema['comment']['indexes']['field__entity_id']);
    $schema['comment']['indexes'] += array(
      'comment__status_pid' => array('pid', 'status'),
      'comment__num_new' => array(
        'entity_id',
        array('entity_type', 32),
        'comment_type',
        'status',
        'created',
        'cid',
        'thread',
      ),
      'comment__entity_langcode' => array(
        'entity_id',
        array('entity_type', 32),
        'comment_type',
        'langcode',
      ),
      'comment__created' => array('created'),
    );
    $schema['comment']['foreign keys'] += array(
      'comment__author' => array(
        'table' => 'users',
        'columns' => array('uid' => 'uid'),
      ),
    );

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function getUnapprovedCount() {
    return  $this->database->select('comment', 'c')
      ->condition('status', CommentInterface::NOT_PUBLISHED, '=')
      ->countQuery()
      ->execute()
      ->fetchField();
  }

}
