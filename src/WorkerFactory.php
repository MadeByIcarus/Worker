<?php


namespace Icarus\Worker;


use Nette\DI\Container;


class WorkerFactory
{

    /**
     * @var Container
     */
    private $container;



    public function __construct(Container $container)
    {
        $this->container = $container;
    }



    public function createWorker(string $class, IWorkerInput $arguments): Worker
    {
        $reflection = new \ReflectionClass($class);

        $parameters = $reflection->getConstructor()->getParameters();
        $parameterCount = count($parameters);
        $args = [$arguments];

        for ($i = 1; $i < $parameterCount; $i++) {
            $parameter = $parameters[$i];
            $name = $parameter->getClass()->getName();
            $args[] = $this->container->getByType($name);
        }

        return new $class(...$args);
    }
}