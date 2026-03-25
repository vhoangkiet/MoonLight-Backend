<?php

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Passport;
use League\OAuth2\Server\AuthorizationServer;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;

require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

// Explicitly enable here for test
Passport::enablePasswordGrant();

$params = [
    'grant_type' => 'password',
    'client_id' => config('auth.passport.client_id'),
    'client_secret' => config('auth.passport.client_secret'),
    'username' => 'admin@example.com',
    'password' => 'password',
    'scope' => '',
];

echo 'Testing token issuance with params: '.json_encode($params, JSON_PRETTY_PRINT)."\n";

// Try Direct respondToAccessTokenRequest Call
try {
    $psrFactory = new PsrHttpFactory(
        new HttpFactory,
        new HttpFactory,
        new HttpFactory,
        new HttpFactory
    );

    $psrRequest = $psrFactory->createRequest(Request::create('/oauth/token', 'POST', $params));
    $psrResponse = app(AuthorizationServer::class)->respondToAccessTokenRequest(
        $psrRequest,
        new Response
    );

    echo "--- Direct respondToAccessTokenRequest Call ---\n";
    echo 'Status: '.$psrResponse->getStatusCode()."\n";
    echo 'Body: '.$psrResponse->getBody()."\n";
} catch (Exception $e) {
    echo 'Direct respondToAccessTokenRequest failed: '.$e->getMessage()."\n";
}

// Try Direct Controller Call

// Try Internal Dispatch
$requestDispatch = Request::create('/oauth/token', 'POST', $params);
$requestDispatch->headers->set('Accept', 'application/json');
$requestDispatch->headers->set('Content-Type', 'application/x-www-form-urlencoded');

$responseDispatch = Route::dispatch($requestDispatch);

echo "--- Internal Route::dispatch ---\n";
echo 'Status: '.$responseDispatch->getStatusCode()."\n";
echo 'Content: '.$responseDispatch->getContent()."\n";

// Try Http Call (Server must be alive)
try {
    $httpResponse = Http::asForm()->post('http://localhost:8000/oauth/token', $params);
    echo "--- HTTP Call (localhost:8000) ---\n";
    echo 'Status: '.$httpResponse->status()."\n";
    echo 'Content: '.$httpResponse->body()."\n";
} catch (Exception $e) {
    echo 'HTTP Call failed: '.$e->getMessage()."\n";
}
