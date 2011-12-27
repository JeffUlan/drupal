<?php

namespace Drupal\Database;

use Exception;

/**
 * Exception thrown for merge queries that do not make semantic sense.
 *
 * There are many ways that a merge query could be malformed.  They should all
 * throw this exception and set an appropriately descriptive message.
 */
class InvalidMergeQueryException extends Exception {}
