<?php

/**
 * @file
 * Definition of Drupal\views\Tests\UpgradeTestCase.
 */

namespace Drupal\views\Tests;

/**
 * Tests the upgrade path of all conversions.
 *
 * You can find all conversions by searching for "moved to".
 */
class UpgradeTestCase extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * To import a view the user needs use PHP for settings rights, so enable php
   * module.
   *
   * @var array
   */
  public static $modules = array('views_ui', 'block', 'php');

  public static function getInfo() {
    return array(
      'name' => 'Upgrade path',
      'description' => 'Tests the upgrade path of modules which were changed.',
      'group' => 'Views',
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();
  }

  function viewsData() {
    $data = parent::viewsData();
    $data['views_test']['old_field_1']['moved to'] = array('views_test', 'id');
    $data['views_test']['old_field_2']['field']['moved to'] = array('views_test', 'name');
    $data['views_test']['old_field_3']['filter']['moved to'] = array('views_test', 'age');

    // @todo Test this scenario, too.
    $data['views_old_table_2']['old_field']['moved to'] = array('views_test', 'job');

    $data['views_old_table']['moved to'] = 'views_test';

    return $data;
  }

  function debugField($field) {
    $keys = array('id', 'table', 'field', 'actualField', 'original_field', 'realField');
    $info = array();
    foreach ($keys as $key) {
      $info[$key] = $field->{$key};
    }
    debug($info, NULL, TRUE);
  }

  /**
   * Tests the moved to parameter in general.
   */
  public function testMovedTo() {
    // Test moving on field lavel.
    $view = $this->createViewFromConfig('test_views_move_to_field');
    $view->update();
    $view->build();

//     $this->assertEqual('old_field_1', $view->field['old_field_1']->options['id'], "Id shouldn't change during conversion");
//     $this->assertEqual('id', $view->field['old_field_1']->field, 'The field should change during conversion');
    $this->assertEqual('id', $view->field['old_field_1']->realField);
    $this->assertEqual('views_test', $view->field['old_field_1']->table);
    $this->assertEqual('old_field_1', $view->field['old_field_1']->original_field, 'The field should have stored the original_field');

    // Test moving on handler lavel.
    $view = $this->createViewFromConfig('test_views_move_to_handler');
    $view->update();
    $view->build();

//     $this->assertEqual('old_field_2', $view->field['old_field_2']->options['id']);
    $this->assertEqual('name', $view->field['old_field_2']->realField);
    $this->assertEqual('views_test', $view->field['old_field_2']->table);

//     $this->assertEqual('old_field_3', $view->filter['old_field_3']->options['id']);
    $this->assertEqual('age', $view->filter['old_field_3']->realField);
    $this->assertEqual('views_test', $view->filter['old_field_3']->table);

    // Test moving on table level.
    $view = $this->createViewFromConfig('test_views_move_to_table');
    $view->update();
    $view->build();

    $this->assertEqual('views_test', $view->base_table, 'Make sure that view->base_table gets automatically converted.');
//     $this->assertEqual('id', $view->field['id']->field, 'If we move a whole table fields of this table should work, too.');
    $this->assertEqual('id', $view->field['id']->realField, 'To run the query right the realField has to be set right.');
    $this->assertEqual('views_test', $view->field['id']->table);
  }

  /**
   * Tests a import via ui.
   *
   * To ensure the general functionality, the recent comments view from drupal6
   * is used.
   */
  public function testUpgradeImport() {
    $admin_user = $this->drupalCreateUser(array('administer views', 'administer site configuration', 'use PHP for settings'));
    $this->drupalLogin($admin_user);
    $edit = array(
      'view' => $this->viewUpgradeImport(),
    );
    $this->drupalPost('admin/structure/views/import', $edit, t('Import'));

    $this->assertText('Recent comments');
  }

  /**
   * @todo When we know if we are having import, we can either remove or
   * update this.
   */
  protected function viewUpgradeImport() {
    $import = '
      $view = new Drupal\views\View(array(), "view");
      $view->name = "comments_recent";
      $view->description = "Contains a block and a page to list recent comments; the block will automatically link to the page, which displays the comment body as well as a link to the node.";
      $view->tag = "default";
      $view->base_table = "comments";
      $view->human_name = "";
      $view->core = 8;
      $view->api_version = "3.0";
      $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

      /* Display: Defaults */
      $handler = $view->newDisplay("default", "Defaults", "default");
      $handler->display->display_options["title"] = "Recent comments";
      $handler->display->display_options["use_more"] = TRUE;
      $handler->display->display_options["access"]["type"] = "none";
      $handler->display->display_options["cache"]["type"] = "none";
      $handler->display->display_options["query"]["type"] = "views_query";
      $handler->display->display_options["exposed_form"]["type"] = "basic";
      $handler->display->display_options["pager"]["type"] = "some";
      $handler->display->display_options["pager"]["options"]["items_per_page"] = 5;
      $handler->display->display_options["style_plugin"] = "html_list";
      $handler->display->display_options["row_plugin"] = "fields";
      /* Relationship: Comment: Node */
      $handler->display->display_options["relationships"]["nid"]["id"] = "nid";
      $handler->display->display_options["relationships"]["nid"]["table"] = "comments";
      $handler->display->display_options["relationships"]["nid"]["field"] = "nid";
      /* Field: Comment: Title */
      $handler->display->display_options["fields"]["subject"]["id"] = "subject";
      $handler->display->display_options["fields"]["subject"]["table"] = "comments";
      $handler->display->display_options["fields"]["subject"]["field"] = "subject";
      $handler->display->display_options["fields"]["subject"]["label"] = "";
      $handler->display->display_options["fields"]["subject"]["link_to_comment"] = 1;
      /* Field: Comment: Post date */
      $handler->display->display_options["fields"]["timestamp"]["id"] = "timestamp";
      $handler->display->display_options["fields"]["timestamp"]["table"] = "comments";
      $handler->display->display_options["fields"]["timestamp"]["field"] = "timestamp";
      $handler->display->display_options["fields"]["timestamp"]["label"] = "";
      $handler->display->display_options["fields"]["timestamp"]["date_format"] = "time ago";
      /* Sort criterion: Comment: Post date */
      $handler->display->display_options["sorts"]["timestamp"]["id"] = "timestamp";
      $handler->display->display_options["sorts"]["timestamp"]["table"] = "comments";
      $handler->display->display_options["sorts"]["timestamp"]["field"] = "timestamp";
      $handler->display->display_options["sorts"]["timestamp"]["order"] = "DESC";
      /* Filter: Node: Published or admin */
      $handler->display->display_options["filters"]["status_extra"]["id"] = "status_extra";
      $handler->display->display_options["filters"]["status_extra"]["table"] = "node";
      $handler->display->display_options["filters"]["status_extra"]["field"] = "status_extra";
      $handler->display->display_options["filters"]["status_extra"]["relationship"] = "nid";
      $handler->display->display_options["filters"]["status_extra"]["group"] = 0;
      $handler->display->display_options["filters"]["status_extra"]["expose"]["operator"] = FALSE;

      /* Display: Page */
      $handler = $view->newDisplay("page", "Page", "page");
      $handler->display->display_options["defaults"]["items_per_page"] = FALSE;
      $handler->display->display_options["defaults"]["style_plugin"] = FALSE;
      $handler->display->display_options["style_plugin"] = "html_list";
      $handler->display->display_options["defaults"]["style_options"] = FALSE;
      $handler->display->display_options["defaults"]["row_plugin"] = FALSE;
      $handler->display->display_options["row_plugin"] = "fields";
      $handler->display->display_options["row_options"]["inline"] = array(
        "title" => "title",
        "timestamp" => "timestamp",
      );
      $handler->display->display_options["row_options"]["separator"] = "&nbsp;";
      $handler->display->display_options["defaults"]["row_options"] = FALSE;
      $handler->display->display_options["defaults"]["fields"] = FALSE;
      /* Field: Node: Title */
      $handler->display->display_options["fields"]["title"]["id"] = "title";
      $handler->display->display_options["fields"]["title"]["table"] = "node";
      $handler->display->display_options["fields"]["title"]["field"] = "title";
      $handler->display->display_options["fields"]["title"]["relationship"] = "nid";
      $handler->display->display_options["fields"]["title"]["label"] = "Reply to";
      $handler->display->display_options["fields"]["title"]["link_to_node"] = 1;
      /* Field: Comment: Post date */
      $handler->display->display_options["fields"]["timestamp"]["id"] = "timestamp";
      $handler->display->display_options["fields"]["timestamp"]["table"] = "comments";
      $handler->display->display_options["fields"]["timestamp"]["field"] = "timestamp";
      $handler->display->display_options["fields"]["timestamp"]["label"] = "";
      $handler->display->display_options["fields"]["timestamp"]["date_format"] = "time ago";
      /* Field: Comment: Title */
      $handler->display->display_options["fields"]["subject"]["id"] = "subject";
      $handler->display->display_options["fields"]["subject"]["table"] = "comments";
      $handler->display->display_options["fields"]["subject"]["field"] = "subject";
      $handler->display->display_options["fields"]["subject"]["label"] = "";
      $handler->display->display_options["fields"]["subject"]["link_to_comment"] = 1;
      /* Field: Comment: Body */
      $handler->display->display_options["fields"]["comment"]["id"] = "comment";
      $handler->display->display_options["fields"]["comment"]["table"] = "comments";
      $handler->display->display_options["fields"]["comment"]["field"] = "comment";
      $handler->display->display_options["fields"]["comment"]["label"] = "";
      $handler->display->display_options["path"] = "comments/recent";

      /* Display: Block */
      $handler = $view->newDisplay("block", "Block", "block");
      $handler->display->display_options["block_description"] = "Recent comments view"
;';

      return $import;
  }

}
