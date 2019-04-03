<?php

require __DIR__.'/../vendor/autoload.php';

function terminal_log(string $msg){
	$date = date('Y-m-d H:i:s').'.'.explode('.', round(microtime(true), 2))[1];

	echo "[$date] $msg\n";
}

$loop = \React\EventLoop\Factory::create();

$context = new \React\ZMQ\Context($loop);
$pull = $context->getSocket(ZMQ::SOCKET_PULL);
$pull->bind('tcp://0.0.0.0:5550');

$pull->on('message', function ($request){
	terminal_log("Got a message: $request");
});

$loop->addTimer(1, function (){
	terminal_log("Loop was started, will report that it is still running every 5 minutes");
});

$loop->addPeriodicTimer(300, function (){
	terminal_log("Still running, next update in 5 minutes...");
});

$loop->run();
