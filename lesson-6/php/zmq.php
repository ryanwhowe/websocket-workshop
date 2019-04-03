<?php

require __DIR__.'/../vendor/autoload.php';

function terminal_log(string $msg){
	$date = date('Y-m-d H:i:s').'.'.explode('.', round(microtime(true), 2))[1];

	echo "[$date] $msg\n";
}

// Add your new functions here

date_default_timezone_set(getenv('TZ'));

// Add your procedural code here
