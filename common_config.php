<?php
/*
* Onion Link List - Configuration
*
* Copyright (C) 2016-2020 Daniel Winzen <daniel@danwin1210.me>
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
const DBHOST = 'localhost'; // Database host
const DBUSER = 'www-data'; // Database user
const DBPASS = 'YOUR_DB_PASS'; // Database password
const DBNAME = 'links'; // Database
const PREFIX = ''; // Table Prefix - useful if other programs use the same names for tables - use only alpha-numeric values (A-Z, a-z, 0-9, or _)
const PERSISTENT = true; // Use persistent database conection true/false
const ADMINPASS = 'YOUR_ADMIN_PASS'; // Password for the admin interface
const PROXY = '127.0.0.1:9050'; // Socks5 Proxy to connect to (Tor)
const USERAGENT = 'Mozilla/5.0 (Windows NT 10.0; rv:68.0) Gecko/20100101 Firefox/68.0'; // User-Agent to use when testing a site
const LANG = 'en'; // Default language
const PROMOTEPRICE = 0.025; // Price to promote a site for PROMOTETIME long
const PROMOTETIME = 2592000; // Time (in seconds) to promote a site payed with PROMOTEPRICE - 864000 equals 10 days
const PER_PAGE = 50; // Sites listed per page
const VERSION = '1.1'; // Script version
const DBVERSION = 6; // Database layout version
const REQUIRE_APPROVAL = false; // require admin approval of new sites? true/false
const CANONICAL_URL = 'https://onions.danwin1210.me'; // our preferred domain for search engines
//Categories - new links will always be put into the first one, leave it to Unsorted
//once configured, only add new categories at the end or you have to manually adjust the database.
$categories=['Unsorted', 'Adult/Porn', 'Communication/Social', 'Forums', 'Hacking/Programming/Software', 'Hosting', 'Libraries/Wikis', 'Link Lists', 'Market/Shop/Store', 'Other', 'Personal Sites/Blogs', 'Security/Privacy/Encryption', 'Whistleblowing', 'Empty/Error/Unknown', 'Cryptocurrencies', 'Scams', 'Fun/Games/Joke', 'Search', 'Autodetected scam (unchecked)'];

// Language selection
$I = $T = [];
$language = LANG;
$L=[
	'de' => 'Deutsch',
	'en' => 'English',
	'ja' => '日本語',
	'tr' => 'Türkçe',
];
if(isset($_REQUEST['lang']) && isset($L[$_REQUEST['lang']])){
	$language=$_REQUEST['lang'];
	if(!isset($_COOKIE['language']) || $_COOKIE['language']!==$language){
		set_secure_cookie('language', $language);
	}
}elseif(isset($_COOKIE['language']) && isset($L[$_COOKIE['language']])){
	$language=$_COOKIE['language'];
}
require_once(__DIR__.'/lang_en.php'); //always include English
if($language!=='en'){
	require_once(__DIR__."/lang_$language.php"); //replace with translation if available
	foreach($T as $name=>$translation){
		$I[$name]=$translation;
	}
}

function print_langs(){
	global $I, $L;
	echo "<small>$I[language]: ";
	$query=ltrim(preg_replace('/&?lang=[a-z_\-]*/i', '', $_SERVER['QUERY_STRING']), '&');
	foreach($L as $code=>$name){
		if($query===''){
			$uri="?lang=$code";
		}else{
			$uri='?'.htmlspecialchars($query)."&amp;lang=$code";
		}
		echo " <a href=\"$uri\" target='_self' hreflang=\"$code\">$name</a>";
	}
	echo '</small>';
}

