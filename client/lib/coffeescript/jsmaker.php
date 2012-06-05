<?php
ini_set('display_errors', 1);
error_reporting(0);
header('Content-Type: application/javascript', true);
if(isset($_GET['f']))
{
	include 'class.jstocoffee.php';
	$JsToCoffee = new JsToCoffee;
	$JsToCoffee->setCacheDir(__DIR__ . '/cache/');
	$JsToCoffee->setAllowedCoffeeDir(__DIR__ . '/../../coffee/');
	echo $JsToCoffee->makeJavascript($_GET['f']);
}
else
{
	echo '';
}