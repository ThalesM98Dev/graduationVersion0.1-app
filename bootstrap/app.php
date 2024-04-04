<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\DriverMiddleware;
use App\Http\Middleware\ExceptionsHandler;
use App\Http\Middleware\ShipmentEmployeeMiddleware;
use App\Http\Middleware\TravelTripsEmployeeMiddleware;
use App\Http\Middleware\UniversityTripsEmployeeMiddleware;
use App\Http\Middleware\UserMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'exception.handler' => ExceptionsHandler::class,
            'user' => UserMiddleware::class,
            'driver' => DriverMiddleware::class,
            'admin' => AdminMiddleware::class,
            'shipment.employee' => ShipmentEmployeeMiddleware::class,
            'travel.employee' => TravelTripsEmployeeMiddleware::class,
            'university.trips.employee' => UniversityTripsEmployeeMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
