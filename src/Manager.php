<?php


namespace sockstack;


use sockstack\exceptions\ForkProcessFailException;

class Manager extends Process
{
    private $min = 5;

    private $max = 10;

    /**
     * @var array
     * [
     *      [
     *          "pid" => $pid,
     *          "worker" => $worker
     *      ]
     * ]
     */
    private $worker_pool = [];

    private $callable;

    public function __construct(\Closure $callable = null)
    {
        $this->pid = posix_getpid();
        $callable ?: $this->callable = $callable;
    }

    public function start()
    {
        $this->definedSignal();
        $this->execFork();
        $this->hangup();
    }

    private function forkChild()
    {
        if (count($this->worker_pool) >= $this->max) return;
        $pid = pcntl_fork();
        switch ($pid) {
            case -1:
                throw new ForkProcessFailException("fork process fail");
                break;

            case 0: //子进程
                $worker = (new Worker());
                $worker->setType("worker");
                $worker->createPipe();
                $worker->setCallable($this->callable);
                $worker->hangup();
                exit();

            default: //父进程
                $this->worker_pool[] = $pid;
        }
    }

    private function execFork()
    {
        for ($i = 0; $i < $this->min; $i++) {
            $this->forkChild();
        }
    }

    /**
     * @param \Closure $callable
     * @return Manager
     */
    public function setCallable($callable)
    {
        $this->callable = $callable;

        return $this;
    }

    /**
     * 进程挂起
     */
    public function hangup()
    {
        while (true) {
            pcntl_signal_dispatch();

            foreach ($this->worker_pool as $key => $pid) {
                if (pcntl_waitpid($pid, $status, WNOHANG) == -1) {
                   unset($this->worker_pool[$key]);
                   $this->forkChild();
                }
            }

            usleep(1 * 1000 * 1000);
        }
    }

    public function signalTermHander()
    {
        Log::debug("manager term...");
        $this->writePipe("reload");
    }
}