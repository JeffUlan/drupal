<?php

/**
 * @file
 * Definition of Drupal\Core\HtmlPageController.
 */

namespace Drupal\Core;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default controller for most HTML pages.
 */
class HtmlPageController implements ContainerAwareInterface {

  /**
   * The injection container for this object.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * Injects the service container used by this object.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this object should use.
   */
  public function setContainer(ContainerInterface $container = NULL) {
    $this->container = $container;
  }

  /**
   * Controller method for generic HTML pages.
   *
   * @param Request $request
   *   The request object.
   * @param callable $_content
   *   The body content callable that contains the body region of this page.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response object.
   */
  public function content(Request $request, $_content) {

    // @todo When we have a Generator, we can replace the forward() call with
    // a render() call, which would handle ESI and hInclude as well.  That will
    // require an _internal route.  For examples, see:
    // https://github.com/symfony/symfony/blob/master/src/Symfony/Bundle/FrameworkBundle/Resources/config/routing/internal.xml
    // https://github.com/symfony/symfony/blob/master/src/Symfony/Bundle/FrameworkBundle/Controller/InternalController.php
    $attributes = clone $request->attributes;
    $controller = $_content;

    // We need to clean off the derived information and such so that the
    // subrequest can be processed properly without leaking data through.
    $attributes->remove('system_path');
    $attributes->remove('_content');

    $response = $this->container->get('http_kernel')->forward($controller, $attributes->all(), $request->query->all());

    // For successful (HTTP status 200) responses, decorate with blocks.
    if ($response->isOk()) {
      $page_content = $response->getContent();
      $response = new Response(drupal_render_page($page_content));
    }

    return $response;
  }
}
