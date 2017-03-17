<?php
// config file
$ganalyticsid = '';
$adsenseid = '';
$amazonids = array(
	'co.uk'	=> '',
	'com'	=> '',
);
$keywords = array();
$title = 'In Hindsight';
$welcomemsg = 'Welcome';
$author = 'Benjamin Ellis, Mukudu Sites, Mukudu Ltd';
$description = 'The In Hindsight Site';

//cookie consent
$theme = "light-top";		// for dark sites
//$theme = "dark-top";			// for light sites
$message = "This website uses cookies to ensure you get the best experience on our website";
$dismissmsg = "Got it!";
$moremsg = "More info";
$morelink = '/cookiepolicy.php';		// link to policy document

// database configuration
$dbconfig = array(
	'dbserver' => 'localhost',
	'dbuser' => 'inhindsight',
	'dbpasswd' => '1nh1nd5ight',
	'dbname' => 'inhindsight',
	'dbport' => 3306
);

// application configuration
$config = array(
	'startyear' => 1965,
);