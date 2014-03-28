<?php

/**
 * @file
 * Definition of Drupal\Core\Routing\MatcherDumper.
 */

namespace Drupal\Core\Routing;

use Drupal\Core\KeyValueStore\StateInterface;
use Symfony\Component\Routing\RouteCollection;

use Drupal\Core\Database\Connection;

/**
 * Dumps Route information to a database table.
 */
class MatcherDumper implements MatcherDumperInterface {

  /**
   * The database connection to which to dump route information.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The routes to be dumped.
   *
   * @var \Symfony\Component\Routing\RouteCollection
   */
  protected $routes;

  /**
   * The state.
   *
   * @var \Drupal\Core\KeyValueStore\StateInterface
   */
  protected $state;

  /**
   * The name of the SQL table to which to dump the routes.
   *
   * @var string
   */
  protected $tableName;

  /**
   * Construct the MatcherDumper.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection which will be used to store the route
   *   information.
   * @param \Drupal\Core\KeyValueStore\StateInterface $state
   *   The state.
   * @param string $table
   *   (optional) The table to store the route info in. Defaults to 'router'.
   */
  public function __construct(Connection $connection, StateInterface $state, $table = 'router') {
    $this->connection = $connection;
    $this->state = $state;

    $this->tableName = $table;
  }

  /**
   * {@inheritdoc}
   */
  public function addRoutes(RouteCollection $routes) {
    if (empty($this->routes)) {
      $this->routes = $routes;
    }
    else {
      $this->routes->addCollection($routes);
    }
  }

  /**
   * Dumps a set of routes to the router table in the database.
   *
   * Available options:
   * - provider: The route grouping that is being dumped. All existing
   *   routes with this provider will be deleted on dump.
   * - base_class: The base class name.
   *
   * @param array $options
   *   An array of options.
   */
  public function dump(array $options = array()) {
    $options += array(
      'provider' => '',
    );

    // If there are no new routes, just delete any previously existing of this
    // provider.
    if (empty($this->routes) || !count($this->routes)) {
      $this->connection->delete($this->tableName)
        ->condition('provider', $options['provider'])
        ->execute();
    }
    // Convert all of the routes into database records.
    else {
      // Accumulate the menu masks on top of any we found before.
      $masks = array_flip($this->state->get('routing.menu_masks.' . $this->tableName, array()));
      $insert = $this->connection->insert($this->tableName)->fields(array(
        'name',
        'provider',
        'fit',
        'path',
        'pattern_outline',
        'number_parts',
        'route',
      ));
      $names = array();
      foreach ($this->routes as $name => $route) {
        $route->setOption('compiler_class', '\Drupal\Core\Routing\RouteCompiler');
        $compiled = $route->compile();
        // The fit value is a binary number which has 1 at every fixed path
        // position and 0 where there is a wildcard. We keep track of all such
        // patterns that exist so that we can minimize the the number of path
        // patterns we need to check in the RouteProvider.
        $masks[$compiled->getFit()] = 1;
        $names[] = $name;
        $values = array(
          'name' => $name,
          'provider' => $options['provider'],
          'fit' => $compiled->getFit(),
          'path' => $compiled->getPath(),
          'pattern_outline' => $compiled->getPatternOutline(),
          'number_parts' => $compiled->getNumParts(),
          'route' => serialize($route),
        );
        $insert->values($values);
      }

      // Delete any old records of this provider first, then insert the new ones.
      // That avoids stale data. The transaction makes it atomic to avoid
      // unstable router states due to random failures.
      $transaction = $this->connection->startTransaction();
      try {
        // Previously existing routes might have been moved to a new provider,
        // so ensure that none of the names to insert exists. Also delete any
        // old records of this provider (which may no longer exist).
        $delete = $this->connection->delete($this->tableName);
        $or = $delete->orConditionGroup()
          ->condition('provider', $options['provider'])
          ->condition('name', $names);
        $delete->condition($or);
        $delete->execute();

        // Insert all new routes.
        $insert->execute();
      } catch (\Exception $e) {
        $transaction->rollback();
        watchdog_exception('Routing', $e);
        throw $e;
      }
      // Sort the masks so they are in order of descending fit.
      $masks = array_keys($masks);
      rsort($masks);
      $this->state->set('routing.menu_masks.' . $this->tableName, $masks);
    }
    // The dumper is reused for multiple providers, so reset the queued routes.
    $this->routes = NULL;
  }

  /**
   * Gets the routes to match.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   A RouteCollection instance representing all routes currently in the
   *   dumper.
   */
  public function getRoutes() {
    return $this->routes;
  }

}
