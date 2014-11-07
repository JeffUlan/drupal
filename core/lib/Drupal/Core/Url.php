<?php

/**
 * @file
 * Contains \Drupal\Core\Url.
 */

namespace Drupal\Core;

use Drupal\Component\Utility\String;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\UnroutedUrlAssemblerInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines an object that holds information about a URL.
 */
class Url {
  use DependencySerializationTrait;

  /**
   * The URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The unrouted URL assembler.
   *
   * @var \Drupal\Core\Utility\UnroutedUrlAssemblerInterface
   */
  protected $urlAssembler;

  /**
   * The access manager
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * The route name.
   *
   * @var string
   */
  protected $routeName;

  /**
   * The route parameters.
   *
   * @var array
   */
  protected $routeParameters = array();

  /**
   * The URL options.
   *
   * @var array
   */
  protected $options = array();

  /**
   * Indicates whether this object contains an external URL.
   *
   * @var bool
   */
  protected $external = FALSE;

  /**
   * Indicates whether this URL is for a URI without a Drupal route.
   *
   * @var bool
   */
  protected $unrouted = FALSE;

  /**
   * The non-route URI.
   *
   * Only used if self::$unrouted is TRUE.
   *
   * @var string
   */
  protected $uri;

  /**
   * Constructs a new Url object.
   *
   * In most cases, use Url::fromRoute() or Url::fromUri() rather than
   * constructing Url objects directly in order to avoid ambiguity and make your
   * code more self-documenting.
   *
   * @param string $route_name
   *   The name of the route
   * @param array $route_parameters
   *   (optional) An associative array of parameter names and values.
   * @param array $options
   *   (optional) An associative array of additional options, with the following
   *   elements:
   *   - 'query': An array of query key/value-pairs (without any URL-encoding)
   *     to append to the URL. Merged with the parameters array.
   *   - 'fragment': A fragment identifier (named anchor) to append to the URL.
   *     Do not include the leading '#' character.
   *   - 'absolute': Defaults to FALSE. Whether to force the output to be an
   *     absolute link (beginning with http:). Useful for links that will be
   *     displayed outside the site, such as in an RSS feed.
   *   - 'language': An optional language object used to look up the alias
   *     for the URL. If $options['language'] is omitted, it defaults to the
   *     current language for the language type LanguageInterface::TYPE_URL.
   *   - 'https': Whether this URL should point to a secure location. If not
   *     defined, the current scheme is used, so the user stays on HTTP or HTTPS
   *     respectively. if mixed mode sessions are permitted, TRUE enforces HTTPS
   *     and FALSE enforces HTTP.
   *
   * @see static::fromRoute()
   * @see static::fromUri()
   *
   * @todo Update this documentation for non-routed URIs in
   *   https://www.drupal.org/node/2346787
   */
  public function __construct($route_name, $route_parameters = array(), $options = array()) {
    $this->routeName = $route_name;
    $this->routeParameters = $route_parameters;
    $this->options = $options;
  }

  /**
   * Creates a new Url object for a URL that has a Drupal route.
   *
   * This method is for URLs that have Drupal routes (that is, most pages
   * generated by Drupal). For non-routed local URIs relative to the base
   * path (like robots.txt) use Url::fromUri() with the base:// scheme.
   *
   * @param string $route_name
   *   The name of the route
   * @param array $route_parameters
   *   (optional) An associative array of route parameter names and values.
   * @param array $options
   *   (optional) An associative array of additional URL options, with the
   *   following elements:
   *   - 'query': An array of query key/value-pairs (without any URL-encoding)
   *     to append to the URL. Merged with the parameters array.
   *   - 'fragment': A fragment identifier (named anchor) to append to the URL.
   *     Do not include the leading '#' character.
   *   - 'absolute': Defaults to FALSE. Whether to force the output to be an
   *     absolute link (beginning with http:). Useful for links that will be
   *     displayed outside the site, such as in an RSS feed.
   *   - 'language': An optional language object used to look up the alias
   *     for the URL. If $options['language'] is omitted, it defaults to the
   *     current language for the language type LanguageInterface::TYPE_URL.
   *   - 'https': Whether this URL should point to a secure location. If not
   *     defined, the current scheme is used, so the user stays on HTTP or HTTPS
   *     respectively. if mixed mode sessions are permitted, TRUE enforces HTTPS
   *     and FALSE enforces HTTP.
   *
   * @return \Drupal\Core\Url
   *   A new Url object for a routed (internal to Drupal) URL.
   *
   * @see static::fromUri()
   */
  public static function fromRoute($route_name, $route_parameters = array(), $options = array()) {
    return new static($route_name, $route_parameters, $options);
  }

