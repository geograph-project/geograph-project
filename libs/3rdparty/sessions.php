<?php

require dirname(__FILE__)."/redis.php";

class redis_sessions
{
	private $redis = NULL;
	private $session_name = NULL;
	public $db = 0;
	public $lifetime = NULL;
	public $host = "127.0.0.1";
	public $port = 6379;
	
	public function __construct()
	{
	}
	
	public function open($save_path, $session_name)
	{
		$this->session_name = $session_name;
		
		if ($this->redis === NULL)
		{
			$this->redis = new php_redis($this->host, $this->port);
			if ($this->db != 0)
			{
				$this->redis->select($this->db);
			}
		}
	}

	public function close()
	{
		$this->redis = NULL;
	}
	
	public function read($id)
	{
		$key = $this->session_name.":".$id;
		
		$sess_data = $this->redis->get($key);
		if ($sess_data === NULL)
		{
			return "";
		}
		return $sess_data;
	}
	
	public function write($id, $sess_data)
	{
		$key = $this->session_name.":".$id;
		$lifetime = $this->lifetime;
		if ($lifetime === NULL) {
			$lifetime = ini_get("session.gc_maxlifetime");
		}
		
		$this->redis->set_expire($key, $lifetime, $sess_data);
	}
	
	public function destroy($id)
	{
		$key = $this->session_name.":".$id;
		
		$this->redis->delete($key);
	}
	
	public function gc($maxlifetime)
	{
	}

	public function install()
	{
		session_set_save_handler(
			array($this, "open"),
			array($this, "close"),
			array($this, "read"),
			array($this, "write"),
			array($this, "destroy"),
			array($this, "gc")
		);
	}
}
