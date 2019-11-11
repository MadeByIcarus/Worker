<?php


namespace IcarusTests\Unit;


require __DIR__ . '/../bootstrap.php';

use Contributte\EventDispatcher\EventDispatcher;
use Icarus\Worker\Exceptions\InvalidWorkerStateException;
use Icarus\Worker\IWorkerOutput;
use Icarus\Worker\Worker;
use Mockery;
use Tester\Assert;
use Tester\Environment;
use Tester\TestCase;


class WorkersTest extends TestCase
{

    public function testOk()
    {

    }



    public function testMissingParentCall()
    {
        $worker = new class(
            Mockery::mock(EventDispatcher::class)
        ) extends Worker
        {

            protected function checkRequirements(): void
            {
                // intentionally omitting parent method call
                //parent::checkRequirements();
            }



            /** Never call entityManager->flush() inside this method. */
            protected function workload(): void
            {
            }
        };

        $callback = function () use ($worker) {
            $worker->run();
        };

        Assert::exception($callback, InvalidWorkerStateException::class, '#parent::checkRequirements#');
    }



    public function testSingleRun()
    {
        $worker = new class(
            Mockery::mock(EventDispatcher::class)
        ) extends Worker
        {

            protected function workload(): void
            {
            }
        };

        $callback = function () use ($worker) {
            $worker->run();
        };

        Assert::noError($callback);
        Assert::exception($callback, InvalidWorkerStateException::class, "#more than once#");
    }



    public function testOutput()
    {
        $worker = new class(
            Mockery::mock(EventDispatcher::class)
        ) extends Worker
        {

            public function enableOutput()
            {
                $this->output = new class() implements IWorkerOutput
                {

                };
            }



            protected function workload(): void
            {
            }



            public function getOutput()
            {
                return $this->getUnnamedOutput();
            }
        };

        Assert::exception(function () use ($worker) {
            $worker->getOutput();
        }, InvalidWorkerStateException::class, "#not.*run#");

        Assert::exception(function () use ($worker) {
            $worker->run();
            $worker->getOutput();
        }, InvalidWorkerStateException::class, "#no output#");

        Assert::noError(function () use ($worker) {
            $worker->enableOutput();
            $worker->getOutput();
        });
    }



    protected function tearDown()
    {
        Mockery::close();
        Environment::$checkAssertions = false;
    }
}

(new WorkersTest())->run();