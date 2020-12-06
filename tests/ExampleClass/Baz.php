<?php

namespace Tests\ExampleClass;

class Baz
{
    /**
     * @var Buz
     */
    protected Buz $buz;

    /**
     * Baz constructor.
     * @param  Buz $buz
     */
    public function __construct(Buz $buz)
    {
        $this->buz = $buz;
    }

    /**
     * @return Buz
     */
    public function getBuz() : Buz
    {
        return $this->buz;
    }
}