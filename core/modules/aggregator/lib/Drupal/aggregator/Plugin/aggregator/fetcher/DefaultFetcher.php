<?php

/**
 * @file
 * Definition of Drupal\aggregator\Plugin\aggregator\fetcher\DefaultFetcher.
 */

namespace Drupal\aggregator\Plugin\aggregator\fetcher;

use Drupal\aggregator\Plugin\FetcherInterface;
use Drupal\aggregator\Plugin\Core\Entity\Feed;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Guzzle\Http\Exception\BadResponseException;

/**
 * Defines a default fetcher implementation.
 *
 * Uses drupal_http_request() to download the feed.
 *
 * @Plugin(
 *   id = "aggregator",
 *   title = @Translation("Default fetcher"),
 *   description = @Translation("Downloads data from a URL using Drupal's HTTP request handler.")
 * )
 */
class DefaultFetcher implements FetcherInterface {

  /**
   * Implements Drupal\aggregator\Plugin\FetcherInterface::fetch().
   */
  function fetch(Feed $feed) {
    $request = drupal_container()->get('http_default_client')->get($feed->url->value);
    $feed->source_string = FALSE;

    // Generate conditional GET headers.
    if ($feed->etag->value) {
      $request->addHeader('If-None-Match', $feed->etag->value);
    }
    if ($feed->modified->value) {
      $request->addHeader('If-Modified-Since', gmdate(DATE_RFC1123, $feed->modified->value));
    }

    try {
      $response = $request->send();
      $feed->source_string = $response->getBody(TRUE);
      $feed->etag = $response->getEtag();
      $feed->modified = strtotime($response->getLastModified());
      $feed->http_headers = $response->getHeaders();

      return TRUE;
    }
    catch (BadResponseException $e) {
      $response = $e->getResponse();
      watchdog('aggregator', 'The feed from %site seems to be broken due to "%error".', array('%site' => $feed->label(), '%error' => $response->getStatusCode() . ' ' . $response->getReasonPhrase()), WATCHDOG_WARNING);
      drupal_set_message(t('The feed from %site seems to be broken because of error "%error".', array('%site' => $feed->label(), '%error' => $response->getStatusCode() . ' ' . $response->getReasonPhrase())));
      return FALSE;
    }
  }
}
