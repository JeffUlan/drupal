<?php

/**
 * @file
 * Definition of Drupal\Core\HttpKernel.
 *
 * @todo This file is copied verbatim, with the exception of the namespace
 * change and this commment block, from Symfony full stack's FrameworkBundle.
 * Once the FrameworkBundle is available as a Composer package we should switch
 * to pulling it via Composer.
 */

namespace Drupal\Core;

use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\HttpKernel as BaseHttpKernel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * This HttpKernel is used to manage scope changes of the DI container.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class HttpKernel extends BaseHttpKernel
{
    protected $container;

    private $esiSupport;

  /**
   * Constructs a new HttpKernel.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The dependency injection container.
   * @param \Symfony\Component\HttpKernel\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
    public function __construct(EventDispatcherInterface $dispatcher, ContainerInterface $container, ControllerResolverInterface $controller_resolver, RequestStack $request_stack = NULL)
    {
        parent::__construct($dispatcher, $controller_resolver, $request_stack);

        $this->container = $container;
    }

    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        $request->headers->set('X-Php-Ob-Level', ob_get_level());

        $this->container->enterScope('request');
        $this->container->set('request', $request, 'request');

        try {
            $response = parent::handle($request, $type, $catch);
        } catch (\Exception $e) {
            $this->container->leaveScope('request');

            throw $e;
        }

        $this->container->leaveScope('request');

        return $response;
    }

    /**
     * Forwards the request to another controller.
     *
     * @param string|null $controller
     *   The controller name (a string like BlogBundle:Post:index).
     * @param array $attributes
     *   An array of request attributes.
     * @param array $query
     *   An array of request query parameters.
     *
     * @return Response
     *   A Response instance
     */
    public function forward($controller, array $attributes = array(), array $query = array())
    {
      $subrequest = $this->setupSubrequest($controller, $attributes, $query);

      return $this->handle($subrequest, HttpKernelInterface::SUB_REQUEST);
    }

    /**
     * Renders a Controller and returns the Response content.
     *
     * Note that this method generates an esi:include tag only when both the standalone
     * option is set to true and the request has ESI capability (@see Symfony\Component\HttpKernel\HttpCache\ESI).
     *
     * Available options:
     *
     *  * attributes: An array of request attributes (only when the first argument is a controller)
     *  * query: An array of request query parameters (only when the first argument is a controller)
     *  * ignore_errors: true to return an empty string in case of an error
     *  * alt: an alternative controller to execute in case of an error (can be a controller, a URI, or an array with the controller, the attributes, and the query arguments)
     *  * standalone: whether to generate an esi:include tag or not when ESI is supported
     *  * comment: a comment to add when returning an esi:include tag
     *
     * @param string $controller A controller name to execute (a string like BlogBundle:Post:index), or a relative URI
     * @param array  $options    An array of options
     *
     * @return string The Response content
     */
    public function render($controller, array $options = array())
    {
        $options = array_merge(array(
            'attributes'    => array(),
            'query'         => array(),
            'ignore_errors' => !$this->container->getParameter('kernel.debug'),
            'alt'           => array(),
            'standalone'    => false,
            'comment'       => '',
        ), $options);

        if (!is_array($options['alt'])) {
            $options['alt'] = array($options['alt']);
        }

        if (null === $this->esiSupport) {
            $this->esiSupport = $this->container->has('esi') && $this->container->get('esi')->hasSurrogateEsiCapability($this->container->get('request'));
        }

        if ($this->esiSupport && (true === $options['standalone'] || 'esi' === $options['standalone'])) {
            $uri = $this->generateInternalUri($controller, $options['attributes'], $options['query']);

            $alt = '';
            if ($options['alt']) {
                $alt = $this->generateInternalUri($options['alt'][0], isset($options['alt'][1]) ? $options['alt'][1] : array(), isset($options['alt'][2]) ? $options['alt'][2] : array());
            }

            return $this->container->get('esi')->renderIncludeTag($uri, $alt, $options['ignore_errors'], $options['comment']);
        }

        if ('js' === $options['standalone']) {
            $uri = $this->generateInternalUri($controller, $options['attributes'], $options['query'], false);
            $defaultContent = null;

            if ($template = $this->container->getParameter('templating.hinclude.default_template')) {
                $defaultContent = $this->container->get('templating')->render($template);
            }

            return $this->renderHIncludeTag($uri, $defaultContent);
        }

        $request = $this->container->get('request');

        // controller or URI?
        if (0 === strpos($controller, '/')) {
            $subRequest = Request::create($request->getUriForPath($controller), 'get', array(), $request->cookies->all(), array(), $request->server->all());
            if ($session = $request->getSession()) {
                $subRequest->setSession($session);
            }
        } else {
            $options['attributes']['_controller'] = $controller;

            if (!isset($options['attributes']['_format'])) {
                $options['attributes']['_format'] = $request->getRequestFormat();
            }

            $options['attributes'][RouteObjectInterface::ROUTE_OBJECT] = '_internal';
            $subRequest = $request->duplicate($options['query'], null, $options['attributes']);
            $subRequest->setMethod('GET');
        }

        $level = ob_get_level();
        try {
            $response = $this->handle($subRequest, HttpKernelInterface::SUB_REQUEST, false);

            if (!$response->isSuccessful()) {
                throw new \RuntimeException(sprintf('Error when rendering "%s" (Status code is %s).', $request->getUri(), $response->getStatusCode()));
            }

            if (!$response instanceof StreamedResponse) {
                return $response->getContent();
            }

            $response->sendContent();
        } catch (\Exception $e) {
            if ($options['alt']) {
                $alt = $options['alt'];
                unset($options['alt']);
                $options['attributes'] = isset($alt[1]) ? $alt[1] : array();
                $options['query'] = isset($alt[2]) ? $alt[2] : array();

                return $this->render($alt[0], $options);
            }

            if (!$options['ignore_errors']) {
                throw $e;
            }

            // let's clean up the output buffers that were created by the sub-request
            while (ob_get_level() > $level) {
                ob_get_clean();
            }
        }
    }

    /**
     * Generates an internal URI for a given controller.
     *
     * This method uses the "_internal" route, which should be available.
     *
     * @param string  $controller A controller name to execute (a string like BlogBundle:Post:index), or a relative URI
     * @param array   $attributes An array of request attributes
     * @param array   $query      An array of request query parameters
     * @param boolean $secure
     *
     * @return string An internal URI
     */
    public function generateInternalUri($controller, array $attributes = array(), array $query = array(), $secure = true)
    {
        if (0 === strpos($controller, '/')) {
            return $controller;
        }

        $path = http_build_query($attributes, '', '&');
        $uri = $this->container->get('router')->generate($secure ? '_internal' : '_internal_public', array(
            'controller' => $controller,
            'path'       => $path ?: 'none',
            '_format'    => $this->container->get('request')->getRequestFormat(),
        ));

        if ($queryString = http_build_query($query, '', '&')) {
            $uri .= '?'.$queryString;
        }

        return $uri;
    }

    /**
     * Renders an HInclude tag.
     *
     * @param string $uri A URI
     * @param string $defaultContent Default content
     */
    public function renderHIncludeTag($uri, $defaultContent = null)
    {
        return sprintf('<hx:include src="%s">%s</hx:include>', $uri, $defaultContent);
    }

    public function hasEsiSupport()
    {
        return $this->esiSupport;
    }

  /**
   * Creates a request object for a subrequest.
   *
   * @param string $controller
   *   The controller name (a string like BlogBundle:Post:index)
   * @param array $attributes
   *   An array of request attributes.
   * @param array $query
   *   An array of request query parameters.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   Returns the new request.
   */
  public function setupSubrequest($controller, array $attributes, array $query) {
    // Don't override the controller if it's NULL.
    if (isset($controller)) {
      $attributes['_controller'] = $controller;
    }
    else {
      unset($attributes['_controller']);
    }
    return $this->container->get('request')->duplicate($query, NULL, $attributes);
  }

}
