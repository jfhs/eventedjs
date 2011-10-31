<?php
error_reporting(E_ALL);
include('../server/php/evented.js.php');
$token = (isset($_REQUEST['token']) && $_REQUEST['token'])?$_REQUEST['token']:md5(rand().time().rand());
$evented = new Eventedjs(array(
		'storage' => 'EventedJs_Storage_DB',
		'token'   => $token,
		'on_shutdown' => 'on_shutdown',
                         ));
$evented->bind('message', 'on_message');
$evented->bind('enter', 'on_enter');

$state = (isset($_REQUEST['state']) && $_REQUEST['state'])?$_REQUEST['state']:$evented->get_current_state($token);

$users = unserialize(file_get_contents('users.dat'));

$evented->process($state);

function on_message($event) {
	global $evented, $users;
	$data = $event['data'];
	$data['text'] = $users[$evented->token].': '.htmlspecialchars($data['text']);
	$evented->push('message', $data, Eventedjs::BROADCAST);
}

function on_enter($event) {
	global $evented, $users;
	$data = $event['data'];
	$data['nick'] = htmlspecialchars($data['nick']);
	$evented->push('enter', $data, Eventedjs::BROADCAST);
	$users[$evented->token] = $data['nick'];
}

function on_shutdown() {
	global $users;
	file_put_contents('users.dat', serialize($users));
}