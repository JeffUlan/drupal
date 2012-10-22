<?php

/**
 * @file
 * Definition of Views\comment\Plugin\views\argument\UserUid.
 */

namespace Views\comment\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Drupal\Core\Annotation\Plugin;

/**
 * Argument handler to accept a user id to check for nodes that
 * user posted or commented on.
 *
 * @ingroup views_argument_handlers
 *
 * @Plugin(
 *   id = "argument_comment_user_uid",
 *   module = "comment"
 * )
 */
class UserUid extends ArgumentPluginBase {

  function title() {
    if (!$this->argument) {
      $title = config('user.settings')->get('anonymous');
    }
    else {
      $query = db_select('users', 'u');
      $query->addField('u', 'name');
      $query->condition('u.uid', $this->argument);
      $title = $query->execute()->fetchField();
    }
    if (empty($title)) {
      return t('No user');
    }

    return check_plain($title);
  }

  function default_actions($which = NULL) {
    // Disallow summary views on this argument.
    if (!$which) {
      $actions = parent::default_actions();
      unset($actions['summary asc']);
      unset($actions['summary desc']);
      return $actions;
    }

    if ($which != 'summary asc' && $which != 'summary desc') {
      return parent::default_actions($which);
    }
  }

  public function query($group_by = FALSE) {
    $this->ensureMyTable();

    $subselect = db_select('comment', 'c');
    $subselect->addField('c', 'cid');
    $subselect->condition('c.uid', $this->argument);
    $subselect->where("c.nid = $this->tableAlias.nid");

    $condition = db_or()
      ->condition("$this->tableAlias.uid", $this->argument, '=')
      ->exists($subselect);

    $this->query->add_where(0, $condition);
  }

  function get_sort_name() {
    return t('Numerical', array(), array('context' => 'Sort order'));
  }

}
