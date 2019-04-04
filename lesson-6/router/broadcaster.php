<?php
/**
 * Copy of the authenticator with a few changes
 */

use Thruway\ClientSession;

require __DIR__.'/../vendor/autoload.php';

define('LOG_NAME', 'Broadcaster');

require 'utilities.php';
require 'connection.php';

$loop = \React\EventLoop\Factory::create();

$loop->addPeriodicTimer(30, function (){
	terminal_log("Still running, next update in 5 minutes...");
});

start_connection($argv, function (ClientSession $session, $transport, $details) use ($loop){
	terminal_log("Connection opened with role '{$details->authrole}'");
}, $loop);
