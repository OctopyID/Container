<?php

namespace Tests;

use ReflectionException;
use Tests\ExampleClass\Foo;
use Tests\ExampleClass\Baz;
use Tests\ExampleClass\Bar;
use Tests\ExampleClass\Buz;
use Tests\ExampleClass\Fox;
use PHPUnit\Framework\TestCase;
use Octopy\Container\Container;

class ContainerTest extends TestCase
{
    /**
     * @var Container
     */
    private Container $container;

    /**
     * @return void
     */
    protected function setUp() : void
    {
        $this->container = new Container;
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testFooClassHasResolved() : void
    {
        $this->assertInstanceOf(Foo::class, $this->container->make(Foo::class));
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testDependencyHasResolved() : void
    {
        $foo = $this->container->make(Foo::class);

        $this->assertInstanceOf(Bar::class, $foo->getBar());
        $this->assertInstanceOf(Baz::class, $foo->getBaz());
        $this->assertInstanceOf(Buz::class, $foo->getBaz()?->getBuz());
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testDependencyOverrideHasResolved() : void
    {
        $this->assertTrue($this->container->make(Fox::class, ['fox' => true])?->fox);
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testSingletonBinding() : void
    {
        $this->container->singleton(Buz::class, function () {
            return new Buz;
        });

        $this->assertTrue($this->container->isShared(Buz::class));

        $this->container->make(Buz::class)->foo = 'foo';

        $this->assertSame('foo', $this->container->make(Buz::class)?->foo);
    }
}
