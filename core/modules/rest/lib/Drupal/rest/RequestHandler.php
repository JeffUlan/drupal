<?php

/**
 * @file
 * Definition of Drupal\rest\RequestHandler.
 */

namespace Drupal\rest;

use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * Acts as intermediate request forwarder for resource plugins.
 */
class RequestHandler extends ContainerAware {

  /**
   * Handles a web API request.
   *
   * @param Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request object.
   * @param mixed $id
   *   The resource ID.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   */
  public function handle(Request $request, $id = NULL) {
    $plugin = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)->getDefault('_plugin');
    $method = strtolower($request->getMethod());

    $resource = $this->container
      ->get('plugin.manager.rest')
      ->getInstance(array('id' => $plugin));

    // Deserialze incoming data if available.
    $serializer = $this->container->get('serializer');
    $received = $request->getContent();
    $unserialized = NULL;
    if (!empty($received)) {
      $definition = $resource->getDefinition();
      $class = $definition['serialization_class'];
      try {
        // @todo Replace the format here with something we get from the HTTP
        //   Content-type header. See http://drupal.org/node/1850704
        $unserialized = $serializer->deserialize($received, $class, 'drupal_jsonld');
      }
      catch (UnexpectedValueException $e) {
        $error['error'] = $e->getMessage();
        $content = $serializer->serialize($error, 'drupal_jsonld');
        return new Response($content, 400, array('Content-Type' => 'application/vnd.drupal.ld+json'));
      }
    }

    // Invoke the operation on the resource plugin.
    try {
      $response = $resource->{$method}($id, $unserialized, $request);
    }
    catch (HttpException $e) {
      $error['error'] = $e->getMessage();
      $content = $serializer->serialize($error, 'drupal_jsonld');
      // Add the default content type, but only if the headers from the
      // exception have not specified it already.
      $headers = $e->getHeaders() + array('Content-Type' => 'application/vnd.drupal.ld+json');
      return new Response($content, $e->getStatusCode(), $headers);
    }

    // Serialize the outgoing data for the response, if available.
    $data = $response->getResponseData();
    if ($data != NULL) {
      // @todo Replace the format here with something we get from the HTTP
      //   Accept headers. See http://drupal.org/node/1833440
      $output = $serializer->serialize($data, 'drupal_jsonld');
      $response->setContent($output);
      $response->headers->set('Content-Type', 'application/vnd.drupal.ld+json');
    }
    return $response;
  }

  /**
   * Generates a CSRF protecting session token.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   */
  public function csrfToken() {
    return new Response(drupal_get_token('rest'), 200, array('Content-Type' => 'text/plain'));
  }
}
