<?php
// config file
$ganalyticsid = '';
$adsenseid = '';
$amazonids = array(
	'co.uk'	=> '',
	'com'	=> '',
);
$keywords = array();
$title = "Life Journal by Mukudu";
$welcomemsg = 'In Hindsight – Ben’s Story';
$author = 'Benjamin Ellis, Mukudu Sites, Mukudu Ltd';
$description = "In Hindsight – Ben's Story";
$byline = "An ordinary person's extraordinary life.";

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
	//'dbname' => 'inhindsight',
    'dbname' => 'inhindsight2',
	'dbport' => 3306
);

// application configuration
$config = array(
	'startyear' => 1964,
	'defaultnumrecords' => 50,
	'detaillength' => 120,
);
