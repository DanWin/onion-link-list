<?php
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
const DBVERSION = 8; // Database layout version
const REQUIRE_APPROVAL = false; // require admin approval of new sites? true/false
const CANONICAL_URL = 'https://onions.danwin1210.de'; // our preferred domain for search engines
const CAPTCHA = 0; // Captcha difficulty (0=off, 1=simple, 2=moderate, 3=extreme)
//Categories - new links will always be put into the first one, leave it to Unsorted
//once configured, only add new categories at the end or you have to manually adjust the database.
$categories=['Unsorted', 'Adult/Porn', 'Communication/Social', 'Forums', 'Hacking/Programming/Software', 'Hosting', 'Libraries/Wikis', 'Link Lists', 'Market/Shop/Store', 'Other', 'Personal Sites/Blogs', 'Security/Privacy/Encryption', 'Whistleblowing', 'Empty/Error/Unknown', 'Cryptocurrencies', 'Scams', 'Fun/Games/Joke', 'Search'];

// Language selection
const LANGUAGES = [
	'de' => ['name' => 'Deutsch', 'locale' => 'de_DE', 'flag' => '🇩🇪', 'show_in_menu' => true, 'dir' => 'ltr'],
	'en' => ['name' => 'English', 'locale' => 'en_GB', 'flag' => '🇬🇧', 'show_in_menu' => true, 'dir' => 'ltr'],
	'fa' => ['name' => 'فارسی', 'locale' => 'fa_IR', 'flag' => '🇮🇷', 'show_in_menu' => true, 'dir' => 'rtl'],
	'ja' => ['name' => '日本語', 'locale' => 'ja_JP', 'flag' => '🇯🇵', 'show_in_menu' => true, 'dir' => 'ltr'],
	'pt' => ['name' => 'Português', 'locale' => 'pt_PT', 'flag' => '🇵🇹', 'show_in_menu' => true, 'dir' => 'ltr'],
	'ru' => ['name' => 'Русский', 'locale' => 'ru_RU', 'flag' => '🇷🇺', 'show_in_menu' => true, 'dir' => 'ltr'],
	'tr' => ['name' => 'Türkçe', 'locale' => 'tr_TR', 'flag' => '🇹🇷', 'show_in_menu' => true, 'dir' => 'ltr'],
];
$language = LANG;
$locale = LANGUAGES[LANG]['locale'];
$dir = LANGUAGES[LANG]['dir'];
if(isset($_REQUEST['lang']) && isset(LANGUAGES[$_REQUEST['lang']])){
	$locale = LANGUAGES[$_REQUEST['lang']]['locale'];
	$language = $_REQUEST['lang'];
	$dir = LANGUAGES[$_REQUEST['lang']]['dir'];
	setcookie('language', $_REQUEST['lang'], ['expires' => 0, 'path' => '/', 'domain' => '', 'secure' => ($_SERVER['HTTPS'] ?? '' === 'on'), 'httponly' => true, 'samesite' => 'Strict']);
}elseif(isset($_COOKIE['language']) && isset(LANGUAGES[$_COOKIE['language']])){
	$locale = LANGUAGES[$_COOKIE['language']]['locale'];
	$language = $_COOKIE['language'];
	$dir = LANGUAGES[$_COOKIE['language']]['dir'];
}elseif(!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
	$prefLocales = array_reduce(
		explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']),
		function (array $res, string $el) {
			list($l, $q) = array_merge(explode(';q=', $el), [1]);
			$res[$l] = (float) $q;
			return $res;
		}, []);
	arsort($prefLocales);
	foreach($prefLocales as $l => $q){
		$lang = locale_lookup(array_keys(LANGUAGES), $l);
		if(!empty($lang)){
			$locale = LANGUAGES[$lang]['locale'];
			$language = $lang;
			$dir = LANGUAGES[$lang]['dir'];
			setcookie('language', $lang, ['expires' => 0, 'path' => '/', 'domain' => '', 'secure' => ($_SERVER['HTTPS'] ?? '' === 'on'), 'httponly' => true, 'samesite' => 'Strict']);
			break;
		}
	}
}
putenv('LC_ALL='.$locale);
setlocale(LC_ALL, $locale);

