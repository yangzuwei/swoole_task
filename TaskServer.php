<?php
date_default_timezone_set('Asia/Shanghai');

class TaskServer
{
    protected $server;

    protected $config =  
    [
        'server_param'=>
        [
            'worker_num' => 1,   //一般设置为服务器CPU数的1-4倍
            'daemonize' => 1,  //以守护进程执行
            'max_request' => 10000,
            'dispatch_mode' => 2,
            'task_worker_num' => 8,  //task进程的数量
            "task_ipc_mode " => 3 ,  //使用消息队列通信，并设置为争抢模式
            "log_file" => "log/error.log" ,//日志
        ],
    ]; 

    public function __construct()
    {
        $this->server = new swoole_server("127.0.0.1",9503);
        $this->server->set($this->config["server_param"]);
    }

    public function run()
    {
        $this->server->on("receive",[$this,"onReceive"]);
        $this->server->on("task",[$this,"onTask"]);
        $this->server->on("finish",[$this,"onFinish"]);
        $this->server->start();
    }

    public function onReceive($serv, $fd, $from_id, $data) 
    {
        //发送数据到任务池中 且给客户端返回一个响应
        $serv->task($data);
        $serv->send($fd,"done");
    }

    public function onTask($serv, $task_id, $src_worker_id, $data)
    {
        //异步任务执行逻辑
        $dataToArr = json_decode($data,true);
        $activityId = $dataToArr['activity_id'];
        //使用命令行或者其他处理逻辑来执行任务，这个方法中已经是异步处理了，不用担心阻塞
        if("success" == exec("php /home/wwwroot/sports/index.php taskqueue saveactivityrecord ".$activityId)){
            return json_encode(["activity_id"=>$activityId,"status"=>1]);
        }else{
            return json_encode(["activity_id"=>$activityId,"status"=>0]);
        }
    }

    public function onFinish($serv, $task_id, $data)
    {
        //写日志
        file_put_contents("/home/wwwroot/sports/log/task_activity_finish.log",$data." saved ".date("Y-m-d H:i:s")."\n",FILE_APPEND);
    }

}

$serv = new TaskServer();
$serv->run();