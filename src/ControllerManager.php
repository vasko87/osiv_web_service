<?php

namespace DbService;

use DbService\Controller\Base;
use DbService\Controller\Comparison;
use DbService\Controller\Db;
use DbService\Controller\Jira;
use DbService\Response\Response;
use DbService\Response\JsonResponse;

class ControllerManager
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function execute(Request $request): Response
    {
        $response = new JsonResponse(404);

        $defaultController = 'home';
        $defaultAction = 'index';

        $controller = $request->getControllerName();
        $action = $request->getActionName();

        $routes = [
            $defaultController => [
                'constructor' => function() {
                    return new Base();
                },
                'actions' => [
                    'GET:index' => 'actionIndex',
                ],
            ],
            'db' => [
                'constructor' => function() {
                    return new Db($this->config['db']);
                },
                'actions' => [
                    'POST:query' => 'actionQuery',
                    'POST:comparison' => 'actionComparison',
                    'POST:get_db_struct' => 'actionGetDbStruct',
                ],
            ],
            'jira' => [
                'constructor' => function() {
                    return new Jira($this->config['jira']);
                },
                'actions' => [
                    'POST:fetch' => 'actionFetch',
                ],
            ],
        ];

        $route = null;
        if (isset($routes[$controller])) {
            $route = $routes[$controller];
        } elseif ($request->method === 'GET' && empty($action)) {
            $route = $routes[$defaultController];
        }

        if (!$route) {
            return $response;
        }

        $route['actions']['GET:' . $defaultAction] = $routes[$defaultController]['actions']['GET:' . $defaultAction];

        $actionFromPost = $request->getActionFromPost();
        if (!empty($actionFromPost)) {
            $action = $actionFromPost;
        }

        $actionPart = $request->method . ':' . $action;

        if (!isset($route['actions'][$actionPart])) {
            if ($request->method === 'GET') {
                $action = $defaultAction;
                $actionPart = $request->method . ':' . $defaultAction;
            } else {
                return $response;
            }
        }

        $controllerInstance = $route['constructor']();
        $controllerMethod = $route['actions'][$actionPart];
        if (!method_exists($controllerInstance, $controllerMethod)) {
            return $response;
        }

        return $controllerInstance->$controllerMethod($request);
    }
}
