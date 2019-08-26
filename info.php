<?php
require_once "vendor/autoload.php";
// Hopefully you're using Composer autoloading.

use Pheanstalk\Pheanstalk;
//var_dump(new Pheanstalk());
// Create using autodetection of socket implementation
$pheanstalk = new Pheanstalk('127.0.0.1');

// ----------------------------------------
// producer (queues jobs)

$pheanstalk
  ->useTube('testtube')
  ->put("job payload goes here\n");

// ----------------------------------------
// worker (performs jobs)
echo "START ".microtime(true).PHP_EOL;
while ($job = $pheanstalk->watch("testtube")->ignore('default')->reserve(2)) {
	$pheanstalk->bury($job);
	sleep(1);
	echo $job->getData();
	$pheanstalk->delete($job);
}
echo "END ".microtime(true).PHP_EOL;
//
/*$job = $pheanstalk
  ->watch('testtube')
  ->ignore('default')
  ->reserve();*/