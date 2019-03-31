<?php
/**
 * All the work from lesson 4 with a few extras added to get us up to speed for session 5
 * Have moved a few items into functions to keep stuff clean
 */

use Thruway\CallResult;
use Thruway\ClientSession;

require __DIR__.'/../vendor/autoload.php';

require 'utilities.php';
require 'connection.php';
require 'auth.php';

start_connection($argv, function (ClientSession $session, $details){
	terminal_log("Connection opened with role {$details->authrole}");

	register_auth($session);
	register_permissions($session);

	// We're still going to be adding more stuff in here - you can add new
	// functions or just write it straight in
});
