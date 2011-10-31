<?php

class EventedJs_Storage_Session implements IEventedjs_Storage {

	protected $session_key;

	public function __construct($params) {
		array_merge(array(
			            'session_key' => '_events',
		            ), $params);
		$this->session_key = $params['session_key'];
	}

	public function save($event, $event_data, $token, $broadcast) {
		if ($broadcast) {
			throw new Exception("Broadcast messages are not supported by Session storage");
		}
		session_start();
		$_SESSION[$this->session_key]['events'][] = array(
			'event' => $event,
			'data' => $event_data,
		);
		$_SESSION[$this->session_key]['token'] = $token;
		return count($_SESSION[$this->session_key]['events']);
	}

	public function get($token = null, $state = null) {
		if ($_SESSION[$this->session_key]['token'] == $token) {
			return array_slice($_SESSION[$this->session_key]['events'], $state);
		}
		return null;
	}
}