bindtextdomain('onion-link-list', __DIR__.'/locale');
bind_textdomain_codeset('onion-link-list', 'UTF-8');
textdomain('onion-link-list');

function print_langs(): void
{
	echo "<ul class=\"list\"><li>"._('Language:')."</li>";
	$query=ltrim(preg_replace('/&?lang=[a-z_\-]*/i', '', $_SERVER['QUERY_STRING']), '&');
	foreach(LANGUAGES as $code => $data){
		if($query===''){
			$uri="?lang=$code";
		}else{
			$uri='?'.htmlspecialchars($query)."&amp;lang=$code";
		}
		echo "<li><a href=\"$uri\" target='_self' hreflang=\"$code\">$data[name]</a></li>";
	}
	echo '</ul>';
}

function blacklist_scams(string $address, string $content): void
{
	global $db;
	$scams = ['Black&White Cards :: Index', 'Shadow guide | The ultimate guide of dark web ', 'ONIONLIST - SAFE .ONION LINKS LISTING', 'Dir ', 'netAuth', 'POPBUY MARKET', 'Digital Goods - Verified by GoDark Search, Hidden Links, Wiki, Escrow', 'Delta - Secure Black Market', 'DeDope', 'Unlocker - iCloud Activation Services', '222LOTTO!', 'STREAMING SERVICES ACCOUNTS', 'Red Room', 'Digital Cash'];
	$cp_scams = ['Wonderful shop', '~ DROP BY TARYAXX ~', 'Magic CP', 'Lolita Club', 'Daft Tadjikskiy Sex Video _ Inductively Fiberless Porno Qom Along With Post Porn Com Numb _ Porn Zdarma', 'xPlay - hosting service for porn videos', 'DARK PRIVATE PACK', 'Good Porn'];
	//xonions
	if(strpos($content, '<p class="title"><a href="account.html" title="Asia Holiday">Asia Holiday</a></p>')){
			$move=$db->prepare("UPDATE onions SET address='', category=15, locked=1, description=CONCAT(description, ' - SCAM'), timechanged=? WHERE address = ? AND locked=0;");
			$move->execute([time(), $address]);
	}
	//raped bitch
	if(strpos($content, 'rape material uploaded on highspeed servers that don\'t require')){
			$move=$db->prepare("UPDATE onions SET address='', category=15, locked=1, description=CONCAT(description, ' - SCAM'), timechanged=? WHERE address = ? AND locked=0;");
			$move->execute([time(), $address]);
	}
	//underage cam girl
	if(strpos($content, 'also have some real underage prostitutes for you')){
			$move=$db->prepare("UPDATE onions SET address='', category=15, locked=1, description=CONCAT(description, ' - SCAM'), timechanged=? WHERE address = ? AND locked=0;");
			$move->execute([time(), $address]);
	}
	if(preg_match('~<title>(.*?)</title>~s', $content, $matches)){
		if(in_array($matches[1], $scams, true) || preg_match('~(paypal|weed store|credit card|western union|Market Guns|weedstore|banknotes|porn hacker|hack facebook|hack twitter|hack insta|^amazin(\s|$)|Transfers?|btc generat|counterfeit|Cocaine|gift card|BITCOIN ADDRESS MARKET|mastercard|hidden\swiki|CCShop|bitcoin exploit|Bitcoin Generat|bitcoin x200|bitcoin x100|bitcoin x3|bitxoin x10|stolen bitcoin|galaxyshop|icloudremove|icloud activat|netflix|spotify|clone cc|clone card|cloned card|Preloaded|prepaid|moneygram|Financial Service|Delta Marketplace|apple product|apple shop|apple store|samsung product|apple market|samsung shop|hitman|hitmen|samsung store|samsung phone|Marijuana|deepmarket|drugs? store)~i', $matches[1])){
			$move=$db->prepare("UPDATE onions SET category=15, locked=1, description=CONCAT(description, ' - SCAM'), timechanged=? WHERE address = ? AND locked=0;");
			$move->execute([time(), $address]);
		}
		if(in_array($matches[1], $cp_scams, true) || preg_match('~(PTHC|Family Porn|Animal Porno|Child Porn|^CP|^Pedo|Underage|^baby|Little Girls|porno child|porn child|loliporn|H.M.M.|preteen|illegal sex|kids? porn|love cp|dog sex|zoo porn|daddy i love you|family love|xonions|best onion porn|onion link porn|^rape|young cam| cp |yespedo|little daughter|OnionDir - Adult|destroyed daughter|Deep-Pedo|hurt boy|child forbidden)~i', $matches[1])){
			$move=$db->prepare("UPDATE onions SET address='', category=15, locked=1, description=CONCAT(description, ' - SCAM'), timechanged=? WHERE address = ? AND locked=0;");
			$move->execute([time(), $address]);
		}
	}
}

