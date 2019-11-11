<?php
declare(strict_types=1);


namespace Icarus\Worker;


use Icarus\Worker\Exceptions\InvalidWorkerStateException;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tracy\Debugger;


abstract class Worker
{

    const RETURN_CODE_SUCCESS = 0;
    const RETURN_CODE_ERROR = -1;

    /** @var bool */
    private $checkRequirementsCalledOnParent = false;

    /** @var \Exception|null */
    protected $exception;

    /** @var IWorkerOutput|null */
    protected $output;

    /** @var int */
    protected $returnCode;

    /** @var bool */
    private $didRun = false;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;



    public function __construct(
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->eventDispatcher = $eventDispatcher;
    }



    public function __destruct()
    {
        if (!$this->didRun) {
            throw new \RuntimeException("You forgot to call the run() method on the instance of " . static::class);
        }
    }



    protected function checkRequirements(): void
    {
        if (!is_null($this->returnCode)) {
            throw new InvalidWorkerStateException("Worker instance cannot be run more than once. Create a new instance.");
        }

        $this->checkRequirementsCalledOnParent = true;
    }



    /** Never call entityManager->flush() inside this method. */
    abstract protected function workload(): void;



    public final function run(): void
    {
        try {
            $this->didRun = true;

            $this->checkRequirements();

            if (!$this->checkRequirementsCalledOnParent) {
                throw new InvalidWorkerStateException("Did not call parent::checkRequirements().");
            }

            $this->workload();

            if (is_null($this->returnCode)) { // workload did not set return code
                $this->returnCode = $this->didFail() ? self::RETURN_CODE_ERROR : self::RETURN_CODE_SUCCESS;
            }

            $this->finish();
        } catch (\Throwable $e) {
            Debugger::log($e, Debugger::ERROR);
            throw $e;
        }

        if ($this->didFail()) {
            throw $this->getException();
        }
    }



    protected final function finish()
    {
        if ($this->didFail() || $this->returnCode === self::RETURN_CODE_ERROR) {
            return;
        }

        $this->finishBody();
    }



    protected function finishBody()
    {

    }



    protected function dispatchEvent(Event $event)
    {
        $this->eventDispatcher->dispatch($event, get_class($event));
    }



    public function getReturnCode(): int
    {
        return $this->returnCode;
    }



    public function didFail(): bool
    {
        return !is_null($this->exception);
    }



    public function fail(\Exception $exception): void
    {
        $this->exception = $exception;
    }



    public function getException(): ?\Exception
    {
        return $this->exception;
    }



    protected function getUnnamedOutput(): IWorkerOutput
    {
        if (!$this->didRun) {
            throw new InvalidWorkerStateException("This process has not been run yet.");
        }
        if (is_null($this->output)) {
            throw new InvalidWorkerStateException("This process has no output.");
        }

        return $this->output;
    }
}


