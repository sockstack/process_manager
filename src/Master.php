<?php


namespace sockstack;


class Master extends Process
{
    private $manager_pid;
    private $manager = null;

    private $callable;

    public function __construct(\Closure $callable = null)
    {
        $this->callable = $callable;
    }

    public function start() {
        $this->welcome();
        $this->setType("master");
        $this->definedSignal();
        $this->fork();
        $this->hangup();
    }

    private function fork()
    {
        $pid = pcntl_fork();
        switch ($pid) {
            case -1:
                break;
            case 0:
                $this->manager = new Manager();
                $this->manager->setType("manager");
                $this->manager->createPipe();
                $this->manager->setCallable($this->callable)->start();
                break;
            default:
                $this->manager_pid = $pid;
        }
    }

    public function hangup()
    {
        while (true) {
            pcntl_signal_dispatch();
            pcntl_waitpid($this->manager_pid, $status, WNOHANG);
            usleep(0.5 * 1000 * 1000);
        }
    }

    /**
     * @param \Closure $callable
     * @return Master
     */
    public function setCallable($callable)
    {
        $this->callable = $callable;

        return $this;
    }

    public function welcome()
	{
		$welcome = <<<WELCOME
\033[36m
                   _          _                _    
                  | |        | |              | |   
 ___   ___    ___ | | __ ___ | |_  __ _   ___ | | __
/ __| / _ \  / __|| |/ // __|| __|/ _` | / __|| |/ /
\__ \| (_) || (__ |   < \__ \| |_| (_| || (__ |   < 
|___/ \___/  \___||_|\_\|___/ \__|\__,_| \___||_|\_\.sockstack.cn
-----------------------------------------------------------------
An object-oriented multi process manager for PHP

Version: 0.5.0
-----------------------------------------------------------------
\033[0m
WELCOME;

		echo $welcome;
	}
}