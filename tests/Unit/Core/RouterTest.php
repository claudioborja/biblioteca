<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use Core\Request;
use Core\Router;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    private function makeRouter(): Router
    {
        return new Router();
    }

    /**
     * Build a fake Request-like object by setting superglobals before
     * calling Request::capture(). We only need method + path.
     */
    private function fakeRequest(string $method, string $uri): Request
    {
        $_SERVER['REQUEST_METHOD'] = strtoupper($method);
        $_SERVER['REQUEST_URI']    = $uri;
        $_SERVER['SCRIPT_NAME']    = '/index.php';
        $_GET  = [];
        $_POST = [];
        return Request::capture();
    }

    // ── Static routes ────────────────────────────────────────────────────────

    #[Test]
    public function it_dispatches_a_simple_get_route(): void
    {
        $router = $this->makeRouter();
        $router->get('/catalog', ['Controllers\PublicController', 'catalog']);

        $match = $router->dispatch($this->fakeRequest('GET', '/catalog'));

        $this->assertNotNull($match);
        $this->assertSame(['Controllers\PublicController', 'catalog'], $match['handler']);
    }

    #[Test]
    public function it_returns_null_for_unregistered_route(): void
    {
        $router = $this->makeRouter();
        $router->get('/catalog', ['Controllers\PublicController', 'catalog']);

        $match = $router->dispatch($this->fakeRequest('GET', '/nonexistent'));

        $this->assertNull($match);
    }

    #[Test]
    public function it_does_not_match_wrong_http_method(): void
    {
        $router = $this->makeRouter();
        $router->get('/catalog', ['Controllers\PublicController', 'catalog']);

        $match = $router->dispatch($this->fakeRequest('POST', '/catalog'));

        $this->assertNull($match);
    }

    // ── Dynamic routes ───────────────────────────────────────────────────────

    #[Test]
    public function it_extracts_route_parameter(): void
    {
        $router = $this->makeRouter();
        $router->get('/catalog/{id}', ['Controllers\PublicController', 'resourceDetail']);

        $match = $router->dispatch($this->fakeRequest('GET', '/catalog/42'));

        $this->assertNotNull($match);
        $this->assertSame('42', $match['params']['id']);
    }

    #[Test]
    public function it_extracts_multiple_route_parameters(): void
    {
        $router = $this->makeRouter();
        $router->get('/admin/branches/{id}/edit', ['Controllers\BranchController', 'edit']);

        $match = $router->dispatch($this->fakeRequest('GET', '/admin/branches/5/edit'));

        $this->assertNotNull($match);
        $this->assertSame('5', $match['params']['id']);
    }

    // ── Route groups ─────────────────────────────────────────────────────────

    #[Test]
    public function it_applies_prefix_from_group(): void
    {
        $router = $this->makeRouter();
        $router->group(['prefix' => '/admin'], function (Router $r): void {
            $r->get('/branches', ['Controllers\BranchController', 'index']);
        });

        $match = $router->dispatch($this->fakeRequest('GET', '/admin/branches'));

        $this->assertNotNull($match);
    }

    #[Test]
    public function it_supports_nested_groups(): void
    {
        $router = $this->makeRouter();
        $router->group(['prefix' => '/admin'], function (Router $r): void {
            $r->group(['prefix' => '/resources'], function (Router $r): void {
                $r->get('/create', ['Controllers\ResourceController', 'create']);
            });
        });

        $match = $router->dispatch($this->fakeRequest('GET', '/admin/resources/create'));

        $this->assertNotNull($match);
    }

    // ── Named routes ─────────────────────────────────────────────────────────

    #[Test]
    public function it_registers_named_routes(): void
    {
        $router = $this->makeRouter();
        $router->get('/catalog', ['Controllers\PublicController', 'catalog'], 'catalog.index');

        $this->assertSame('/catalog', $router->url('catalog.index'));
    }

    #[Test]
    public function it_returns_empty_string_for_unknown_named_route(): void
    {
        $router = $this->makeRouter();

        $this->assertSame('', $router->url('nonexistent.route'));
    }

    // ── POST / PUT / DELETE ───────────────────────────────────────────────────

    #[Test]
    public function it_dispatches_post_route(): void
    {
        $router = $this->makeRouter();
        $router->post('/admin/branches', ['Controllers\BranchController', 'store']);

        $match = $router->dispatch($this->fakeRequest('POST', '/admin/branches'));

        $this->assertNotNull($match);
    }

    #[Test]
    public function it_supports_method_override_via_put(): void
    {
        $router = $this->makeRouter();
        $router->put('/admin/branches/{id}', ['Controllers\BranchController', 'update']);

        // Simulate _method=PUT override (Request class handles this internally)
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI']    = '/admin/branches/3';
        $_SERVER['SCRIPT_NAME']    = '/index.php';
        $_POST = ['_method' => 'PUT'];
        $request = Request::capture();

        $match = $router->dispatch($request);
        $this->assertNotNull($match);
        $this->assertSame('3', $match['params']['id']);
    }
}
