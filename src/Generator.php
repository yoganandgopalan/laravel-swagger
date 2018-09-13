<?php

namespace yoganandgopalan\LaravelSwagger;

use ReflectionMethod;
use Illuminate\Support\Str;
use Illuminate\Routing\Route;
use Illuminate\Foundation\Http\FormRequest;

class Generator
{
    protected $config;

    protected $routeFilter;

    protected $auth;

    protected $host;

    protected $docs;

    protected $uri;

    protected $originalUri;

    protected $method;

    protected $action;

    public function __construct($config, $routeFilter = null, $auth = null, $host = null)
    {
        $this->config = $config;
        $this->routeFilter = $routeFilter;
        $this->auth = $auth;
        $this->host = $host;
    }

    public function generate()
    {
        $this->docs = $this->getBaseInfo();

        foreach ($this->getAppRoutes() as $route) {
            $this->originalUri = $uri = $this->getRouteUri($route);
            $this->uri = strip_optional_char($uri);

            if ($this->routeFilter && !preg_match('/^' . preg_quote($this->routeFilter, '/') . '/', $this->uri)) {
                continue;
            }   

            $this->action = $route->getAction('uses');
            $methods = $route->methods();

            if (!isset($this->docs['paths'][$this->uri])) {
                $this->docs['paths'][$this->uri] = [];
            }

            
            foreach ($methods as $method) {
                $this->method = strtolower($method);
                
                if (in_array($this->method, $this->config['ignoredMethods'])) continue;
                
                $this->generatePath();

                $this->addSummary($route->getActionName());

                $this->addTags($route->getAction());

                $this->addAuthParameters($route->middleware());
            }
        }

        return $this->docs;
    }

    protected function getBaseInfo()
    {
        $baseInfo = [
            'swagger' => '2.0',
            'info' => [
                'title' => $this->config['title'],
                'description' => $this->config['description'],
                'version' => $this->config['appVersion'],
            ],
            'host' => !empty($this->host) ? $this->host : $this->config['host'],
            'basePath' => $this->config['basePath'],
        ];

        if (!empty($this->config['schemes'])) {
            $baseInfo['schemes'] = $this->config['schemes'];
        }

        if (!empty($this->config['consumes'])) {
            $baseInfo['consumes'] = $this->config['consumes'];
        }

        if (!empty($this->config['produces'])) {
            $baseInfo['produces'] = $this->config['produces'];
        }

        if(!empty($this->auth)) {
            switch ($this->auth) {
                case 'jwt':
                    $baseInfo['securityDefinitions'] = [
                        'api_key' => [
                            'type' => 'apiKey',
                            'name' => 'Authorization',
                            'in' => 'header'
                        ]
                    ];
                    break;
                
                default:
                    $baseInfo['securityDefinitions'] = [];
                    break;
            }
        }

        $baseInfo['paths'] = [];

        return $baseInfo;
    }

    protected function getAppRoutes()
    {
        return app('router')->getRoutes();
    }

    protected function getRouteUri(Route $route)
    {
        $uri = $route->uri();

        if (!starts_with($uri, '/')) {
            $uri = '/' . $uri;
        }

        return $uri;
    }

    protected function generatePath()
    {
        $methodDescription = strtoupper($this->method);

        $this->docs['paths'][$this->uri][$this->method] = [
            'description' => "$methodDescription {$this->uri}",
            'summary' => '',
            'tags' => [],
            'responses' => [
                '200' => [
                    'description' => 'OK'
                ]
            ],
        ];

        $this->addActionParameters();
    }

    protected function addActionParameters()
    {
        $rules = $this->getFormRules() ?: [];

        $parameters = (new Parameters\PathParameterGenerator($this->originalUri))->getParameters();

        if (!empty($rules)) {
            $parameterGenerator = $this->getParameterGenerator($rules);

            $parameters = array_merge($parameters, $parameterGenerator->getParameters());
        }

        if (!empty($parameters)) {
            $this->docs['paths'][$this->uri][$this->method]['parameters'] = $parameters;
        }
    }

    protected function getFormRules()
    {
        if (!is_string($this->action)) return false;

        $parsedAction = Str::parseCallback($this->action);

        $reflector = (new ReflectionMethod($parsedAction[0], $parsedAction[1]));
        $parameters = $reflector->getParameters();
        $docComment = $reflector->getDocComment();

        if($docComment) {
            $this->addDescription($docComment);
        }

        foreach ($parameters as $parameter) {
            $class = (string) $parameter->getType();

            if (is_subclass_of($class, FormRequest::class)) {
                return (new $class)->rules();
            }
        }
    }

    protected function addDescription($docComment)
    {
        $docComment = $this->getDescription($docComment);
        $this->docs['paths'][$this->uri][$this->method]['description'] = $docComment;
    }

    protected function getDescription($docComment)
    {
        $docCommentParsed = trim(str_replace(array('/', '*'), '', substr($docComment, 0, strpos($docComment, '@'))));
        $docComment = trim(preg_replace('/\s+/', ' ', $docCommentParsed));

        return $docComment;
    }

    protected function addSummary($actionName)
    {
        $actionName = $this->getActionName($actionName);
        $this->docs['paths'][$this->uri][$this->method]['summary'] = $actionName;
    }

    protected function getActionName($actionName)
    {
        $actionNameSubString = substr($actionName, strpos($actionName, '@')+1);
        $actionNameArray = preg_split('/(?=[A-Z])/', ucfirst($actionNameSubString));
        $actionName = trim(implode(' ', $actionNameArray));

        return $actionName;
    }

    protected function addTags($controllerArray)
    {
        $tagName = $this->getControllerName($controllerArray);
        $this->docs['paths'][$this->uri][$this->method]['tags'][] = $tagName;
    }

    protected function getControllerName($controllerArray)
    {
        $namespaceReplaced = str_replace($controllerArray['namespace']. '\\', '', $controllerArray['controller']);
        $actionNameReplaced = substr($namespaceReplaced, 0, strpos($namespaceReplaced, '@'));
        $controllerReplaced = str_replace('Controller', '', $actionNameReplaced);
        $controllerNameArray = preg_split('/(?=[A-Z])/', $controllerReplaced);
        $controllerName = trim(implode(' ', $controllerNameArray));

        return $controllerName;
    }

    protected function addAuthParameters($middlewares)
    {
        if(!empty($this->auth)) {
            switch ($this->auth) {
                case 'jwt':
                    $hasAuth = array_filter($middlewares, function ($var) { 
                        return (strpos($var, 'jwt') > -1); 
                    });
                    if($hasAuth) {
                        $this->docs['paths'][$this->uri][$this->method]['security'] = [
                            'api_key' => []
                        ];
                    }
                    break;
            }
        }
    }

    protected function getParameterGenerator($rules)
    {
        switch ($this->method) {
            case 'post':
            case 'put':
            case 'patch':
                return new Parameters\BodyParameterGenerator($rules);
            default:
                return new Parameters\QueryParameterGenerator($rules);
        }
    }
}