function send_headers(array $styles = []): void
{
	header('Content-Type: text/html; charset=UTF-8');
	header('Pragma: no-cache');
	header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0, private');
	header('Expires: 0');
	header('Referrer-Policy: no-referrer');
	header("Permissions-Policy: accelerometer=(), ambient-light-sensor=(), autoplay=(), battery=(), camera=(), cross-origin-isolated=(), display-capture=(), document-domain=(), encrypted-media=(), execution-while-not-rendered=(), execution-while-out-of-viewport=(), fullscreen=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), midi=(), navigation-override=(), payment=(), picture-in-picture=(), publickey-credentials-get=(), screen-wake-lock=(), sync-xhr=(), usb=(), web-share=(), xr-spatial-tracking=(), clipboard-read=(), clipboard-write=(), gamepad=(), speaker-selection=(), conversion-measurement=(), focus-without-user-activation=(), hid=(), idle-detection=(), sync-script=(), vertical-scroll=(), serial=(), trust-token-redemption=(), interest-cohort=(), otp-credentials=()");
	header("Cross-Origin-Embedder-Policy: require-corp");
	header("Cross-Origin-Opener-Policy: same-origin");
	header("Cross-Origin-Resource-Policy: same-origin");
	$style_hashes = '';
	foreach($styles as $style) {
		$style_hashes .= " 'sha256-".base64_encode(hash('sha256', $style, true))."'";
	}
	header("Content-Security-Policy: base-uri 'self'; default-src 'none'; form-action 'self'; frame-ancestors 'none'; img-src data: 'self'; style-src $style_hashes");
	header('X-Content-Type-Options: nosniff');
	header('X-Frame-Options: deny');
	header('X-XSS-Protection: 0');
	if($_SERVER['REQUEST_METHOD'] === 'HEAD'){
		exit; // headers sent, no further processing needed
	}
}

function set_curl_options($ch): void
{
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_USERAGENT, USERAGENT);
	curl_setopt($ch, CURLOPT_PROXY, PROXY);
	curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5_HOSTNAME);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
	curl_setopt($ch, CURLOPT_TIMEOUT, 120);
	curl_setopt($ch, CURLOPT_ENCODING, '');
}

function alt_links(): void
{
	global $language;
	$canonical_query = [];
	if(isset($_REQUEST['cat'])) {
		$canonical_query['cat'] = $_REQUEST['cat'];
	}
	if(isset($_REQUEST['pg'])) {
		$canonical_query['pg'] = $_REQUEST['pg'];
	}
	foreach(LANGUAGES as $lang => $data) {
		if($lang === $language){
			continue;
		}
		$canonical_query['lang'] = $lang;
		$link = CANONICAL_URL . ($_SERVER['SCRIPT_NAME'] === '/index.php' ? '/' : $_SERVER['SCRIPT_NAME']) . '?' . http_build_query($canonical_query);
		echo '<link rel="alternate" href="'.$link.'" hreflang="'.$lang.'" />';
		echo '<meta property="og:locale:alternate" content="'.$data['locale'].'">';
	}
}
