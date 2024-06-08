<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/deploy', function (Request $request) {
    try {
        // Install Composer dependencies
        Artisan::call('composer:install', ['--no-dev' => true, '--prefer-dist' => true]);

        // Install npm dependencies
        exec('npm install');

        // Build frontend assets
        exec('npm run production');

        // Generate application key
        Artisan::call('key:generate');

        // Run database migrations
        Artisan::call('migrate', ['--force' => true]);

        // Set file permissions (replace 'username' with appropriate values)
        exec('chown -R username:username ' . base_path());
        exec('chmod -R 755 ' . base_path());
        exec('chmod -R 775 ' . storage_path());
        exec('chmod -R 775 ' . base_path('/bootstrap/cache'));

        return response()->json(['message' => 'Deployment successful']);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Deployment failed', 'message' => $e->getMessage()], 500);
    }
});
