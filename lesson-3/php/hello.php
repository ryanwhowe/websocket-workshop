<?php

require __DIR__.'/../vendor/autoload.php';

$args = array_splice($argv, 1);

echo "Hello. This is an example script";

if (!$args){
	die("\n");
}

echo ", here are the arguments you provided";

print_r($args);
