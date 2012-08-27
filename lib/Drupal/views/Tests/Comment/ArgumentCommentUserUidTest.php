<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Comment\ArgumentCommentUserUidTest.
 */

namespace Drupal\views\Tests\Comment;

use Drupal\views\Tests\ViewsSqlTest;
use Drupal\views\View;

/**
 * Tests the argument_comment_user_uid handler.
 */
class ArgumentCommentUserUidTest extends ViewsSqlTest {
  protected $profile = 'standard';

  public static function getInfo() {
    return array(
      'name' => 'Tests handler argument_comment_user_uid',
      'description' => 'Tests the user posted or commented argument handler',
      'group' => 'Views Modules',
    );
  }

  /**
   * Post comment.
   *
   * @param $node
   *   Node to post comment on.
   * @param $comment
   *   Comment to save
   */
  function postComment($node, $comment = array()) {
    $comment += array(
      'uid' => $this->loggedInUser->uid,
      'nid' => $node->nid,
      'cid' => '',
      'pid' => '',
    );
    return entity_create('comment', $comment)->save();
  }

  function setUp() {
    parent::setUp();

    // Add two users, create a node with the user1 as author and another node with user2 as author.
    // For the second node add a comment from user1.
    $this->account = $this->drupalCreateUser();
    $this->account2 = $this->drupalCreateUser();
    $this->drupalLogin($this->account);
    $this->node_user_posted = $this->drupalCreateNode();
    $this->node_user_commented = $this->drupalCreateNode(array('uid' => $this->account2->uid));
    $this->postComment($this->node_user_commented);
  }

  function testCommentUserUidTest() {
    $view = $this->view_comment_user_uid();


    $this->executeView($view, array($this->account->uid));
    $resultset = array(
      array(
        'nid' => $this->node_user_posted->nid,
      ),
      array(
        'nid' => $this->node_user_commented->nid,
      ),
    );
    $this->column_map = array('nid' => 'nid');
    debug($view->result);
    $this->assertIdenticalResultset($view, $resultset, $this->column_map);
  }

  function view_comment_user_uid() {
    $view = new View(array(), 'view');
    $view->name = 'test_comment_user_uid';
    $view->description = '';
    $view->tag = 'default';
    $view->base_table = 'node';
    $view->human_name = 'test_comment_user_uid';
    $view->core = 7;
    $view->api_version = '3.0';
    $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

    /* Display: Master */
    $handler = $view->new_display('default', 'Master', 'default');
    $handler->display->display_options['access']['type'] = 'perm';
    $handler->display->display_options['cache']['type'] = 'none';
    $handler->display->display_options['query']['type'] = 'views_query';
    $handler->display->display_options['query']['options']['query_comment'] = FALSE;
    $handler->display->display_options['exposed_form']['type'] = 'basic';
    $handler->display->display_options['pager']['type'] = 'full';
    $handler->display->display_options['style_plugin'] = 'default';
    $handler->display->display_options['row_plugin'] = 'node';
    /* Field: Content: nid */
    $handler->display->display_options['fields']['nid']['id'] = 'nid';
    $handler->display->display_options['fields']['nid']['table'] = 'node';
    $handler->display->display_options['fields']['nid']['field'] = 'nid';
    /* Contextual filter: Content: User posted or commented */
    $handler->display->display_options['arguments']['uid_touch']['id'] = 'uid_touch';
    $handler->display->display_options['arguments']['uid_touch']['table'] = 'node';
    $handler->display->display_options['arguments']['uid_touch']['field'] = 'uid_touch';
    $handler->display->display_options['arguments']['uid_touch']['default_argument_type'] = 'fixed';
    $handler->display->display_options['arguments']['uid_touch']['default_argument_skip_url'] = 0;
    $handler->display->display_options['arguments']['uid_touch']['summary']['number_of_records'] = '0';
    $handler->display->display_options['arguments']['uid_touch']['summary']['format'] = 'default_summary';
    $handler->display->display_options['arguments']['uid_touch']['summary_options']['items_per_page'] = '25';

    return $view;
  }
}
