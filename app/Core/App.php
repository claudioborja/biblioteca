<?php
// app/Core/App.php — Bootstrap y ejecución de la aplicación
declare(strict_types=1);

namespace Core;

final class App
{
    private Router $router;
    private Container $container;

    public function __construct()
    {
        $this->container = new Container();
        $this->router = new Router();

        $this->registerServices();
        $this->loadRoutes();
    }

    public function run(): void
    {
        $request = Request::capture();

        // Track visit
        // TODO: implement VisitTracker

        // Dispatch route
        $match = $this->router->dispatch($request);

        if ($match === null) {
            Response::abort(404);
        }

        ['handler' => $handler, 'params' => $params, 'middleware' => $middleware] = $match;

        // Run middleware pipeline
        $pipeline = new MiddlewarePipeline($this->container);
        $pipeline->through($middleware);

        $response = $pipeline->run($request, function (Request $req) use ($handler, $params) {
            return $this->callHandler($handler, $req, $params);
        });

        if ($response instanceof Response) {
            $response->send();
        }
    }

    private function registerServices(): void
    {
        $this->container->singleton(Database::class, function () {
            $config = require BASE_PATH . '/config/database.php';
            return new Database($config);
        });

        $this->container->singleton(Session::class, function () {
            return new Session();
        });

        $this->container->singleton(View::class, function () {
            return new View(BASE_PATH . '/views');
        });
    }

    private function loadRoutes(): void
    {
        $router = $this->router;
        require BASE_PATH . '/config/routes.php';
    }

    private function callHandler(array|callable $handler, Request $request, array $params): mixed
    {
        if (is_callable($handler)) {
            return $handler($request, ...$params);
        }

        [$controllerClass, $method] = $handler;
        $controller = $this->container->make($controllerClass);

        return $controller->$method($request, ...$params);
    }

    public function container(): Container
    {
        return $this->container;
    }
}
