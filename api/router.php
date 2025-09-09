<?php
// /api/router.php
// Forward Vercel request to your legacy PHP files in project root
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$path = ltrim($path, '/');
if ($path === '') $path = 'index.php'; // default route

$target = __DIR__ . '/../' . $path;
if (!is_file($target) || pathinfo($target, PATHINFO_EXTENSION) !== 'php') {
  http_response_code(404);
  echo "Not Found";
  exit;
}

// Ensure includes resolve from project root
chdir(dirname($target));
require $target;
