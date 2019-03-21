<?php

require __DIR__.'/../vendor/autoload.php';

$n = 1;
$wait = 5;
while (true){
	sleep($wait);
	$time = $wait*$n;
	echo "Still here... bored. Been waiting $time seconds now";
	$n++;
}
