<?php

/**
 * @file
 * Fake an https request, for use during testing.
 */

use Symfony\Component\HttpFoundation\Request;

// Set a global variable to indicate a mock HTTPS request.
$is_https_mock = empty($_SERVER['HTTPS']);

// Change to https.
$_SERVER['HTTPS'] = 'on';
foreach ($_SERVER as $key => $value) {
  $_SERVER[$key] = str_replace('core/modules/system/tests/https.php', 'index.php', $value);
  $_SERVER[$key] = str_replace('http://', 'https://', $_SERVER[$key]);
}

// Change current directory to the Drupal root.
chdir('../../../..');
define('DRUPAL_ROOT', getcwd());
require_once DRUPAL_ROOT . '/core/includes/bootstrap.inc';

// Make sure this file can only be used by simpletest.
drupal_bootstrap(DRUPAL_BOOTSTRAP_CONFIGURATION);
if (!drupal_valid_test_ua()) {
  header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
  exit;
}

// Continue with normal request handling.
$request = Request::createFromGlobals();
request($request);

drupal_bootstrap(DRUPAL_BOOTSTRAP_CODE);

$kernel = drupal_container()->get('httpkernel');
$response = $kernel->handle($request)->prepare($request)->send();
$kernel->terminate($request, $response);
