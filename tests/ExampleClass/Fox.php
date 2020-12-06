<?php

namespace Tests\ExampleClass;

class Fox
{
    /**
     * @var bool
     */
    public bool $fox;

    /**
     * Bar constructor.
     * @param  bool $fox
     */
    public function __construct(bool $fox)
    {
        $this->fox = $fox;
    }
}