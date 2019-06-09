<?php


namespace sockstack;


abstract class Process
{
    private $type;

    protected $pid;

    private $pipe_file = "/tmp/sockstack.pipe";

    private $mode = 0777;

    private $signals = [
        SIGCHLD,
        SIGTERM,
        SIGUSR1,
        SIGUSR2,
    ];

    public function __construct()
    {
        $this->pid = posix_getpid();
    }

    abstract public function hangup();

    public function stop()
    {

    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     * @return Process
     */
    public function setType($type)
    {
        $this->type = $type;
        if ($this->type) {
            cli_set_process_title($this->getType());
        }
        return $this;
    }

    public function signalKillHander()
    {
        echo "kill";
    }

    public function signalTermHander()
    {
        echo "term";
    }

    public function signalUsr1Hander()
    {
        echo "usr1";
    }

    public function signalUsr2Hander()
    {
        echo "usr2";
    }

    public function execSignal($signal = 0) {
        switch ($signal) {
            case SIGCHLD:
                $this->signalKillHander();
                break;
            case SIGTERM:
                $this->signalTermHander();
                break;
            case SIGUSR1:
                $this->signalUsr1Hander();
                break;
            default:
                $this->signalUsr2Hander();
                break;
        }
    }

    public function definedSignal() {
        foreach ($this->signals as $signal) {
            pcntl_signal($signal, [$this, "execSignal"]);
        }
    }

    public function createPipe()
    {
        $pipe_name = $this->pipe_file . posix_getpid();
        if (!file_exists($pipe_name)) {
            if (!is_dir(dirname($pipe_name))) {
                mkdir(dirname($pipe_name), $this->mode);
            }
            if (!posix_mkfifo($pipe_name, $this->mode)) {
                Log::error("create ${pipe_name} fail");
                touch($pipe_name);
            }
            chmod($this->pipe_file, $this->mode);
            Log::debug( "create " . $pipe_name);
        }
    }

    public function writePipe($string)
    {
        $pipe_name = $this->pipe_file . posix_getpid();
        Log::debug("start write " . $pipe_name);
        $fd = fopen($pipe_name, "w+");

        fwrite($fd, $string);
        fclose($fd);
        Log::debug("write ${string} " . $pipe_name);

        if (!file_exists($pipe_name)) Log::debug("${pipe_name} is not found");

        do {
            $fd = fopen($pipe_name, "r+");
        } while(!$fd);
        stream_set_blocking($fd, false);
        $fread = fread($fd, 1024);
        if ($fread) {
            Log::debug("read " . $fread);
        } else {
            Log::debug("read fail");
        }
    }

    public function readPipe()
    {
        $pipe_name = $this->pipe_file . posix_getpid();
        $fd = fopen($pipe_name, "r+");
        stream_set_blocking($fd, false);

        $string = fread($fd, 1024);

        fclose($fd);

        return $string;
    }

    public function clearPipe()
    {
        if (file_exists($this->pipe_file)) {
            unlink($this->pipe_file);
        }
    }
}