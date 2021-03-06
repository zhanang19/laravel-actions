<?php

namespace Lorisleiva\Actions\Concerns;

use Illuminate\Http\Request;
use Illuminate\Routing\Router;

trait RunsAsController
{
    protected $request;

    public static function routes(Router $router)
    {
        //
    }

    public function runAsController(Request $request)
    {
        $this->runningAs = 'controller';
        $this->request = $request;

        $this->reset($request->user());
        $this->fill($this->getAttributesFromRequest($request));

        $result = $this->run();

        if (method_exists($this, 'response')) {
            return $this->response($result, $request);
        }

        if (method_exists($this, 'jsonResponse') && $request->wantsJson()) {
            return $this->jsonResponse($result, $request);
        }

        if (method_exists($this, 'htmlResponse') && ! $request->wantsJson()) {
            return $this->htmlResponse($result, $request);
        }

        return $result;
    }

    public function getAttributesFromRequest(Request $request)
    {
        return array_merge(
            $this->getAttributesFromRoute($request),
            $request->all()
        );
    }

    public function getAttributesFromRoute(Request $request)
    {
        $route = $request->route();

        return $route ? $route->parametersWithoutNulls() : [];
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getMiddleware()
    {
        $middleware = [];

        if (method_exists($this, 'controllerMiddleware')) {
            $middleware = $this->controllerMiddleware();
        } elseif (method_exists($this, 'middleware')) {
            $middleware = $this->middleware();
        }

        return array_map(function ($m) {
            return [
                'middleware' => $m,
                'options' => [],
            ];
        }, $middleware);
    }

    public function callAction($method, $parameters)
    {
        return $method === '__invoke'
            ? $this->runAsController(app(Request::class))
            : call_user_func_array([$this, $method], $parameters);
    }
}
