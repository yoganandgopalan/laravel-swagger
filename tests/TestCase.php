<?php

namespace yoganandgopalan\LaravelSwagger\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return ['yoganandgopalan\LaravelSwagger\SwaggerServiceProvider'];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['router']->get('/users', 'yoganandgopalan\\LaravelSwagger\\Tests\\Stubs\\Controllers\\UserController@index');
        $app['router']->get('/users/{id}', 'yoganandgopalan\\LaravelSwagger\\Tests\\Stubs\\Controllers\\UserController@show');
        $app['router']->post('/users', 'yoganandgopalan\\LaravelSwagger\\Tests\\Stubs\\Controllers\\UserController@store');
        $app['router']->get('/api', 'yoganandgopalan\\LaravelSwagger\\Tests\\Stubs\\Controllers\\ApiController@index');
        $app['router']->put('/api/store', 'yoganandgopalan\\LaravelSwagger\\Tests\\Stubs\\Controllers\\ApiController@store');
    }
}
