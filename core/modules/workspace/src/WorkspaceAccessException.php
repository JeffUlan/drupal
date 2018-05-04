<?php

namespace Drupal\workspace;

use Drupal\Core\Access\AccessException;

/**
 * Exception thrown when trying to switch to an inaccessible workspace.
 */
class WorkspaceAccessException extends AccessException {

}
