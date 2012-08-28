<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Handler\ArgumentStringTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\views\View;

/**
 * Tests the core Drupal\views\Plugin\views\argument\String handler.
 */
class ArgumentStringTest extends HandlerTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Argument: String',
      'description' => 'Test the core Drupal\views\Plugin\views\argument\String handler.',
      'group' => 'Views Handlers',
    );
  }

  /**
   * Tests the glossary feature.
   */
  function testGlossary() {
    // Setup some nodes, one with a, two with b and three with c.
    $counter = 1;
    foreach (array('a', 'b', 'c') as $char) {
      for ($i = 0; $i < $counter; $i++) {
        $edit = array(
          'title' => $char . $this->randomName(),
        );
        $this->drupalCreateNode($edit);
      }
    }

    $view = $this->viewGlossary();
    $view->init_display();
    $this->executeView($view);

    $count_field = 'nid';
    foreach ($view->result as &$row) {
      if (strpos($row->node_title, 'a') === 0) {
        $this->assertEqual(1, $row->{$count_field});
      }
      if (strpos($row->node_title, 'b') === 0) {
        $this->assertEqual(2, $row->{$count_field});
      }
      if (strpos($row->node_title, 'c') === 0) {
        $this->assertEqual(3, $row->{$count_field});
      }
    }
  }

  /**
   * Provide a test view for testGlossary.
   *
   * @see testGlossary
   * @return Drupal\views\View
   */
  function viewGlossary() {
    $view = new View(array(), 'view');
    $view->name = 'test_glossary';
    $view->description = '';
    $view->tag = 'default';
    $view->base_table = 'node';
    $view->human_name = 'test_glossary';
    $view->core = 7;
    $view->api_version = '3.0';
    $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

    /* Display: Master */
    $handler = $view->new_display('default', 'Master', 'default');
    $handler->display->display_options['access']['type'] = 'perm';
    $handler->display->display_options['cache']['type'] = 'none';
    $handler->display->display_options['query']['type'] = 'views_query';
    $handler->display->display_options['exposed_form']['type'] = 'basic';
    $handler->display->display_options['pager']['type'] = 'full';
    $handler->display->display_options['style_plugin'] = 'default';
    $handler->display->display_options['row_plugin'] = 'fields';
    /* Field: Content: Title */
    $handler->display->display_options['fields']['title']['id'] = 'title';
    $handler->display->display_options['fields']['title']['table'] = 'node';
    $handler->display->display_options['fields']['title']['field'] = 'title';
    $handler->display->display_options['fields']['title']['label'] = '';
    /* Contextual filter: Content: Title */
    $handler->display->display_options['arguments']['title']['id'] = 'title';
    $handler->display->display_options['arguments']['title']['table'] = 'node';
    $handler->display->display_options['arguments']['title']['field'] = 'title';
    $handler->display->display_options['arguments']['title']['default_argument_type'] = 'fixed';
    $handler->display->display_options['arguments']['title']['summary']['number_of_records'] = '0';
    $handler->display->display_options['arguments']['title']['summary']['format'] = 'default_summary';
    $handler->display->display_options['arguments']['title']['summary_options']['items_per_page'] = '25';
    $handler->display->display_options['arguments']['title']['glossary'] = TRUE;
    $handler->display->display_options['arguments']['title']['limit'] = '1';

    return $view;
  }
}
