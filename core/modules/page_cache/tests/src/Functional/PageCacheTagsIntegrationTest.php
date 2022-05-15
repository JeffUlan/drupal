<?php

namespace Drupal\Tests\page_cache\Functional;

use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Language\LanguageInterface;
use Drupal\node\NodeInterface;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Enables the page cache and tests its cache tags in various scenarios.
 *
 * @group Cache
 * @see \Drupal\Tests\page_cache\Functional\PageCacheTest
 */
class PageCacheTagsIntegrationTest extends BrowserTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->enablePageCaching();
  }

  /**
   * Tests that cache tags are properly bubbled up to the page level.
   */
  public function testPageCacheTags() {
    // Create two nodes.
    $author_1 = $this->drupalCreateUser();
    $node_1 = $this->drupalCreateNode([
      'uid' => $author_1->id(),
      'title' => 'Node 1',
      'body' => [
        0 => ['value' => 'Body 1', 'format' => 'basic_html'],
      ],
      'promote' => NodeInterface::PROMOTED,
    ]);
    $author_2 = $this->drupalCreateUser();
    $node_2 = $this->drupalCreateNode([
      'uid' => $author_2->id(),
      'title' => 'Node 2',
      'body' => [
        0 => ['value' => 'Body 2', 'format' => 'full_html'],
      ],
      'promote' => NodeInterface::PROMOTED,
    ]);

    // Place a block, but only make it visible on full node page 2.
    $block = $this->drupalPlaceBlock('views_block:comments_recent-block_1', [
      'visibility' => [
        'request_path' => [
          'pages' => '/node/' . $node_2->id(),
        ],
      ],
    ]);

    $cache_contexts = [
      'languages:' . LanguageInterface::TYPE_INTERFACE,
      'route',
      'theme',
      'timezone',
      // The placed block is only visible on certain URLs through a visibility
      // condition.
      'url.path',
      'url.query_args:' . MainContentViewSubscriber::WRAPPER_FORMAT,
      // rel=canonical links and friends have absolute URLs as their values.
      'url.site',
      // These two cache contexts are added by BigPipe.
      'cookies:big_pipe_nojs',
      'session.exists',
      'user.permissions',
      'user.roles:authenticated',
    ];

    // Full node page 1.
    $this->assertPageCacheContextsAndTags($node_1->toUrl(), $cache_contexts, [
      'http_response',
      'rendered',
      'block_view',
      'local_task',
      'config:block_list',
      'config:block.block.olivero_site_branding',
      'config:block.block.olivero_breadcrumbs',
      'config:block.block.olivero_content',
      'config:block.block.olivero_help',
      'config:block.block.olivero_search_form_narrow',
      'config:block.block.olivero_search_form_wide',
      'config:block.block.' . $block->id(),
      'config:block.block.olivero_powered',
      'config:block.block.olivero_main_menu',
      'config:block.block.olivero_account_menu',
      'config:block.block.olivero_messages',
      'config:block.block.olivero_primary_local_tasks',
      'config:block.block.olivero_secondary_local_tasks',
      'config:block.block.olivero_syndicate',
      'config:block.block.olivero_primary_admin_actions',
      'config:block.block.olivero_page_title',
      'node_view',
      'node:' . $node_1->id(),
      'user:' . $author_1->id(),
      'config:filter.format.basic_html',
      'config:search.settings',
      'config:system.menu.account',
      'config:system.menu.main',
      'config:system.site',
      // FinishResponseSubscriber adds this cache tag to responses that have the
      // 'user.permissions' cache context for anonymous users.
      'config:user.role.anonymous',
    ]);

    // Render the view block adds the languages cache context.
    $cache_contexts[] = 'languages:' . LanguageInterface::TYPE_CONTENT;

    // Full node page 2.
    $this->assertPageCacheContextsAndTags($node_2->toUrl(), $cache_contexts, [
      'http_response',
      'rendered',
      'block_view',
      'local_task',
      'config:block_list',
      'config:block.block.olivero_site_branding',
      'config:block.block.olivero_breadcrumbs',
      'config:block.block.olivero_content',
      'config:block.block.olivero_help',
      'config:block.block.olivero_search_form_narrow',
      'config:block.block.olivero_search_form_wide',
      'config:block.block.' . $block->id(),
      'config:block.block.olivero_powered',
      'config:block.block.olivero_main_menu',
      'config:block.block.olivero_account_menu',
      'config:block.block.olivero_messages',
      'config:block.block.olivero_primary_local_tasks',
      'config:block.block.olivero_secondary_local_tasks',
      'config:block.block.olivero_syndicate',
      'config:block.block.olivero_primary_admin_actions',
      'config:block.block.olivero_page_title',
      'node_view',
      'node:' . $node_2->id(),
      'user:' . $author_2->id(),
      'config:filter.format.full_html',
      'config:search.settings',
      'config:system.menu.account',
      'config:system.menu.main',
      'config:system.site',
      'comment_list',
      'node_list',
      'config:views.view.comments_recent',
      // FinishResponseSubscriber adds this cache tag to responses that have the
      // 'user.permissions' cache context for anonymous users.
      'config:user.role.anonymous',
    ]);
  }

}
