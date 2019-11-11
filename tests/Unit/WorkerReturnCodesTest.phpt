<?php


namespace IcarusTests\Unit;


require __DIR__ . '/../bootstrap.php';

use Contributte\EventDispatcher\EventDispatcher;
use Icarus\Worker\Worker;
use Mockery;
use Tester\Assert;
use Tester\Environment;
use Tester\TestCase;


class WorkerReturnCodesTest extends TestCase
{

    public function testSuccessfulWorker()
    {
        $worker = new class(
            Mockery::mock(EventDispatcher::class)
        ) extends Worker
        {

            protected function workload(): void
            {
            }
        };

        Assert::noError(function () use ($worker) {
            $worker->run();
        });
        Assert::same(Worker::RETURN_CODE_SUCCESS, $worker->getReturnCode());
    }



    public function testFailedWorker()
    {
        $failedWorker = new class(
            Mockery::mock(EventDispatcher::class)
        ) extends Worker
        {

            protected function workload(): void
            {
                $this->fail(new \Exception("Failed."));
            }
        };

        Assert::exception(function () use ($failedWorker) {
            $failedWorker->run();
        }, \Exception::class, "Failed.");
        Assert::same(Worker::RETURN_CODE_ERROR, $failedWorker->getReturnCode());
    }



    public function testCustomCodeWorker()
    {
        $worker = new class(
            Mockery::mock(EventDispatcher::class)
        ) extends Worker
        {

            protected function workload(): void
            {
                $this->returnCode = 100;
            }
        };

        Assert::noError(function () use ($worker) {
            $worker->run();
        });
        Assert::same(100, $worker->getReturnCode());
    }



    public function testCustomCodeFailedWorker()
    {
        $worker = new class(
            Mockery::mock(EventDispatcher::class)
        ) extends Worker
        {

            protected function workload(): void
            {
                $this->returnCode = 100;
                $this->fail(new \Exception("Failed."));
            }
        };

        Assert::exception(function () use ($worker) {
            $worker->run();
        }, \Exception::class, "Failed.");
        Assert::same(100, $worker->getReturnCode());
    }



    protected function tearDown()
    {
        Mockery::close();
        Environment::$checkAssertions = false;
    }
}

(new WorkerReturnCodesTest())->run();