<?php

namespace TypechoApiPlugin\Http;

use TypechoApiPlugin\Exceptions\ApiException;
use TypechoApiPlugin\Extensions\ExtensionManager;

class Router
{
    private string $prefix;
    private array $routes = [];

    public function __construct(string $prefix)
    {
        $this->prefix = '/' . trim($prefix, '/');
        $this->registerCoreRoutes();
        $this->registerExtensionRoutes();
    }

    public function add(string $method, string $path, $handler, array $middleware = []): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware,
            'pattern' => $this->compilePattern($path),
        ];
    }

    public function match(Request $request): array
    {
        $method = $request->getMethod();
        $relativePath = $request->stripPrefix($this->prefix);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $relativePath, $matches)) {
                $params = [];
                foreach ($matches as $key => $value) {
                    if (!is_int($key)) {
                        $params[$key] = $value;
                    }
                }

                return [
                    'handler' => $route['handler'],
                    'params' => $params,
                    'middleware' => $route['middleware'],
                ];
            }
        }

        throw new ApiException('resource not found', 404);
    }

    private function compilePattern(string $path): string
    {
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_-]*)\}#', '(?P<$1>[^/]+)', $path);
        return '#^' . rtrim($pattern, '/') . '/?$#';
    }

    private function registerExtensionRoutes(): void
    {
        foreach (ExtensionManager::routes() as $route) {
            $this->add(
                $route['method'],
                $route['path'],
                $route['handler'],
                $route['middleware']
            );
        }
    }

    private function registerCoreRoutes(): void
    {
        $this->add('GET', '/posts', 'TypechoApiPlugin\Controllers\PostsController@index', ['rate']);
        $this->add('GET', '/post/{identifier}', 'TypechoApiPlugin\Controllers\PostsController@show', ['rate']);
        $this->add('GET', '/posts/archive', 'TypechoApiPlugin\Controllers\PostsController@archive', ['rate']);

        $this->add('GET', '/pages', 'TypechoApiPlugin\Controllers\PagesController@index', ['rate']);
        $this->add('GET', '/page/{slug}', 'TypechoApiPlugin\Controllers\PagesController@show', ['rate']);

        $this->add('GET', '/categories', 'TypechoApiPlugin\Controllers\TaxonomyController@categories', ['rate']);
        $this->add('GET', '/tags', 'TypechoApiPlugin\Controllers\TaxonomyController@tags', ['rate']);

        $this->add('GET', '/comments/{postId}', 'TypechoApiPlugin\Controllers\CommentsController@index', ['rate']);
        $this->add('POST', '/comment', 'TypechoApiPlugin\Controllers\CommentsController@store', ['rate']);

        $this->add('GET', '/site/info', 'TypechoApiPlugin\Controllers\SiteController@info', ['rate']);
        $this->add('GET', '/site/author/{author}', 'TypechoApiPlugin\Controllers\SiteController@author', ['rate']);

        $this->add('POST', '/auth/login', 'TypechoApiPlugin\Controllers\AuthController@login', ['rate']);
        $this->add('POST', '/auth/refresh', 'TypechoApiPlugin\Controllers\AuthController@refresh', ['rate']);

        $this->add('GET', '/user/info', 'TypechoApiPlugin\Controllers\UserController@info', ['token']);
        $this->add('POST', '/user/update', 'TypechoApiPlugin\Controllers\UserController@updateProfile', ['token']);
        $this->add('POST', '/user/password', 'TypechoApiPlugin\Controllers\UserController@updatePassword', ['token']);
        $this->add('GET', '/user/posts', 'TypechoApiPlugin\Controllers\UserController@posts', ['token']);

        $this->add('POST', '/admin/post/add', 'TypechoApiPlugin\Controllers\AdminPostsController@create', ['token', 'admin']);
        $this->add('POST', '/admin/post/update/{id}', 'TypechoApiPlugin\Controllers\AdminPostsController@update', ['token', 'admin']);
        $this->add('DELETE', '/admin/post/{id}', 'TypechoApiPlugin\Controllers\AdminPostsController@delete', ['token', 'admin']);
        $this->add('POST', '/admin/post/draft', 'TypechoApiPlugin\Controllers\AdminPostsController@saveDraft', ['token', 'admin']);
        $this->add('POST', '/admin/post/draft/{id}', 'TypechoApiPlugin\Controllers\AdminPostsController@updateDraft', ['token', 'admin']);
        $this->add('DELETE', '/admin/post/draft/{id}', 'TypechoApiPlugin\Controllers\AdminPostsController@deleteDraft', ['token', 'admin']);

        $this->add('POST', '/admin/comment/approve/{id}', 'TypechoApiPlugin\Controllers\AdminCommentsController@approve', ['token', 'admin']);
        $this->add('DELETE', '/admin/comment/{id}', 'TypechoApiPlugin\Controllers\AdminCommentsController@delete', ['token', 'admin']);

        $this->add('POST', '/admin/upload', 'TypechoApiPlugin\Controllers\UploadController@store', ['token', 'admin']);
        $this->add('GET', '/admin/statistics', 'TypechoApiPlugin\Controllers\StatisticsController@index', ['token', 'admin']);
        $this->add('POST', '/admin/config', 'TypechoApiPlugin\Controllers\SiteController@updateConfig', ['token', 'admin']);
    }
}
