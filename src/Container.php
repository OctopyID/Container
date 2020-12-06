<?php

namespace Octopy\Container;

use Closure;
use TypeError;
use LogicException;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;
use Octopy\Container\Exceptions\BindingResolutionException;

class Container
{
    /**
     * @var array
     */
    protected array $stacks = [];

    /**
     * @var array
     */
    protected array $aliases = [];

    /**
     * @var array
     */
    protected array $bindings = [];

    /**
     * @var array
     */
    protected array $instances = [];

    /**
     * @var array
     */
    protected array $parameters = [];

    /**
     * @param  string $abstract
     * @param  string $alias
     */
    public function alias(string $abstract, string $alias)
    {
        if ($alias === $abstract) {
            throw new LogicException(sprintf("[%s] is aliased to itself.", $abstract));
        }

        $this->aliases[$alias] = $abstract;
    }

    /**
     * @param  string $abstract
     * @return bool
     */
    public function resolved(string $abstract) : bool
    {
        if (isset($this->aliases[$abstract])) {
            $abstract = $this->getAlias($abstract);
        }

        return isset($this->resolved[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * @param  string $abstract
     * @return mixed
     */
    public function getAlias(string $abstract) : mixed
    {
        return $this->aliases[$abstract] ?? $abstract;
    }

    /**
     * @param  string $abstract
     * @param  null   $concrete
     */
    public function singleton(string $abstract, $concrete = null)
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * @param  string $abstract
     * @param  null   $concrete
     * @param  bool   $shared
     */
    public function bind(string $abstract, $concrete = null, bool $shared = false)
    {
        $this->dropStaleInstances($abstract);

        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        if (! $concrete instanceof Closure) {
            if (! is_string($concrete)) {
                throw new TypeError(self::class . '::bind(): Argument #2 ($concrete) must be of type Closure|string|null');
            }

            $concrete = $this->getClosure($abstract, $concrete);
        }

        $this->bindings[$abstract] = compact('concrete', 'shared');
    }

    /**
     * @param  string $abstract
     */
    protected function dropStaleInstances(string $abstract)
    {
        unset($this->instances[$abstract], $this->aliases[$abstract]);
    }

    /**
     * @param  string $abstract
     * @param  string $concrete
     * @return Closure
     */
    protected function getClosure(string $abstract, string $concrete) : Closure
    {
        return function ($container, $parameters = []) use ($abstract, $concrete) {
            if ($abstract == $concrete) {
                return $container->build($concrete);
            }

            return $container->resolve($concrete, $parameters);
        };
    }

    /**
     * @param  string $abstract
     * @param  mixed  $instance
     * @return mixed
     */
    public function instance(string $abstract, mixed $instance) : mixed
    {
        if (isset($this->aliases[$abstract])) {
            unset($this->aliases[$abstract]);
        }

        return $this->instances[$abstract] = $instance;
    }

    /**
     * @param  string $abstract
     * @param  array  $parameter
     * @return object
     * @throws BindingResolutionException|ReflectionException
     */
    public function make(string $abstract, array $parameter = []) : object
    {
        return $this->resolve($abstract, $parameter);
    }

    /**
     * @param  string $abstract
     * @param  array  $parameters
     * @return object
     * @throws BindingResolutionException|ReflectionException
     */
    public function resolve(string $abstract, array $parameters = []) : object
    {
        $abstract = $this->getAlias($abstract);

        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $this->parameters[] = $parameters;

        $object = $this->build($abstract);

        if ($this->isShared($abstract)) {
            $this->instances[$abstract] = $object;
        }

        array_pop($this->parameters);

        return $object;
    }

    /**
     * @param  mixed $abstract
     * @return object
     * @throws BindingResolutionException|ReflectionException
     */
    protected function build(mixed $abstract) : object
    {
        try {
            $reflector = new ReflectionClass($abstract);
        } catch (ReflectionException $exception) {
            throw new BindingResolutionException($exception->getMessage());
        }

        $this->stacks[] = $abstract;

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            array_pop($this->stacks);

            return new $abstract;
        }

        try {
            $instances = $this->resolveDependencies($constructor?->getParameters() ?? []);
        } catch (BindingResolutionException $exception) {
            array_pop($this->stacks);

            throw  $exception;
        }

        array_pop($this->stacks);

        return $reflector->newInstanceArgs($instances);
    }

    /**
     * @param  array $dependencies
     * @return array
     * @throws BindingResolutionException|ReflectionException
     */
    protected function resolveDependencies(array $dependencies) : array
    {
        $results = [];
        foreach ($dependencies as $dependency) {
            if ($this->hasParameterOverride($dependency)) {
                $results[] = $this->getLastParameterOverride($dependency);
            } else {
                $results[] = $this->make($dependency->getClass()?->getName());
            }
        }

        return $results;
    }

    /**
     * @param  ReflectionParameter $dependency
     * @return bool
     */
    protected function hasParameterOverride(ReflectionParameter $dependency) : bool
    {
        return array_key_exists(
            $dependency->name, $this->getLastParameterOverride()
        );
    }

    /**
     * @param  ReflectionParameter|null $dependency
     * @return mixed
     */
    protected function getLastParameterOverride(?ReflectionParameter $dependency = null) : mixed
    {
        $parameters = count($this->parameters) ? end($this->parameters) : [];
        if ($dependency?->name) {
            return $parameters[$dependency->name] ?? null;
        }

        return $parameters;
    }

    /**
     * @param  string $abstract
     * @return bool
     */
    public function isShared(string $abstract) : bool
    {
        return isset($this->instances[$abstract]) || (
            isset($this->bindings[$abstract]) ? $this->bindings[$abstract]['shared'] && $this->bindings[$abstract]['shared'] === true : false
            );
    }
}