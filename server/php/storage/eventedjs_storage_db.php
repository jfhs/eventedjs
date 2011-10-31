<?php

class EventedJs_Storage_DB implements IEventedjs_Storage {

	protected $connection = null;

	public function __construct($params) {
		$params = array_merge(array(
			            'connection' => array(
				            'host'     => 'localhost',
				            'username' => 'root',
				            'password' => '',
				            'dbname'   => 'events',
			            ),
		            ),$params);
		if (is_array($params['connection'])) {
			if (! ($this->connection = mysql_connect($params['connection']['host'], $params['connection']['username'], $params['connection']['password']))) {
				throw new Exception("Can't connect to db!");
			}
			mysql_select_db($params['connection']['dbname'], $this->connection);
		}
	}

	public function save($event, $event_data, $token, $broadcast) {
		mysql_query("INSERT INTO `events` SET `event`='".mysql_real_escape_string($event, $this->connection)."', `data`='".
			mysql_real_escape_string(serialize($event_data), $this->connection)."', `receiver`='".
			mysql_real_escape_string($broadcast?0:$token, $this->connection)."'", $this->connection);
		$state = mysql_insert_id($this->connection);
		return $state;
	}

	public function get($token = null, $state = null) {
		$q = mysql_query("SELECT * FROM `events` WHERE (`receiver`=0 OR `receiver`='".mysql_real_escape_string($token, $this->connection)."')".($state===null?'':
		            " AND `id` > ".($state*1)), $this->connection);
		$result = array();
		while($r = mysql_fetch_assoc($q)) {
			$r['data'] = unserialize($r['data']);
			$result[] = $r;
		}
		return $result;
	}

	public function get_current_state($token) {
		$q = mysql_query("SELECT id FROM `events` WHERE `receiver`=0 OR `receiver`='".mysql_real_escape_string($token, $this->connection)."' ORDER BY id DESC LIMIT 1");
		if ($v = mysql_fetch_assoc($q)) {
			return $v['id'];
		}
		return 0;
	}
}