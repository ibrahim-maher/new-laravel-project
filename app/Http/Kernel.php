protected $routeMiddleware = [
    // Other middleware...
    'role' => \App\Http\Middleware\CheckRole::class,
];