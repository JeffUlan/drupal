<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Wizard\ItemsPerPageTest.
 */

namespace Drupal\views\Tests\Wizard;

/**
 * Tests the ability of the views wizard to specify the number of items per page.
 */
class ItemsPerPageTest extends WizardTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Items per page functionality',
      'description' => 'Test the ability of the views wizard to specify the number of items per page.',
      'group' => 'Views Wizard',
    );
  }

  /**
   * Tests the number of items per page.
   */
  function testItemsPerPage() {
    $this->drupalCreateContentType(array('type' => 'article'));

    // Create articles, each with a different creation time so that we can do a
    // meaningful sort.
    $node1 = $this->drupalCreateNode(array('type' => 'article', 'created' => REQUEST_TIME));
    $node2 = $this->drupalCreateNode(array('type' => 'article', 'created' => REQUEST_TIME + 1));
    $node3 = $this->drupalCreateNode(array('type' => 'article', 'created' => REQUEST_TIME + 2));
    $node4 = $this->drupalCreateNode(array('type' => 'article', 'created' => REQUEST_TIME + 3));
    $node5 = $this->drupalCreateNode(array('type' => 'article', 'created' => REQUEST_TIME + 4));

    // Create a page. This should never appear in the view created below.
    $page_node = $this->drupalCreateNode(array('type' => 'page', 'created' => REQUEST_TIME + 2));

    // Create a view that sorts newest first, and shows 4 items in the page and
    // 3 in the block.
    $view = array();
    $view['human_name'] = $this->randomName(16);
    $view['name'] = strtolower($this->randomName(16));
    $view['description'] = $this->randomName(16);
    $view['show[wizard_key]'] = 'node';
    $view['show[type]'] = 'article';
    $view['show[sort]'] = 'created:DESC';
    $view['page[create]'] = 1;
    $view['page[title]'] = $this->randomName(16);
    $view['page[path]'] = $this->randomName(16);
    $view['page[items_per_page]'] = 4;
    $view['block[create]'] = 1;
    $view['block[title]'] = $this->randomName(16);
    $view['block[items_per_page]'] = 3;
    $this->drupalPost('admin/structure/views/add', $view, t('Save & exit'));

    // Make sure the page display shows the nodes we expect, and that they
    // appear in the expected order.
    $this->assertUrl($view['page[path]']);
    $this->assertText($view['page[title]']);
    $content = $this->drupalGetContent();
    $this->assertText($node5->label());
    $this->assertText($node4->label());
    $this->assertText($node3->label());
    $this->assertText($node2->label());
    $this->assertNoText($node1->label());
    $this->assertNoText($page_node->label());
    $pos5 = strpos($content, $node5->label());
    $pos4 = strpos($content, $node4->label());
    $pos3 = strpos($content, $node3->label());
    $pos2 = strpos($content, $node2->label());
    $this->assertTrue($pos5 < $pos4 && $pos4 < $pos3 && $pos3 < $pos2, t('The nodes appear in the expected order in the page display.'));

    // Put the block into the first sidebar region, visit a page that displays
    // the block, and check that the nodes we expect appear in the correct
    // order.
    $this->drupalGet('admin/structure/block');
    $this->assertText('View: ' . $view['human_name']);
    $edit = array();
    $edit["blocks[views_{$view['name']}-block][region]"] = 'sidebar_first';
    $this->drupalPost('admin/structure/block', $edit, t('Save blocks'));
    $this->drupalGet('user');
    $content = $this->drupalGetContent();
    $this->assertText($node5->label());
    $this->assertText($node4->label());
    $this->assertText($node3->label());
    $this->assertNoText($node2->label());
    $this->assertNoText($node1->label());
    $this->assertNoText($page_node->label());
    $pos5 = strpos($content, $node5->label());
    $pos4 = strpos($content, $node4->label());
    $pos3 = strpos($content, $node3->label());
    $this->assertTrue($pos5 < $pos4 && $pos4 < $pos3, t('The nodes appear in the expected order in the block display.'));
  }

}
