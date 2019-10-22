<?php
namespace SwooleGadget\Process;

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
    private $Mworkers;
    /**
     * 功能：保持启动进程数量
     *
     * @var integer
     */
    private $WorkMaxNum = 8;
    /**
     * 回调函数的参数
     * @var string
     */
    private $param = '';
    /**
     * 回调函数
     * @var null
     */
    private $callBack = null;

    /**
     * ProManager constructor.
     *
     * @param callable|null $func 接受任务回调函数
     * @param array         $params 配置任务参数与设置启动进程数量
     */
    public function __construct(callable $func = null, array $params = [])
    {
        $this->callBack = $func;
        $this->param = $params['param'];
        $this->WorkMaxNum = $params['workerNumLimit'];
        $this->run();
    }

    /**
     * 功   能: 进程启动入口方法
     * 修改日期: 2019/10/09
     * 作   者: xiaoming.hu
     * @return void
     */
    private function run()
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
                call_user_func($this->callBack, $this->param);
            }catch (\Exception $ex){
                //TODO 错误处理逻辑
            }
        }, false, false);

        $pid = $process->start();
        $this->Mworkers[$proName] = $pid;
    }
}