  /**
   * Creates a new Url object for a URI that does not have a Drupal route.
   *
   * This method is for generating URLs for URIs that do not have Drupal
   * routes, both external URLs and unrouted local URIs like
   * base://robots.txt. For URLs that have Drupal routes (that is, most pages
   * generated by Drupal), use Url::fromRoute().
   *
   * @param string $uri
   *   The URI of the external resource including the scheme. For Drupal paths
   *   that are not handled by the routing system, use base:// for the scheme.
   * @param array $options
   *   (optional) An associative array of additional URL options, with the
   *   following elements:
   *   - 'query': An array of query key/value-pairs (without any URL-encoding)
   *     to append to the URL. Merged with the parameters array.
   *   - 'fragment': A fragment identifier (named anchor) to append to the URL.
   *     Do not include the leading '#' character.
   *   - 'absolute': Defaults to FALSE. Whether to force the output to be an
   *     absolute link (beginning with http:). Useful for links that will be
   *     displayed outside the site, such as in an RSS feed.
   *   - 'language': An optional language object used to look up the alias
   *     for the URL. If $options['language'] is omitted, it defaults to the
   *     current language for the language type LanguageInterface::TYPE_URL.
   *   - 'https': Whether this URL should point to a secure location. If not
   *     defined, the current scheme is used, so the user stays on HTTP or HTTPS
   *     respectively. if mixed mode sessions are permitted, TRUE enforces HTTPS
   *     and FALSE enforces HTTP.
   *
   * @return \Drupal\Core\Url
   *   A new Url object for an unrouted (non-Drupal) URL.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the passed in path has no scheme.
   *
   * @see static::fromRoute()
   */
  public static function fromUri($uri, $options = array()) {
    if (!parse_url($uri, PHP_URL_SCHEME)) {
      throw new \InvalidArgumentException(String::format('The URI "@uri" is invalid. You must use a valid URI scheme. Use base:// for a path, e.g., to a Drupal file that needs the base path. Do not use this for internal paths controlled by Drupal.', ['@uri' => $uri]));
    }

    $url = new static($uri, array(), $options);
    $url->setUnrouted();

    return $url;
  }

  /**
   * Returns the Url object matching a request.
   *
   * SECURITY NOTE: The request path is not checked to be valid and accessible
   * by the current user to allow storing and reusing Url objects by different
   * users. The 'path.validator' service getUrlIfValid() method should be used
   * instead of this one if validation and access check is desired. Otherwise,
   * 'access_manager' service checkNamedRoute() method should be used on the
   * router name and parameters stored in the Url object returned by this
   * method.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object.
   *
   * @return static
   *   A Url object. Warning: the object is created even if the current user
   *   would get an access denied running the same request via the normal page
   *   flow.
   *
   * @throws \Drupal\Core\Routing\MatchingRouteNotFoundException
   *   Thrown when the request cannot be matched.
   */
  public static function createFromRequest(Request $request) {
    // We use the router without access checks because URL objects might be
    // created and stored for different users.
    $result = \Drupal::service('router.no_access_checks')->matchRequest($request);
    $route_name = $result[RouteObjectInterface::ROUTE_NAME];
    $route_parameters = $result['_raw_variables']->all();
    return new static($route_name, $route_parameters);
  }

