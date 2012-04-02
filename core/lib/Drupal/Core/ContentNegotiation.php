<?php

/**
 * @file
 * Definition of Drupal\Core\ContentNegotiation.
 */

namespace Drupal\Core;

use Symfony\Component\HttpFoundation\Request;

/**
 * This class is a central library for content type negotiation.
 *
 * @todo Replace this class with a real content negotiation library based on
 * mod_negotiation.  Development of that is a work in progress.
 */
class ContentNegotiation {

  /**
   * Returns the normalized type of a given request.
   *
   * The normalized type is a short, lowercase version of the format, such as
   * "html" or "json" or "atom".
   *
   * @param Request $request
   *   The request object from which to extract the content type.
   */
  public function getContentType(Request $request) {
    $acceptable_content_types = $request->getAcceptableContentTypes();

    // AJAX iframe uploads need special handling, because they contain a json
    // response wrapped in <textarea>.
    if ($request->get('ajax_iframe_upload', FALSE)) {
      return 'iframeupload';
    }

    // AJAX calls need to be run through ajax rendering functions
    elseif ($request->isXmlHttpRequest()) {
      return 'ajax';
    }

    // JSON requests can be responded to using JsonResponse().
    elseif (in_array('application/json', $acceptable_content_types)) {
      return 'json';
    }

    // Do HTML last so that it always wins for */* formats.
    elseif(in_array('text/html', $acceptable_content_types) || in_array('*/*', $acceptable_content_types)) {
      return 'html';
    }
  }

}

