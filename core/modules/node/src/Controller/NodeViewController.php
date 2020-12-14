<?php

namespace Drupal\node\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Controller\EntityViewController;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a controller to render a single node.
 */
class NodeViewController extends EntityViewController {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Creates a NodeViewController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer, AccountInterface $current_user, EntityRepositoryInterface $entity_repository) {
    parent::__construct($entity_type_manager, $renderer);
    $this->currentUser = $current_user;
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('current_user'),
      $container->get('entity.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $node, $view_mode = 'full', $langcode = NULL) {
    $build = parent::view($node, $view_mode, $langcode);

    foreach ($node->uriRelationships() as $rel) {
      $url = $node->toUrl($rel)->setAbsolute(TRUE);
      // Add link relationships if the user is authenticated or if the anonymous
      // user has access. Access checking must be done for anonymous users to
      // avoid traffic to inaccessible pages from web crawlers. For
      // authenticated users, showing the links in HTML head does not impact
      // user experience or security, since the routes are access checked when
      // visited and only visible via view source. This prevents doing
      // potentially expensive and hard to cache access checks on every request.
      // This means that the page will vary by user.permissions. We also rely on
      // the access checking fallback to ensure the correct cacheability
      // metadata if we have to check access.
      if ($this->isValidRel($rel) && ($this->currentUser->isAuthenticated() || $url->access($this->currentUser))) {
        // Set the node path as the canonical URL to prevent duplicate content.
        $build['#attached']['html_head_link'][] = [
          [
            'rel' => $rel,
            'href' => $url->toString(),
          ],
          TRUE,
        ];
      }

      if ($rel == 'canonical') {
        // Set the non-aliased canonical path as a default shortlink.
        $build['#attached']['html_head_link'][] = [
          [
            'rel' => 'shortlink',
            'href' => $url->setOption('alias', TRUE)->toString(),
          ],
          TRUE,
        ];
      }
    }

    // Since this generates absolute URLs, it can only be cached "per site".
    $build['#cache']['contexts'][] = 'url.site';

    // Given this varies by $this->currentUser->isAuthenticated(), add a cache
    // context based on the anonymous role.
    $build['#cache']['contexts'][] = 'user.roles:anonymous';

    return $build;
  }

  /**
   * The _title_callback for the page that renders a single node.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   The current node.
   *
   * @return string
   *   The page title.
   */
  public function title(EntityInterface $node) {
    return $this->entityRepository->getTranslationFromContext($node)->label();
  }

  /**
   * Checks if rel attribute is w3c.org valid
   *
   * @param string $rel
   *   The unchecked rel attribute.
   *
   * @return bool
   *   The valid state
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/rel
   */
  private function isValidRel($rel) : bool {
    // List of all supported rel tags.
    $validRelList = [
      'alternate',
      'author',
      'bookmark',
      'canonical',
      'dns-prefetch',
      'external',
      'help',
      'icon',
      'license',
      'manifest',
      'modulepreload',
      'next',
      'nofollow',
      'noopener',
      'noreferrer',
      'opener',
      'pingback',
      'preconnect',
      'prefetch',
      'preload',
      'prerender',
      'prev',
      'search',
      'shortcut icon',
      'shortlink',
      'stylesheet',
      'tag',
    ];
    return in_array($rel, $validRelList, TRUE);
  }

}