function blacklist_scams(string $address, string $content){
	global $db;
	$scams = ['Black&White Cards :: Index', 'Shadow guide | The ultimate guide of dark web ', 'ONIONLIST - SAFE .ONION LINKS LISTING', 'Dir ', 'netAuth', 'POPBUY MARKET', 'Digital Goods - Verified by GoDark Search, Hidden Links, Wiki, Escrow', 'Delta - Secure Black Market', 'DeDope', 'Unlocker - iCloud Activation Services', '222LOTTO!', 'STREAMING SERVICES ACCOUNTS', 'Red Room', 'Digital Cash'];
	$cp_scams = ['Wonderful shop', '~ DROP BY TARYAXX ~', 'Magic CP', 'Lolita Club', 'Daft Tadjikskiy Sex Video _ Inductively Fiberless Porno Qom Along With Post Porn Com Numb _ Porn Zdarma', 'xPlay - hosting service for porn videos', 'DARK PRIVATE PACK', 'Good Porn'];
	//xonions
	if(strpos($content, '<p class="title"><a href="account.html" title="Asia Holiday">Asia Holiday</a></p>')){
			$move=$db->prepare("UPDATE onions SET address='', category=15, locked=1, description=CONCAT(description, ' - SCAM') WHERE address = ? AND locked=0;");
			$move->execute([$address]);
	}
	//raped bitch
	if(strpos($content, 'rape material uploaded on highspeed servers that don\'t require')){
			$move=$db->prepare("UPDATE onions SET address='', category=15, locked=1, description=CONCAT(description, ' - SCAM') WHERE address = ? AND locked=0;");
			$move->execute([$address]);
	}
	//underage cam girl
	if(strpos($content, 'also have some real underage prostitutes for you')){
			$move=$db->prepare("UPDATE onions SET address='', category=15, locked=1, description=CONCAT(description, ' - SCAM') WHERE address = ? AND locked=0;");
			$move->execute([$address]);
	}
	if(preg_match('~<title>(.*?)</title>~s', $content, $matches)){
		if(in_array($matches[1], $scams, true) || preg_match('~(paypal|weed store|credit card|western union|Market Guns|weedstore|banknotes|porn hacker|hack facebook|hack twitter|hack insta|^amazin(\s|$)|Transfers?|btc generat|counterfeit|Cocaine|gift card|BITCOIN ADDRESS MARKET|mastercard|hidden\swiki|CCShop|bitcoin exploit|Bitcoin Generat|bitcoin x200|bitcoin x100|bitcoin x3|bitxoin x10|stolen bitcoin|galaxyshop|icloudremove|icloud activat|netflix|spotify|clone cc|clone card|cloned card|Preloaded|prepaid|moneygram|Financial Service|Delta Marketplace|apple product|apple shop|apple store|samsung product|apple market|samsung shop|hitman|hitmen|samsung store|samsung phone|Marijuana|deepmarket|drugs? store)~i', $matches[1])){
			$move=$db->prepare("UPDATE onions SET category=15, locked=1, description=CONCAT(description, ' - SCAM') WHERE address = ? AND locked=0;");
			$move->execute([$address]);
		}
		if(in_array($matches[1], $cp_scams, true) || preg_match('~(PTHC|Family Porn|Animal Porno|Child Porn|^CP|^Pedo|Underage|^baby|Little Girls|porno child|porn child|loliporn|H.M.M.|preteen|illegal sex|kids? porn|love cp|dog sex|zoo porn|daddy i love you|family love|xonions|best onion porn|onion link porn|^rape|young cam| cp |yespedo|little daughter|OnionDir - Adult|destroyed daughter|Deep-Pedo|hurt boy|child forbidden)~i', $matches[1])){
			$move=$db->prepare("UPDATE onions SET address='', category=15, locked=1, description=CONCAT(description, ' - SCAM') WHERE address = ? AND locked=0;");
			$move->execute([$address]);
		}
	}
}

function send_headers(array $styles = []){
	header('Content-Type: text/html; charset=UTF-8');
	header('Pragma: no-cache');
	header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0, private');
	header('Expires: 0');
	header('Referrer-Policy: no-referrer');
	header("Permissions-Policy: accelerometer 'none'; ambient-light-sensor 'none'; autoplay 'none'; battery 'none'; camera 'none'; cross-origin-isolated 'none'; display-capture 'none'; document-domain 'none'; encrypted-media 'none'; geolocation 'none'; fullscreen 'none'; execution-while-not-rendered 'none'; execution-while-out-of-viewport 'none'; gyroscope 'none'; magnetometer 'none'; microphone 'none'; midi 'none'; navigation-override 'none'; payment 'none'; picture-in-picture 'none'; publickey-credentials-get 'none'; screen-wake-lock 'none'; sync-xhr 'none'; usb 'none'; web-share 'none'; xr-spatial-tracking 'none'; clipboard-read 'none'; clipboard-write 'none'; gamepad 'none'; speaker-selection 'none'; conversion-measurement 'none'; focus-without-user-activation 'none'; hid 'none'; idle-detection 'none'; sync-script 'none'; vertical-scroll 'none'; serial 'none'; trust-token-redemption 'none';");
	$style_hashes = '';
	foreach($styles as $style) {
		$style_hashes .= " 'sha256-".base64_encode(hash('sha256', $style, true))."'";
	}
	header("Content-Security-Policy: base-uri 'self'; default-src 'none'; form-action 'self'; frame-ancestors 'none'; img-src data:; style-src $style_hashes");
	header('X-Content-Type-Options: nosniff');
	header('X-Frame-Options: deny');
	header('X-XSS-Protection: 1; mode=block');
	if($_SERVER['REQUEST_METHOD'] === 'HEAD'){
		exit; // headers sent, no further processing needed
	}
}

function set_secure_cookie(string $name, string $value){
	if (version_compare(PHP_VERSION, '7.3.0') >= 0) {
		setcookie($name, $value, ['expires' => 0, 'path' => '/', 'domain' => '', 'secure' => is_definitely_ssl(), 'httponly' => true, 'samesite' => 'Strict']);
	}else{
		setcookie($name, $value, 0, '/', '', is_definitely_ssl(), true);
	}
}

function is_definitely_ssl() : bool {
	if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
		return true;
	}
	if (isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'])) {
		return true;
	}
	if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && ('https' === $_SERVER['HTTP_X_FORWARDED_PROTO'])) {
		return true;
	}
	return false;
}

function set_curl_options($ch){
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_USERAGENT, USERAGENT);
	curl_setopt($ch, CURLOPT_PROXY, PROXY);
	curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5_HOSTNAME);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
	curl_setopt($ch, CURLOPT_TIMEOUT, 60);
	curl_setopt($ch, CURLOPT_ENCODING, '');
}
