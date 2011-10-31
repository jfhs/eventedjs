<?php
define('EVENTEDJS_ROOT', __DIR__.DIRECTORY_SEPARATOR);

interface IEventedjs_Storage {
	public function save($event, $event_data, $token, $broadcast);
	public function get($token = null, $state = null);
	public function get_current_state($token);
	public function __construct($params);
}

class Eventedjs {

	const BROADCAST = -1;
	const CBTYPE_ALL = 0;
	const CBTYPE_LOCAL = 1;
	const CBTYPE_REMOTE = 2;

	protected $params;
	/**
	 * @var IEventedjs_Storage
	 */
	protected $storage;
	public $token;
	protected $state;
	protected $handlers = array();
	protected $global_handlers = array();

	public function __construct($params = array()) {
		$_params = array(
			'storage' => 'EventedJs_Storage_Session',
			'storage_params' => array(),
			'token' => null,
			'parse_json' => true,
		);
		$this->params = array_merge($_params, $params);
		if (!class_exists($this->params['storage'])) {
			if (file_exists(EVENTEDJS_ROOT.'storage/'.strtolower($this->params['storage']).'.php')) {
				include(EVENTEDJS_ROOT.'storage/'.strtolower($this->params['storage']).'.php');
			} else {
				throw new Exception('Storage class '.$this->params['storage'].' for Eventedjs is not available');
			}
		}
		if (!in_array('IEventedjs_Storage', class_implements($this->params['storage']))) {
			throw new Exception('Storage class '.$this->params['storage'].
			                    ' does not implements IEventedjs_Storage interface');
		}
		$this->storage = new $this->params['storage']($this->params['storage_params']);
		$this->token = $this->params['token'];
	}

	protected function store($event, $event_data, $receiver) {
		$state = $this->storage->save($event, $event_data,
		                     $receiver === self::BROADCAST?null:$receiver,
		                     $receiver === self::BROADCAST);
		return $state;
	}

	protected function trigger($event_data) {
		$event_data['evented'] = this;
		$event = $event_data['event'];
		if (isset($this->handlers[$event])) {
			foreach($this->handlers[$event] as $handler) {
				if (($handler['type'] == self::CBTYPE_ALL) ||
					(($handler['type'] == self::CBTYPE_LOCAL) == $event_data['local'])) {
					if (call_user_func($handler['cb'], $event_data)) {
						return;
					}
				}
			}
		}
		foreach($this->global_handlers as $handler) {
			if (($handler['type'] == self::CBTYPE_ALL) ||
				(($handler['type'] == self::CBTYPE_LOCAL) == $event_data['local'])) {
				if (call_user_func($handler['cb'], $event_data)) {
					return;
				}
			}
		}
	}

	public function bind($event, $cb, $type = self::CBTYPE_ALL) {
		if ($event === null) {
			$this->global_handlers[] = $cb;
		} else {
			if (!isset($this->handlers[$event])) {
				$this->handlers[$event] = array();
			}
			$this->handlers[$event][] = array(
				'cb' => $cb,
				'type' => $type,
			);
		}
	}

	public function push($event, $event_data, $receiver = null) {
		$this->trigger(
			array(
				'event' => $event['event'],
				'data' => $event['data'],
				'local' => true,
			)
		);
		return $this->state = $this->store($event, $event_data, ($receiver === null)?$this->token:$receiver);
	}

	public function pull($source = null) {
		if ($source === null) {
			$source = $_POST;
		}
		if (isset($source['events'])) {
			if (is_string($source['events']) && $this->params['parse_json']) {
				$source['events'] = json_decode($source['events']);
			}
			if (isset($source['token'])) {
				$this->token = $source['token'];
			}
			foreach($source['events'] as $event) {
				$this->trigger(
					array(
						'event' => $event['event'],
						'data' => $event['data'],
						'local' => false,
					)
				);
			}
		}
	}

	public function get_events($state = null) {
		return $this->storage->get($this->token, $state);
	}

	public function get_current_state($token = null) {
		return $this->storage->get_current_state(($token === null)?$this->token:$token);
	}

	public function process($state, $source = null) {
		$this->state = $state;
		$this->pull($source);
		if (isset($this->params['on_shutdown'])) {
			call_user_func($this->params['on_shutdown'], array());
		}
		die(json_encode(array(
			'events' => $this->get_events($state),
			'state'  => $this->get_current_state($this->token),
			'token'  => $this->token,
		)));
	}
}
