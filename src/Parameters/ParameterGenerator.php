<?php

namespace yoganandgopalan\LaravelSwagger\Parameters;

interface ParameterGenerator
{
    public function getParameters();

    public function getParamLocation();
}