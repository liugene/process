<?php

namespace linkphp\process\drives;

use linkphp\process\Process;
use swoole_process;
use linkphp\Application;

class Swoole extends Process
{
    /**
     * @var $workers
     */
    private static $pids = [];

    public function start($callback)
    {
        for($i=0;$i<$this->process_max_num; ++$i){
            $process = new swoole_process($callback);
            $pid = $process->start();
            $this->process_max_num++;
            self::$pids[$pid] = $process;
            $this->writePid(static::$pid_file_path);
        }
//        $process = new swoole_process($callback);
//        $pid = $process->start();
//        if(!$this->writePid(static::$pid_file_path)){
//            $this->stop();
//        }
//        $this->process_max_num++;
//        self::$pids[$pid] = $process;

        while(1){
            $ret = $this->wait();
            if ($ret){// $ret 是个数组 code是进程退出状态码，
                $pid = $ret['pid'];
                dump(self::$pids);
                unset(self::$pids[$pid]);
                $this->process_max_num--;
                echo PHP_EOL."Worker Exit, PID=" . $pid . PHP_EOL;
            }else{
                break;
            }
        }
    }

    public static function stop()
    {
        if(file_exists(static::$pid_file_path)){
            unlink(static::$pid_file_path);
        }
        foreach (self::$pids as $pid => $process){
            self::kill($pid,SIGKILL);
        }
        self::kill(self::getMasterPid(static::$pid_file_path),SIGKILL);
        exit(0);
    }

    public function restart()
    {
        parent::restart(); // TODO: Change the autogenerated stub
    }

    public static function kill($pid, $signo = SIGTERM)
    {
        return swoole_process::kill($pid, $signo);
    }

    public function wait()
    {
        return swoole_process::wait();
    }

    public function alarm($interval_usec, $type = 0)
    {
        return swoole_process::alarm($interval_usec, $type);
    }

    public function signal($signo, $callback)
    {
        swoole_process::signal($signo, $callback);
    }

    public function daemon()
    {
        $this->daemon->setPidFilePath(self::$pid_file_path);
    }

    public function getPid()
    {
        return getmypid();
    }

    // 返回主进程 id
    public static function getMasterPid($pidFile)
    {
        if (!file_exists($pidFile)) {
            return false;
        }
        $pid = file_get_contents($pidFile);
        if (self::isRunning($pid)) {
            return $pid;
        }
        return false;
    }

    // 检查 PID 是否运行
    public static function isRunning($pid)
    {
        return self::kill($pid, 0);
    }

    // 设置进程名称
    public static function setName($name)
    {
        if (stristr(PHP_OS, 'DAR')) {
            return;
        }
        if (function_exists('cli_set_process_title')) {
            cli_set_process_title($name);
        } else if (function_exists('swoole_set_process_name')) {
            swoole_set_process_name($name);
        }
    }

}