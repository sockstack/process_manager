<?php


namespace sockstack;


class Worker extends Process
{
    private $callable;

    private $exec_time = .01;

    public function hangup()
    {
        while (true) {
            pcntl_signal_dispatch();
            $re = $this->readPipe();
            if ($re) {
                Log::debug($re);
            }
            $this->run();
            usleep($this->exec_time * 1000 * 1000);
        }
    }

    /**
     * @param mixed $callable
     * @return Worker
     */
    public function setCallable($callable)
    {
        $this->callable = $callable;
        return $this;
    }

    final public function run($arg = null)
    {
        if ($this->callable) {
            call_user_func($this->callable, $arg);
        }
    }
}