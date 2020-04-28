<?php
// Cron job started every 15 minutes - up/down checks
include('../common_config.php');
try{
	$db=new PDO('mysql:host=' . DBHOST . ';dbname=' . DBNAME, DBUSER, DBPASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_WARNING, PDO::ATTR_PERSISTENT=>PERSISTENT]);
}catch(PDOException $e){
	die('No Connection to MySQL database!');
}
$stmt=$db->prepare('SELECT address, category, md5sum, description, id FROM ' . PREFIX . "onions WHERE address!='' AND lasttest<(?-86400) ORDER BY lasttest LIMIT 75;");
$stmt->execute([time()]);
$onions=$stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt=$db->prepare('SELECT * FROM ' . PREFIX . 'phishing WHERE onion_id=?;');

$mh = curl_multi_init();
$curl_handles = [];
//do tests
foreach($onions as $onion){
	$ch=curl_init();
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_USERAGENT, USERAGENT);
//	curl_setopt($ch, CURLOPT_PROXY, PROXY);
//	curl_setopt($ch, CURLOPT_PROXYTYPE, 7);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 50);
	curl_setopt($ch, CURLOPT_TIMEOUT, 50);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
	curl_setopt($ch, CURLOPT_ENCODING, '');
	curl_setopt($ch, CURLOPT_HTTPHEADER, ["Host: $onion[address].onion", 'User-Agent: Mozilla/5.0 (Windows NT 10.0; rv:68.0) Gecko/20100101 Firefox/68.0', 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', 'Accept-Language: en-US,en;q=0.5', 'Accept-Encoding: gzip, deflate', 'Connection: keep-alive', 'Upgrade-Insecure-Requests: 1']);
	curl_setopt($ch, CURLOPT_URL, "http://$onion[address].onion/");
	curl_multi_add_handle($mh, $ch);
	$curl_handles []= ['handle' => $ch, 'onion' => $onion];
}
unset($onions);
do {
	$status = curl_multi_exec($mh, $active);
	if ($active) {
		// Wait a short time for more activity
		curl_multi_select($mh);
	}
} while ($active && $status == CURLM_OK);
$online_stmt=$db->prepare('UPDATE ' . PREFIX . 'onions SET lasttest=?, lastup=lasttest, timediff=0 WHERE md5sum=?');
$offline_stmt=$db->prepare('UPDATE ' . PREFIX . 'onions SET lasttest=?, timediff=lasttest-lastup WHERE md5sum=? AND lasttest<?');
$desc_online_stmt=$db->prepare('UPDATE ' . PREFIX . 'onions SET description=?, category=0, locked=0 WHERE md5sum=?');
$desc_empty_stmt=$db->prepare('UPDATE ' . PREFIX . 'onions SET description=?, category=13, locked=1 WHERE md5sum=?');
$error_stmt=$db->prepare('UPDATE ' . PREFIX . 'onions SET category=13 WHERE md5sum=?'); //in case of error, move the address to an error category - edit the category id to fit yours!
$phishing_stmt=$db->prepare('INSERT INTO ' . PREFIX . 'phishing (onion_id, original) VALUES (?, ?);');
$db->beginTransaction();
foreach($curl_handles as $handle){
	$content = curl_multi_getcontent($handle['handle']);
	curl_multi_remove_handle($mh, $handle['handle']);
	$header_size = curl_getinfo($handle['handle'], CURLINFO_HEADER_SIZE);
	$http_code = curl_getinfo($handle['handle'], CURLINFO_HTTP_CODE);
	curl_close($handle['handle']);
	$onion = $handle['onion'];
	if($content!==''){
		$header = substr($content, 0, $header_size);
		$content = substr($content, $header_size);
		// update description to title, if not yet set
		if(($onion['description']==='' || $onion['description']==='Site hosted by Daniel\'s hosting service') && preg_match('~<title>([^<]+)</title>~i', $content, $match)){
			$desc=preg_replace("/(\r?\n|\r\n?)/", '<br>', htmlspecialchars(html_entity_decode(trim($match[1]))));
			if($desc!=='Site hosted by Daniel\'s hosting service'){
				$desc_online_stmt->execute([$desc, $onion['md5sum']]);
			}else{
				$desc_empty_stmt->execute([$desc, $onion['md5sum']]);
			}
		}
		$online_stmt->execute([time(), $onion['md5sum']]);
		// checks for server errors, to move the address to a dedicated error category
		if($onion['category']==0 && $http_code>=400){
			$error_stmt->execute([$onion['md5sum']]);
		}
		$stmt->execute([$onion['id']]);
		if(!$stmt->fetch(PDO::FETCH_NUM)){
			if(preg_match('~^HTTP/1\.(1|0) 504 Connect to ([a-z2-7]{16}|[a-z2-7]{56})\.onion(:80)? failed: SOCKS error: host unreachable~', $content, $match)){
				$phishing_stmt->execute([$onion['id'], $match[2]]);
			}elseif(strpos($content, "<body>HttpReadDisconnect('Server disconnected',)</body>")!==false){
				$phishing_stmt->execute([$onion['id'], '']);
			}
		}
		if(preg_match('~window\.location\.replace\("http://'.$onion['address'].'.onion/(.*?)"\)~', $content, $matches)){
			$ch=curl_init();
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_USERAGENT, USERAGENT);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 50);
			curl_setopt($ch, CURLOPT_TIMEOUT, 50);
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
			curl_setopt($ch, CURLOPT_ENCODING, '');
			curl_setopt($ch, CURLOPT_HTTPHEADER, ["Host: $onion[address].onion", 'User-Agent: Mozilla/5.0 (Windows NT 10.0; rv:68.0) Gecko/20100101 Firefox/68.0', 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', 'Accept-Language: en-US,en;q=0.5', 'Accept-Encoding: gzip, deflate', 'Connection: keep-alive', 'Upgrade-Insecure-Requests: 1']);
			curl_setopt($ch, CURLOPT_URL, "http://$onion[address].onion/".$matches[1]);
			$content=curl_exec($ch);
		}
		if(preg_match('~^refresh:.*url=(https?://[^;\s]+).*?$~m', $header, $matches)){
			$ch=curl_init();
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_USERAGENT, USERAGENT);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 50);
			curl_setopt($ch, CURLOPT_TIMEOUT, 50);
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
			curl_setopt($ch, CURLOPT_ENCODING, '');
			curl_setopt($ch, CURLOPT_HTTPHEADER, ["Host: $onion[address].onion", 'User-Agent: Mozilla/5.0 (Windows NT 10.0; rv:68.0) Gecko/20100101 Firefox/68.0', 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', 'Accept-Language: en-US,en;q=0.5', 'Accept-Encoding: gzip, deflate', 'Connection: keep-alive', 'Upgrade-Insecure-Requests: 1']);
			curl_setopt($ch, CURLOPT_URL, $matches[1]);
			$content=curl_exec($ch);
		}
		if(preg_match_all('~<meta[^>]+http-equiv="refresh"[^>]+content="(\d+);[^>]*url=([^>"]+)">~', $content, $matches, PREG_SET_ORDER)){
			$time = null;
			$link_to_check = '';
			foreach($matches as $match){
				if($time === null || $time > $match[1]){
					$time = $match[1];
					$link_to_check = $match[2];
				}
			}
			$ch=curl_init();
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_USERAGENT, USERAGENT);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 50);
			curl_setopt($ch, CURLOPT_TIMEOUT, 50);
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
			curl_setopt($ch, CURLOPT_ENCODING, '');
			curl_setopt($ch, CURLOPT_HTTPHEADER, ["Host: $onion[address].onion", 'User-Agent: Mozilla/5.0 (Windows NT 10.0; rv:68.0) Gecko/20100101 Firefox/68.0', 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', 'Accept-Language: en-US,en;q=0.5', 'Accept-Encoding: gzip, deflate', 'Connection: keep-alive', 'Upgrade-Insecure-Requests: 1']);
			curl_setopt($ch, CURLOPT_URL, $link_to_check);
			$content=curl_exec($ch);
		}
		blacklist_scams($onion['address'], $content);
	}else{
		$offline_stmt->execute([time(), $onion['md5sum'], time()]);
	}
}
$db->commit();
curl_multi_close($mh);
