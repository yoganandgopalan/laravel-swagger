<?php

namespace yoganandgopala\LaravelSwagger\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return ['yoganandgopala\LaravelSwagger\SwaggerServiceProvider'];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['router']->get('/users', 'yoganandgopala\\LaravelSwagger\\Tests\\Stubs\\Controllers\\UserController@index');
        $app['router']->get('/users/{id}', 'yoganandgopala\\LaravelSwagger\\Tests\\Stubs\\Controllers\\UserController@show');
        $app['router']->post('/users', 'yoganandgopala\\LaravelSwagger\\Tests\\Stubs\\Controllers\\UserController@store');
        $app['router']->get('/api', 'yoganandgopala\\LaravelSwagger\\Tests\\Stubs\\Controllers\\ApiController@index');
        $app['router']->put('/api/store', 'yoganandgopala\\LaravelSwagger\\Tests\\Stubs\\Controllers\\ApiController@store');
    }
}
