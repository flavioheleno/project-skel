<?php
declare(strict_types = 1);

if (PHP_SAPI !== 'cli-server') {
  echo 'Invalid SAPI ' . php_sapi_name(), PHP_EOL;

  exit;
}

error_reporting(E_ALL);
ini_set('display_errors', '1');

// $_SERVER['DOCUMENT_ROOT'] is the absolute path to the public directory in your filesystem, e.g. /var/www/app/public
// $_SERVER['REQUEST_URI'] is the URI of the HTTP request, e.g. /css/bulma.css

$requestUri = $_SERVER['REQUEST_URI'];
$queryPos = strpos($requestUri, '?');
if ($queryPos !== false) {
  $requestUri = substr($requestUri, 0, $queryPos);
}

$localPath = "{$_SERVER['DOCUMENT_ROOT']}{$requestUri}";

// if $localPath is a direct file hit let the cli server handle this simple case.
if (is_file($localPath)) {
  return false;
}

// if $localPath is a directory and contains an index.html file let the
// cli server handle it, because we know it _will_ serve that index.html
if (is_dir($localPath) && is_file("{$localPath}/index.html")) {
  return false;
}

require_once __DIR__ . '/vendor/autoload.php';

if (is_file(__DIR__ . '/.env')) {
  $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
  $dotenv->safeLoad();
}

$_ENV['PHP_ENV'] = 'dev';

// all other cases should be handled by the real front controller
require_once __DIR__ . '/public/index.php';
