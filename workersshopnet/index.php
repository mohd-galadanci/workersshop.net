
<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Check if this is an API request
$requestUri = $_SERVER['REQUEST_URI'];
if (strpos($requestUri, '/api/') === 0) {
    // Handle API requests through Laravel
    if (file_exists(__DIR__.'/storage/framework/maintenance.php')) {
        require __DIR__.'/storage/framework/maintenance.php';
    }

    require __DIR__.'/vendor/autoload.php';

    $app = require_once __DIR__.'/bootstrap/app.php';
    $kernel = $app->make(Kernel::class);

    $response = $kernel->handle(
        $request = Request::capture()
    )->send();

    $kernel->terminate($request, $response);
} else {
    // Serve static files or redirect to frontend
    if (file_exists(__DIR__ . '/public' . $requestUri) && $requestUri !== '/') {
        return false; // Let the built-in server handle static files
    } else {
        // Serve the main HTML file
        if (file_exists(__DIR__ . '/public/index.html')) {
            include __DIR__ . '/public/index.html';
        } else {
            echo "<h1>Welcome to workersshop</h1><p>Please set up the frontend files.</p>";
        }
    }
}
$requests = $query->orderBy('created_at', 'desc')->paginate(6);