  /**
   * Sets this Url to encapsulate an unrouted URI.
   *
   * @return $this
   */
  protected function setUnrouted() {
    $this->unrouted = TRUE;
    // What was passed in as the route name is actually the URI.
    // @todo Consider fixing this in https://www.drupal.org/node/2346787.
    $this->uri = $this->routeName;
    // Set empty route name and parameters.
    $this->routeName = NULL;
    $this->routeParameters = array();
    // @todo Add a method for the check below in
    // https://www.drupal.org/node/2346859.
    if ($this->external = strpos($this->uri, 'base://') !== 0) {
      $this->options['external'] = TRUE;
    }
  }

  /**
   * Indicates if this Url is external.
   *
   * @return bool
   */
  public function isExternal() {
    return $this->external;
  }

  /**
   * Indicates if this Url has a Drupal route.
   *
   * @return bool
   */
  public function isRouted() {
    return !$this->unrouted;
  }

  /**
   * Returns the route name.
   *
   * @return string
   *
   * @throws \UnexpectedValueException.
   *   If this is a URI with no corresponding route.
   */
  public function getRouteName() {
    if ($this->unrouted) {
      throw new \UnexpectedValueException('External URLs do not have an internal route name.');
    }

    return $this->routeName;
  }

  /**
   * Returns the route parameters.
   *
   * @return array
   *
   * @throws \UnexpectedValueException.
   *   If this is a URI with no corresponding route.
   */
  public function getRouteParameters() {
    if ($this->unrouted) {
      throw new \UnexpectedValueException('External URLs do not have internal route parameters.');
    }

    return $this->routeParameters;
  }

  /**
   * Sets the route parameters.
   *
   * @param array $parameters
   *   The array of parameters.
   *
   * @return $this
   *
   * @throws \UnexpectedValueException.
   *   If this is a URI with no corresponding route.
   */
  public function setRouteParameters($parameters) {
    if ($this->unrouted) {
      throw new \UnexpectedValueException('External URLs do not have route parameters.');
    }
    $this->routeParameters = $parameters;
    return $this;
  }

  /**
   * Sets a specific route parameter.
   *
   * @param string $key
   *   The key of the route parameter.
   * @param mixed $value
   *   The route parameter.
   *
   * @return $this
   *
   * @throws \UnexpectedValueException.
   *   If this is a URI with no corresponding route.
   */
  public function setRouteParameter($key, $value) {
    if ($this->unrouted) {
      throw new \UnexpectedValueException('External URLs do not have route parameters.');
    }
    $this->routeParameters[$key] = $value;
    return $this;
  }

  /**
   * Returns the URL options.
   *
   * @return array
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * Gets a specific option.
   *
   * @param string $name
   *   The name of the option.
   *
   * @return mixed
   *   The value for a specific option, or NULL if it does not exist.
   */
  public function getOption($name) {
    if (!isset($this->options[$name])) {
      return NULL;
    }

    return $this->options[$name];
  }

  /**
   * Sets the URL options.
   *
   * @param array $options
   *   The array of options.
   *
   * @return $this
   */
  public function setOptions($options) {
    $this->options = $options;
    return $this;
  }

  /**
   * Sets a specific option.
   *
   * @param string $name
   *   The name of the option.
   * @param mixed $value
   *   The option value.
   *
   * @return $this
   */
  public function setOption($name, $value) {
    $this->options[$name] = $value;
    return $this;
  }

  /**
   * Returns the URI of the URL.
   *
   * Only to be used if self::$unrouted is TRUE.
   *
   * @return string
   *   A URI not connected to a route. May be an external URL.
   *
   * @throws \UnexpectedValueException
   *   Thrown when the URI was requested for a routed URL.
   */
  public function getUri() {
    if (!$this->unrouted) {
      throw new \UnexpectedValueException('This URL has a Drupal route, so the canonical form is not a URI.');
    }

    return $this->uri;
  }

  /**
   * Sets the absolute value for this Url.
   *
   * @param bool $absolute
   *   (optional) Whether to make this Url absolute or not. Defaults to TRUE.
   *
   * @return $this
   */
  public function setAbsolute($absolute = TRUE) {
    $this->options['absolute'] = $absolute;
    return $this;
  }

  /**
   * Generates the URI for this Url object.
   */
  public function toString() {
    if ($this->unrouted) {
      return $this->unroutedUrlAssembler()->assemble($this->getUri(), $this->getOptions());
    }

    return $this->urlGenerator()->generateFromRoute($this->getRouteName(), $this->getRouteParameters(), $this->getOptions());
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return $this->toString();
  }

