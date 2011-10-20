<?php
error_reporting(E_ALL);
while (file_exists('lock')) {
	sleep(1);
}
touch('lock');
$data = unserialize(file_get_contents('data.dat'));
unlink('lock');
if (!$data) {
	$data = array(
		'nicks' => array(),
		'events' => array(),
	);
}
session_start();
if ($_POST) {
	$event = $_POST['event'];
	$event_data = json_decode($_POST['data'], true);
	if ($event && $event_data) {
		if ($event == 'message') {
			if (strpos($event_data['text'], '/latency') === 0) {
				list($z, $lat) = explode(' ', $event_data['text']);
				$lat *= 1;
				if ($lat) {
					$event = 'latency';
					$event_data = array('latency' => $lat);
				}
			} else {
				$event_data['text'] = $data['nicks'][session_id()].': './*strip_tags*/($event_data['text']);
			}
		} elseif($event == 'enter') {
			$data['nicks'][session_id()] = $event_data['nick'];
		}
		$data['events'][] = array('event' => $event, 'data' => $event_data);
	}
	while (file_exists('lock')) {
		sleep(1);
	}
	touch('lock');
	file_put_contents('data.dat', serialize($data));
	unlink('lock');
}
$state = $_REQUEST['state'];
if (!$state || ($state == 'false')) {
	$state = count($data['events']);
}
$new_state = count($data['events']);
die(json_encode(array(
	'state' => $new_state,
	'events' => array_slice($data['events'], $state),
)));