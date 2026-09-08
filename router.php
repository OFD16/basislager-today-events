<?php
// Lokal gelistirme routeri - vercel.json'daki route'lari birebir taklit eder.
// php -S localhost:8000 -t public router.php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && file_exists(__DIR__ . '/public' . $path)) {
  return false; // /images, /fonts vs. public/'ten servis edilir
}
require __DIR__ . '/api/index.php';
