<?php

/**
 * @file
 * Contains \Drupal\search\Plugin\SearchInterface.
 */

namespace Drupal\search\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines a common interface for all SearchPlugin objects.
 */
interface SearchInterface extends PluginInspectionInterface {

  /**
   * Sets the keywords, parameters, and attributes to be used by execute().
   *
   * @param string $keywords
   *   The keywords to use in a search.
   * @param array $parameters
   *   Array of parameters as an associative array. This is expected to
   *   be the query string from the current request.
   * @param array $attributes
   *   Array of attributes, usually from the current request object.
   *
   * @return \Drupal\search\Plugin\SearchInterface
   *   A search plugin object for chaining.
   */
  public function setSearch($keywords, array $parameters, array $attributes);

  /**
   * Returns the currently set keywords of the plugin instance.
   *
   * @return string
   *   The keywords.
   */
  public function getKeywords();

  /**
   * Returns the current parameters set using setSearch().
   *
   * @return array
   *   The parameters.
   */
  public function getParameters();

  /**
   * Returns the currently set attributes (from the request).
   *
   * @return array
   *   The attributes.
   */
  public function getAttributes();

  /**
   * Verifies if the values set via setSearch() are valid and sufficient.
   *
   * @return bool
   *   TRUE if the search settings are valid and sufficient to execute a search,
   *   and FALSE if not.
   */
  public function isSearchExecutable();

  /**
   * Executes the search.
   *
   * @return array
   *   A structured list of search results.
   */
  public function execute();

  /**
   * Executes the search and builds render arrays for the result items.
   *
   * @return array
   *   An array of render arrays of search result items (generally each item
   *   has '#theme' set to 'search_result'), or an empty array if there are no
   *   results.
   */
  public function buildResults();

  /**
   * Alters the search form when being built for a given plugin.
   *
   * The core search module only invokes this method on active module plugins
   * when building a form for them in
   * \Drupal\search\Form\SearchPageForm::form(). A plugin implementing this
   * will also need to implement the buildSearchUrlQuery() method.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param array $form_state
   *   A keyed array containing the current state of the form. The arguments
   *   that \Drupal::formBuilder()->getForm() was originally called with are
   *   available in the array $form_state['build_info']['args'].
   *
   * @see SearchInterface::buildSearchUrlQuery()
   */
  public function searchFormAlter(array &$form, array &$form_state);

  /**
   * Builds the URL GET query parameters array for search.
   *
   * When the search form is submitted, a redirect is generated with the
   * search input as GET query parameters. Plugins using the searchFormAlter()
   * method to add form elements to the search form will need to override this
   * method to gather the form input and add it to the GET query parameters.
   *
   * @param array $form_state
   *   The form state, with submitted form information.
   *
   * @return array
   *   An array of GET query parameters containing all relevant form values
   *   to process the search. The 'keys' element must be present in order to
   *   trigger generation of search results, even if it is empty or unused by
   *   the search plugin.
   *
   * @see SearchInterface::searchFormAlter()
   */
  public function buildSearchUrlQuery($form_state);

}
