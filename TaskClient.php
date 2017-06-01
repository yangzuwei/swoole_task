<?php

class TaskClient
{
	protected $client;

	public function __construct()
	{
	    $this->client = new swoole_client(SWOOLE_SOCK_TCP);
	    if (!$this->client->connect('127.0.0.1', 9503, -1))
	    {
	        exit("connect failed. Error: {$client->errCode}\n");
	    }		
	}

	public function sendData($data)
	{
	    $this->client->send($data);
	    return $this->client->recv();		
	}

	function __destruct()
	{
		$this->client->close();
	}
    
}

$client = new TaskClient();
$data = "hello";
$client->sendData($data);
