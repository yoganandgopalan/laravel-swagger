<?php

namespace yoganandgopala\LaravelSwagger\Tests\Stubs\Controllers;

use Illuminate\Routing\Controller;
use yoganandgopala\LaravelSwagger\Tests\Stubs\Requests\UserShowRequest;
use yoganandgopala\LaravelSwagger\Tests\Stubs\Requests\UserStoreRequest;

class UserController extends Controller
{
    public function index()
    {
        return json_encode([['first_name' => 'John'], ['first_name' => 'Jack']]);
    }

    public function show(UserShowRequest $request, $id)
    {
        return json_encode(['first_name' => 'John']);
    }

    public function store(UserStoreRequest $request)
    {
        return json_encode($request->all());
    }
}
