<?php

namespace yoganandgopalan\LaravelSwagger\Formatters;

abstract class Formatter
{
    protected $docs;

    public function __construct($docs)
    {
        $this->docs = $docs;
    }

    abstract public function format();
}