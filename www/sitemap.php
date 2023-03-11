<?php
require_once __DIR__.'/../common_config.php';
global $categories;
try{
	$db=new PDO('mysql:host=' . DBHOST . ';dbname=' . DBNAME . ';charset=utf8mb4', DBUSER, DBPASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_WARNING, PDO::ATTR_PERSISTENT=>PERSISTENT]);
}catch(PDOException $e){
	http_response_code(500);
	die(_('No database connection!'));
}
$links = [];
$alts = [];
foreach (LANGUAGES as $lang_code => $data) {
	$alts []= ['hreflang' => $lang_code, 'href' => CANONICAL_URL . '/test.php?lang='.$lang_code];
}
$links []= ['loc' => CANONICAL_URL . '/test.php', 'changefreq' => 'weekly', 'priority' => '0.8', 'alt' => $alts];
$links []= ['loc' => CANONICAL_URL . '/?format=json', 'changefreq' => 'daily', 'priority' => '0.2'];
$links []= ['loc' => CANONICAL_URL . '/?format=text', 'changefreq' => 'daily', 'priority' => '0.2'];
$admin_approval = '';
if(REQUIRE_APPROVAL){
	$admin_approval = PREFIX . 'onions.approved = 1 AND';
}
$stmt=$db->prepare('SELECT COUNT(*) FROM ' . PREFIX . "onions WHERE $admin_approval category=? AND address!='' AND id NOT IN (SELECT onion_id FROM " . PREFIX . 'phishing) AND timediff<604800;');
foreach($categories as $cat => $name){
	$alts = [];
	foreach (LANGUAGES as $lang_code => $data) {
		$alts []= ['hreflang' => $lang_code, 'href' => CANONICAL_URL . "/?cat=$cat&lang=$lang_code"];
	}
	$links []= ['loc' => CANONICAL_URL . "/?cat=$cat", 'changefreq' => 'daily', 'priority' => '0.3', 'alt' => $alts];
	$stmt->execute([$cat]);
	$num=$stmt->fetch(PDO::FETCH_NUM);
	$pages=ceil($num[0]/PER_PAGE);
	if($pages > 1) {
		while ( $pages > 1 ) {
			$alts = [];
			foreach (LANGUAGES as $lang_code => $data) {
				$alts []= ['hreflang' => $lang_code, 'href' => CANONICAL_URL . "/?cat=$cat&pg=$pages&lang=$lang_code"];
			}
			$links [] = [ 'loc' => CANONICAL_URL . "/?cat=$cat&pg=$pages", 'changefreq' => 'daily', 'priority' => '0.3', 'alt' => $alts ];
			--$pages;
		}
		$alts = [];
		foreach (LANGUAGES as $lang_code => $data) {
			$alts []= ['hreflang' => $lang_code, 'href' => CANONICAL_URL . "/?cat=$cat&pg=0&lang=$lang_code"];
		}
		$links [] = [ 'loc' => CANONICAL_URL . "/?cat=$cat&pg=0", 'changefreq' => 'daily', 'priority' => '0.3', 'alt' => $alts ];
	}
}
$special=[
	'all'=>"address!='' AND category!=15 AND id NOT IN (SELECT onion_id FROM " . PREFIX . 'phishing) AND timediff<604800',
	'lastadded'=>"address!='' AND id NOT IN (SELECT onion_id FROM " . PREFIX . 'phishing)',
	'offline'=>"address!='' AND id NOT IN (SELECT onion_id FROM " . PREFIX . 'phishing) AND timediff>604800'
];
$cat=count($categories);
foreach($special as $query){
	$alts = [];
	foreach (LANGUAGES as $lang_code => $data) {
		$alts []= ['hreflang' => $lang_code, 'href' => CANONICAL_URL . "/?".($cat===count($categories) ? '' : "cat=$cat&")."lang=$lang_code"];
	}
	$links []= ['loc' => CANONICAL_URL . "/".($cat===count($categories) ? '' : "?cat=$cat"), 'changefreq' => 'daily', 'priority' => $cat===count($categories) ? '1.0' : '0.3', 'alt' => $alts];
	if($cat===count($categories)+1){
		$num[0]=PER_PAGE;
	}else{
		$num=$db->query('SELECT COUNT(*) FROM ' . PREFIX . "onions WHERE $admin_approval $query;")->fetch(PDO::FETCH_NUM);
	}
	$pages=ceil($num[0]/PER_PAGE);
	if($pages > 1) {
		while ( $pages > 1 ) {
			$alts = [];
			foreach (LANGUAGES as $lang_code => $data) {
				$alts []= ['hreflang' => $lang_code, 'href' => CANONICAL_URL . "/?cat=$cat&pg=$pages&lang=$lang_code"];
			}
			$links [] = [ 'loc' => CANONICAL_URL . "/?cat=$cat&pg=$pages", 'changefreq' => 'daily', 'priority' => '0.3', 'alt' => $alts ];
			--$pages;
		}
		$alts = [];
		foreach (LANGUAGES as $lang_code => $data) {
			$alts []= ['hreflang' => $lang_code, 'href' => CANONICAL_URL . "/?cat=$cat&pg=0&lang=$lang_code"];
		}
		$links [] = [ 'loc' => CANONICAL_URL . "/?cat=$cat&pg=0", 'changefreq' => 'daily', 'priority' => '0.3', 'alt' => $alts ];
	}
	++$cat;
}
$alts = [];
foreach (LANGUAGES as $lang_code => $data) {
	$alts []= ['hreflang' => $lang_code, 'href' => CANONICAL_URL . "/?cat=$cat&lang=$lang_code"];
}
$links []= ['loc' => CANONICAL_URL . "/?cat=$cat", 'changefreq' => 'daily', 'priority' => '0.3', 'alt' => $alts];
$dom = new DOMDocument('1.0', 'UTF-8');
try {
	$urlset = $dom->createElement( 'urlset' );
	$urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
	$urlset->setAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml');
	$dom->appendChild($urlset);
	foreach ($links as $link) {
		$url = $dom->createElement('url');
		$urlset->appendChild($url);
		$loc = $dom->createElement('loc', htmlspecialchars($link['loc']));
		$url->appendChild($loc);
		foreach ($link['alt'] as $alt){
			$link_alt = $dom->createElement('xhtml:link');
			$link_alt->setAttribute('rel', 'alternate');
			$link_alt->setAttribute('hreflang', $alt['hreflang']);
			$link_alt->setAttribute('href', $alt['href']);
			$url->appendChild($link_alt);
		}
		$changefreq = $dom->createElement('changefreq', $link['changefreq']);
		$url->appendChild($changefreq);
		$priority = $dom->createElement('priority', $link['priority']);
		$url->appendChild($priority);
	}
} catch ( DOMException $e ) {
	http_response_code(500);
	die(_('Error creating the sitemap!'));
}
header('Content-Type: text/xml; charset=UTF-8');
echo $dom->saveXML();
