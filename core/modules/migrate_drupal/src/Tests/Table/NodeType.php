<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\NodeType.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see cores/scripts/dump-database-d6.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table;

use Drupal\migrate_drupal\Tests\Dump\Drupal6DumpBase;

/**
 * Generated file to represent the node_type table.
 */
class NodeType extends Drupal6DumpBase {

  public function load() {
    $this->createTable("node_type", array(
      'primary key' => array(
        'type',
      ),
      'fields' => array(
        'type' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '32',
        ),
        'name' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'module' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
        ),
        'description' => array(
          'type' => 'text',
          'not null' => TRUE,
          'length' => 100,
        ),
        'help' => array(
          'type' => 'text',
          'not null' => TRUE,
          'length' => 100,
        ),
        'has_title' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '3',
          'unsigned' => TRUE,
        ),
        'title_label' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'has_body' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '3',
          'unsigned' => TRUE,
        ),
        'body_label' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'min_word_count' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '5',
          'unsigned' => TRUE,
        ),
        'custom' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '4',
          'default' => '0',
        ),
        'modified' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '4',
          'default' => '0',
        ),
        'locked' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '4',
          'default' => '0',
        ),
        'orig_type' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
      ),
    ));
    $this->database->insert("node_type")->fields(array(
      'type',
      'name',
      'module',
      'description',
      'help',
      'has_title',
      'title_label',
      'has_body',
      'body_label',
      'min_word_count',
      'custom',
      'modified',
      'locked',
      'orig_type',
    ))
    ->values(array(
      'type' => 'article',
      'name' => 'Article',
      'module' => 'node',
      'description' => 'An <em>article</em>, content type.',
      'help' => '',
      'has_title' => '1',
      'title_label' => 'Title',
      'has_body' => '1',
      'body_label' => 'Body',
      'min_word_count' => '0',
      'custom' => '1',
      'modified' => '1',
      'locked' => '0',
      'orig_type' => 'story',
    ))->values(array(
      'type' => 'company',
      'name' => 'Company',
      'module' => 'node',
      'description' => 'Company node type',
      'help' => '',
      'has_title' => '1',
      'title_label' => 'Name',
      'has_body' => '1',
      'body_label' => 'Description',
      'min_word_count' => '20',
      'custom' => '0',
      'modified' => '0',
      'locked' => '0',
      'orig_type' => 'company',
    ))->values(array(
      'type' => 'employee',
      'name' => 'Employee',
      'module' => 'node',
      'description' => 'Employee node type',
      'help' => '',
      'has_title' => '1',
      'title_label' => 'Name',
      'has_body' => '1',
      'body_label' => 'Bio',
      'min_word_count' => '20',
      'custom' => '0',
      'modified' => '0',
      'locked' => '0',
      'orig_type' => 'employee',
    ))->values(array(
      'type' => 'event',
      'name' => 'Event',
      'module' => 'node',
      'description' => 'Events have a start date and an optional end date as well as a teaser and a body. They can be extended by other modules, too.',
      'help' => '',
      'has_title' => '1',
      'title_label' => 'Title',
      'has_body' => '1',
      'body_label' => 'Body',
      'min_word_count' => '0',
      'custom' => '1',
      'modified' => '1',
      'locked' => '0',
      'orig_type' => 'event',
    ))->values(array(
      'type' => 'page',
      'name' => 'Page',
      'module' => 'node',
      'description' => "A <em>page</em>, similar in form to a <em>story</em>, is a simple method for creating and displaying information that rarely changes, such as an \"About us\" section of a website. By default, a <em>page</em> entry does not allow visitor comments and is not featured on the site's initial home page.",
      'help' => '',
      'has_title' => '1',
      'title_label' => 'Title',
      'has_body' => '1',
      'body_label' => 'Body',
      'min_word_count' => '0',
      'custom' => '1',
      'modified' => '1',
      'locked' => '0',
      'orig_type' => 'page',
    ))->values(array(
      'type' => 'sponsor',
      'name' => 'Sponsor',
      'module' => 'node',
      'description' => 'Sponsor node type',
      'help' => '',
      'has_title' => '1',
      'title_label' => 'Name',
      'has_body' => '0',
      'body_label' => 'Body',
      'min_word_count' => '0',
      'custom' => '0',
      'modified' => '0',
      'locked' => '0',
      'orig_type' => '',
    ))->values(array(
      'type' => 'story',
      'name' => 'Story',
      'module' => 'node',
      'description' => "A <em>story</em>, similar in form to a <em>page</em>, is ideal for creating and displaying content that informs or engages website visitors. Press releases, site announcements, and informal blog-like entries may all be created with a <em>story</em> entry. By default, a <em>story</em> entry is automatically featured on the site's initial home page, and provides the ability to post comments.",
      'help' => '',
      'has_title' => '1',
      'title_label' => 'Title',
      'has_body' => '1',
      'body_label' => 'Body',
      'min_word_count' => '0',
      'custom' => '1',
      'modified' => '1',
      'locked' => '0',
      'orig_type' => 'story',
    ))->values(array(
      'type' => 'test_event',
      'name' => 'Migrate test event',
      'module' => 'node',
      'description' => 'test event description here',
      'help' => 'help text here',
      'has_title' => '1',
      'title_label' => 'Event Name',
      'has_body' => '1',
      'body_label' => 'Body',
      'min_word_count' => '0',
      'custom' => '1',
      'modified' => '1',
      'locked' => '0',
      'orig_type' => 'event',
    ))->values(array(
      'type' => 'test_page',
      'name' => 'Migrate test page',
      'module' => 'node',
      'description' => "A <em>page</em>, similar in form to a <em>story</em>, is a simple method for creating and displaying information that rarely changes, such as an \"About us\" section of a website. By default, a <em>page</em> entry does not allow visitor comments and is not featured on the site's initial home page.",
      'help' => '',
      'has_title' => '1',
      'title_label' => 'Title',
      'has_body' => '1',
      'body_label' => 'This is the body field label',
      'min_word_count' => '0',
      'custom' => '1',
      'modified' => '1',
      'locked' => '0',
      'orig_type' => 'page',
    ))->values(array(
      'type' => 'test_planet',
      'name' => 'Migrate test planet',
      'module' => 'node',
      'description' => "A <em>story</em>, similar in form to a <em>page</em>, is ideal for creating and displaying content that informs or engages website visitors. Press releases, site announcements, and informal blog-like entries may all be created with a <em>story</em> entry. By default, a <em>story</em> entry is automatically featured on the site's initial home page, and provides the ability to post comments.",
      'help' => '',
      'has_title' => '1',
      'title_label' => 'Title',
      'has_body' => '0',
      'body_label' => 'Body',
      'min_word_count' => '0',
      'custom' => '1',
      'modified' => '1',
      'locked' => '0',
      'orig_type' => 'test_planet',
    ))->values(array(
      'type' => 'test_story',
      'name' => 'Migrate test story',
      'module' => 'node',
      'description' => "A <em>story</em>, similar in form to a <em>page</em>, is ideal for creating and displaying content that informs or engages website visitors. Press releases, site announcements, and informal blog-like entries may all be created with a <em>story</em> entry. By default, a <em>story</em> entry is automatically featured on the site's initial home page, and provides the ability to post comments.",
      'help' => '',
      'has_title' => '1',
      'title_label' => 'Title',
      'has_body' => '0',
      'body_label' => 'Body',
      'min_word_count' => '0',
      'custom' => '1',
      'modified' => '1',
      'locked' => '0',
      'orig_type' => 'test_story',
    ))->execute();
  }

}
