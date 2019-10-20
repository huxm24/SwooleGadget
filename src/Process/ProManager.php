<?php
namespace SwooleGadge\Process;

/**
 * Class ProManager
 *
 * @package SwooleGadge\Process
 */
class ProManager
{
    /**
     * 功能：存放进程标识
     *
     * @var array
     * */
    protected $Mworkers;
    /**
     * 功能：保持启动进程数量
     *
     * @var integer
     */
    protected $WorkMaxNum = 8;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 功   能: 进程启动入口方法
     * 修改日期: 2019/10/09
     * 作   者: xiaoming.hu
     * @return void
     */
    public function handle()
    {
        $count = 0;
        while (true) {
            try {
                $count++;
                $ret = \Swoole\Process::wait(false);
                if ($ret) {
                    $this->rebootProcess($ret);
                }

                if ($count >= 5) {
                    $count = 0;
                    //如果子进程数小于上限则继续创建进程
                    if (count($this->Mworkers) < $this->WorkMaxNum) {
                        $this->rebootProcess(null);
                    }
                }
                usleep(20000);
            } catch (\Exception $ex) {
                // TODO 错误处理逻辑
            }
        }
    }

    /**
     * 功   能: 重新启动或者新建进程
     * 修改日期: 2019/10/09
     * 作   者: xiaoming.hu
     * @param string $ret 如果非空则表示需要重启老进程，空则需要新建进程
     * @return void
     */
    private function rebootProcess($ret)
    {
        if (!empty($ret)) {
            //进程回收进入，首先获得截止的进程名称
            $prcname = array_search($ret['pid'], $this->Mworkers);
            if (!empty($prcname)) {
                $this->CreateProcess($prcname);
            }
        } else {
            $this->CreateProcess();
        }
    }

    /**
     * 功   能: 创建新的进程
     * 修改日期: 2019/10/09
     * 作   者: xaoming.hu
     * @param string $proName 进程名称
     * @return void
     */
    private function CreateProcess($proName = '')
    {
        if (empty($proName)) {
            $proName = "worker-" . count($this->Mworkers);
        }
        $process = new \Swoole\Process(function (\Swoole\Process $worker) use ($proName) {
            swoole_set_process_name(sprintf("ProName:%s", $proName));
            try{
                //TODO 完成业务逻辑代码
                $this->testSleep();
            }catch (\Exception $ex){
                //TODO 错误处理逻辑
            }
        }, false, false);

        $pid = $process->start();
        $this->Mworkers[$proName] = $pid;
    }
}