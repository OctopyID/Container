<?php

namespace Tests\ExampleClass;

class Foo
{
    /**
     * @var Bar
     */
    private Bar $bar;

    /**
     * @var Baz
     */
    private Baz $baz;

    /**
     * Foo constructor.
     * @param  Bar $bar
     * @param  Baz $baz
     */
    public function __construct(Bar $bar, Baz $baz)
    {
        $this->bar = $bar;
        $this->baz = $baz;
    }

    /**
     * @return Bar
     */
    public function getBar() : Bar
    {
        return $this->bar;
    }

    /**
     * @return Baz
     */
    public function getBaz() : Baz
    {
        return $this->baz;
    }
}