<?php
require_once __DIR__.'/../common_config.php';
try{
	$db=new PDO('mysql:host=' . DBHOST . ';dbname=' . DBNAME . ';charset=utf8mb4', DBUSER, DBPASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_WARNING, PDO::ATTR_PERSISTENT=>PERSISTENT]);
}catch(PDOException $e){
	http_response_code(500);
	die($I['nodb']);
}
$links = [];
$links []= ['loc' => CANONICAL_URL . '/test.php', 'changefreq' => 'weekly', 'priority' => '0.8'];
$links []= ['loc' => CANONICAL_URL . '/onions.php', 'changefreq' => 'daily', 'priority' => '1'];
$links []= ['loc' => CANONICAL_URL . '/onions.php?format=json', 'changefreq' => 'daily', 'priority' => '0.2'];
$links []= ['loc' => CANONICAL_URL . '/onions.php?format=text', 'changefreq' => 'daily', 'priority' => '0.2'];
$admin_approval = '';
if(REQUIRE_APPROVAL){
	$admin_approval = PREFIX . 'onions.approved = 1 AND';
}
foreach ($L as $lang_code => $lang){
	$links []= ['loc' => CANONICAL_URL . "/test.php?lang=$lang_code", 'changefreq' => 'weekly', 'priority' => '0.4'];
	$links []= ['loc' => CANONICAL_URL . "/onions.php?lang=$lang_code", 'changefreq' => 'daily', 'priority' => '0.5'];
	$stmt=$db->prepare('SELECT COUNT(*) FROM ' . PREFIX . "onions WHERE $admin_approval category=? AND address!='' AND id NOT IN (SELECT onion_id FROM " . PREFIX . 'phishing) AND timediff<604800;');
	foreach($categories as $cat => $name){
		$links []= ['loc' => CANONICAL_URL . "/onions.php?cat=$cat&lang=$lang_code", 'changefreq' => 'daily', 'priority' => '0.3'];
		$stmt->execute([$cat]);
		$num=$stmt->fetch(PDO::FETCH_NUM);
		$pages=ceil($num[0]/PER_PAGE);
		if($pages > 1) {
			while ( $pages > -1 ) {
				$links [] = [ 'loc' => CANONICAL_URL . "/onions.php?cat=$cat&pg=$pages&lang=$lang_code", 'changefreq' => 'daily', 'priority' => '0.3' ];
				--$pages;
			}
		}
	}
	$special=[
		$I['all']=>"address!='' AND category!=15 AND id NOT IN (SELECT onion_id FROM " . PREFIX . 'phishing) AND timediff<604800',
		$I['lastadded']=>"address!='' AND id NOT IN (SELECT onion_id FROM " . PREFIX . 'phishing)',
		$I['offline']=>"address!='' AND id NOT IN (SELECT onion_id FROM " . PREFIX . 'phishing) AND timediff>604800'
	];
	$cat=count($categories);
	foreach($special as $name=>$query){
		$links []= ['loc' => CANONICAL_URL . "/onions.php?cat=$cat&lang=$lang_code", 'changefreq' => 'daily', 'priority' => '0.3'];
		if($cat===count($categories)+1){
			$num[0]=PER_PAGE;
		}else{
			$num=$db->query('SELECT COUNT(*) FROM ' . PREFIX . "onions WHERE $admin_approval $query;")->fetch(PDO::FETCH_NUM);
		}
		$pages=ceil($num[0]/PER_PAGE);
		if($pages > 1) {
			while ( $pages > -1 ) {
				$links [] = [ 'loc' => CANONICAL_URL . "/onions.php?cat=$cat&pg=$pages&lang=$lang_code", 'changefreq' => 'daily', 'priority' => '0.3' ];
				--$pages;
			}
		}
		++$cat;
	}
	$links []= ['loc' => CANONICAL_URL . "/onions.php?cat=$cat&lang=$lang_code", 'changefreq' => 'daily', 'priority' => '0.3'];
}
$dom = new DOMDocument('1.0', 'UTF-8');
$urlset= $dom->createElement('urlset');
$urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
$urlset->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
$urlset->setAttribute('xsi:schemaLocation', 'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd');
$dom->appendChild($urlset);
foreach ($links as $link) {
	$url = $dom->createElement('url');
	$urlset->appendChild($url);
	$loc = $dom->createElement('loc', htmlspecialchars($link['loc']));
	$url->appendChild($loc);
	$changefreq = $dom->createElement('changefreq', $link['changefreq']);
	$url->appendChild($changefreq);
	$priority = $dom->createElement('priority', $link['priority']);
	$url->appendChild($priority);
}
header('Content-Type: text/xml; charset=UTF-8');
echo $dom->saveXML();
