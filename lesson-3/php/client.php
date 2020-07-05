<?php

require __DIR__.'/../vendor/autoload.php';

function terminal_log(string $msg){
	$date = date('Y-m-d H:i:s').'.'.explode('.', round(microtime(true), 2))[1];

	echo "[$date] $msg\n";
}

date_default_timezone_set(getenv('TZ'));

terminal_log("Hello. This is an example script");

/*
 * We can remove this bit - if we don't have this here the script exits
 * as soon as it hits the end, but we want to do the loop in a more
 * manageable format using React PHP
 */
$n = 1;
$wait = 5;
while (true){
	sleep($wait);
	$time = $wait*$n;
	terminal_log("Still here... bored. Been waiting $time seconds now");
	$n++;
}
