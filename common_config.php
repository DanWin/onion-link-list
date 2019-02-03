<?php
/*
* Onion Link List - Configuration
*
* Copyright (C) 2016 Daniel Winzen <d@winzen4.de>
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// Configuration
define('DBHOST', 'localhost'); // Database host
define('DBUSER', 'www-data'); // Database user
define('DBPASS', 'YOUR_DB_PASS'); // Database password
define('DBNAME', 'links'); // Database
define('PREFIX', ''); // Table Prefix - useful if other programs use the same names for tables - use only alpha-numeric values (A-Z, a-z, 0-9, or _)
define('PERSISTENT', true); // Use persistent database conection true/false
define('ADMINPASS', 'YOUR_ADMIN_PASS'); // Password for the admin interface
define('PROXY', '127.0.0.1:9050'); // Socks5 Proxy to connect to (Tor)
define('USERAGENT', 'Daniels Online-Test http://tt3j2x4k5ycaa5zt.onion/test.php'); // User-Agent to use when testing a site
define('LANG', 'en'); // Default language
define('PROMOTEPRICE', 0.025); // Price to promote a site for PROMOTETIME long
define('PROMOTETIME', 2592000); // Time (in seconds) to promote a site payed with PROMOTEPRICE - 864000 equals 10 days
define('PER_PAGE', 50); // Sites listed per page
define('VERSION', '1'); // Script version
define('DBVERSION', 3); // Database layout version
//Categories - new links will always be put into the first one, leave it to Unsorted
//once configured, only add new categories at the end or you have to manually adjust the database.
$categories=['Unsorted', 'Adult/Porn', 'Communication/Social', 'Forums', 'Hacking/Programming/Software', 'Hosting', 'Libraries/Wikis', 'Link Lists', 'Market/Shop/Store', 'Other', 'Personal Sites/Blogs', 'Security/Privacy/Encryption', 'Whistleblowing', 'Empty/Error/Unknown', 'Cryptocurrencies', 'Scams', 'Fun/Joke', 'Search', 'Autodetected scam (unchecked)'];


// Language selection
$L=[
	'de' => 'Deutsch',
	'en' => 'English',
	'ja' => '日本語'
];
if(isSet($_REQUEST['lang']) && isSet($L[$_REQUEST['lang']])){
	$language=$_REQUEST['lang'];
	if(!isSet($_COOKIE['language']) || $_COOKIE['language']!==$language){
		setcookie('language', $language);
	}
}elseif(isSet($_COOKIE['language']) && isSet($L[$_COOKIE['language']])){
	$language=$_COOKIE['language'];
}else{
	$language=LANG;
}
require_once('lang_en.php'); //always include English
if($language!=='en'){
	require_once("lang_$language.php"); //replace with translation if available
	foreach($T as $name=>$translation){
		$I[$name]=$translation;
	}
}

function print_langs(){
	global $I, $L;
	echo "<small>$I[language]: ";
	$query=preg_replace('/(&?lang=[a-z_\-]*)/i', '', $_SERVER['QUERY_STRING']);
	foreach($L as $code=>$name){
		if($query===''){
			$uri="?lang=$code";
		}else{
			$uri='?'.htmlspecialchars($query)."&amp;lang=$code";
		}
		echo " <a href=\"$uri\">$name</a>";
	}
	echo '</small>';
}