  /**
   * Returns all the information about the route.
   *
   * @return array
   *   An associative array containing all the properties of the route.
   *
   * @deprecated in Drupal 8.0.x-dev, will be removed before Drupal 9.0.
   *   Most usecases should use the URL object directly, like #type links. Other
   *   usecases should get the information from the URL object manually.
   */
  public function toArray() {
    return [
      'url' => $this,
      'options' => $this->getOptions(),
    ];
  }

  /**
   * Returns the route information for a render array.
   *
   * @return array
   *   An associative array suitable for a render array.
   */
  public function toRenderArray() {
    $render_array = [
      '#url' => $this,
      '#options' => $this->getOptions(),
    ];
    if (!$this->unrouted) {
      $render_array['#access_callback'] = [get_class(), 'renderAccess'];
    }
    return $render_array;
  }

  /**
   * Returns the internal path (system path) for this route.
   *
   * This path will not include any prefixes, fragments, or query strings.
   *
   * @return string
   *   The internal path for this route.
   *
   * @throws \UnexpectedValueException.
   *   If this is a URI with no corresponding system path.
   *
   * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
   *   System paths should not be used - use route names and parameters.
   */
  public function getInternalPath() {
    if ($this->unrouted) {
      throw new \UnexpectedValueException('Unrouted URIs do not have internal representations.');
    }
    return $this->urlGenerator()->getPathFromRoute($this->getRouteName(), $this->getRouteParameters());
  }

  /**
   * Checks this Url object against applicable access check services.
   *
   * Determines whether the route is accessible or not.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) Run access checks for this account. Defaults to the current
   *   user.
   *
   * @return bool
   *   Returns TRUE if the user has access to the url, otherwise FALSE.
   */
  public function access(AccountInterface $account = NULL) {
    return $this->accessManager()->checkNamedRoute($this->getRouteName(), $this->getRouteParameters(), $account);
  }

  /**
   * Checks a Url render element against applicable access check services.
   *
   * @param array $element
   *   A render element as returned from \Drupal\Core\Url::toRenderArray().
   *
   * @return bool
   *   Returns TRUE if the current user has access to the url, otherwise FALSE.
   */
  public static function renderAccess(array $element) {
    return $element['#url']->access();
  }

  /**
   * @return \Drupal\Core\Access\AccessManagerInterface
   */
  protected function accessManager() {
    if (!isset($this->accessManager)) {
      $this->accessManager = \Drupal::service('access_manager');
    }
    return $this->accessManager;
  }

  /**
   * Gets the URL generator.
   *
   * @return \Drupal\Core\Routing\UrlGeneratorInterface
   *   The URL generator.
   */
  protected function urlGenerator() {
    if (!$this->urlGenerator) {
      $this->urlGenerator = \Drupal::urlGenerator();
    }
    return $this->urlGenerator;
  }

  /**
   * Gets the unrouted URL assembler for non-Drupal URLs.
   *
   * @return \Drupal\Core\Utility\UnroutedUrlAssemblerInterface
   *   The unrouted URL assembler.
   */
  protected function unroutedUrlAssembler() {
    if (!$this->urlAssembler) {
      $this->urlAssembler = \Drupal::service('unrouted_url_assembler');
    }
    return $this->urlAssembler;
  }

  /**
   * Sets the URL generator.
   *
   * @param \Drupal\Core\Routing\UrlGeneratorInterface
   *   The URL generator.
   *
   * @return $this
   */
  public function setUrlGenerator(UrlGeneratorInterface $url_generator) {
    $this->urlGenerator = $url_generator;
    return $this;
  }

  /**
   * Sets the unrouted URL assembler.
   *
   * @param \Drupal\Core\Utility\UnroutedUrlAssemblerInterface
   *   The unrouted URL assembler.
   *
   * @return $this
   */
  public function setUnroutedUrlAssembler(UnroutedUrlAssemblerInterface $url_assembler) {
    $this->urlAssembler = $url_assembler;
    return $this;
  }

}
