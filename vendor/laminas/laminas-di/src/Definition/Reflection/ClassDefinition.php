<?php

declare(strict_types=1);

namespace Laminas\Di\Definition\Reflection;

use Laminas\Di\Definition\ClassDefinitionInterface;
use Laminas\Di\Definition\ParameterInterface;
use ReflectionClass;
use ReflectionParameter;

use function uasort;

class ClassDefinition implements ClassDefinitionInterface
{
    private ReflectionClass $reflection;

    /** @var array<string, Parameter> */
    private ?array $parameters = null;

    /** @var list<class-string> */
    private ?array $supertypes = null;

    /**
     * @param class-string|ReflectionClass $class
     */
    public function __construct($class)
    {
        if (! $class instanceof ReflectionClass) {
            $class = new ReflectionClass($class);
        }

        $this->reflection = $class;
    }

    private function reflectSupertypes(): void
    {
        $this->supertypes = [];
        $class            = $this->reflection;

        while ($class = $class->getParentClass()) {
            $this->supertypes[] = $class->name;
        }
    }

    public function getReflection(): ReflectionClass
    {
        return $this->reflection;
    }

    /**
     * @return list<class-string>
     */
    public function getSupertypes(): array
    {
        if ($this->supertypes === null) {
            $this->reflectSupertypes();
        }

        return $this->supertypes;
    }

    /**
     * @return string[]
     */
    public function getInterfaces(): array
    {
        return $this->reflection->getInterfaceNames();
    }

    private function reflectParameters(): void
    {
        $this->parameters = [];

        if (! $this->reflection->hasMethod('__construct')) {
            return;
        }

        $method = $this->reflection->getMethod('__construct');

        /** @var ReflectionParameter $parameterReflection */
        foreach ($method->getParameters() as $parameterReflection) {
            $parameter                               = new Parameter($parameterReflection);
            $this->parameters[$parameter->getName()] = $parameter;
        }

        uasort(
            $this->parameters,
            fn(ParameterInterface $a, ParameterInterface $b) => $a->getPosition() - $b->getPosition()
        );
    }

    /**
     * @return array<string, Parameter>
     */
    public function getParameters(): array
    {
        if ($this->parameters === null) {
            $this->reflectParameters();
        }

        return $this->parameters;
    }
}
