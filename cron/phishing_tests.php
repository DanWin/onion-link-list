<?php
// Executed every 25 hours via cron - checks for phishing clones on known phishing sites.
date_default_timezone_set('UTC');
require_once(__DIR__.'/../common_config.php');
try{
	$db=new PDO('mysql:host=' . DBHOST . ';dbname=' . DBNAME . ';charset=utf8mb4', DBUSER, DBPASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_WARNING, PDO::ATTR_PERSISTENT=>PERSISTENT]);
}catch(PDOException $e){
	die($I['nodb']);
}
$ch=curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_PROXY, PROXY);
curl_setopt($ch, CURLOPT_PROXYTYPE, 7);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 25);
curl_setopt($ch, CURLOPT_TIMEOUT, 40);

check('http://tt3j2x4k5ycaa5zt.onion/onions.php?cat=15&pg=0', 'http://tt3j277rncfaqmj7.onion/onions.php?cat=15&pg=0');
check('http://skunksworkedp2cg.onion/sites.html', 'http://skunkrdunsylcfqd.onion/sites.html');

function check($link, $phishing_link){
	global $ch, $db;
	curl_setopt($ch, CURLOPT_URL, $link);
	$links=curl_exec($ch);
	curl_setopt($ch, CURLOPT_URL, $phishing_link);
	$phishing_links=curl_exec($ch);
	if(!empty($links) && !empty($phishing_links)){
		$phishings=$db->prepare('INSERT IGNORE INTO ' . PREFIX . 'phishing (onion_id, original) VALUES ((SELECT id FROM onions WHERE md5sum=?), ?);');
		$select=$db->prepare('SELECT id FROM ' . PREFIX . 'onions WHERE md5sum=?;');
		$insert=$db->prepare('INSERT INTO ' . PREFIX . 'onions (address, md5sum, timeadded) VALUES (?, ?, ?);');
		preg_match_all('~(https?://)?([a-z0-9]*\.)?([a-z2-7]{16}).onion(/[^\s><"]*)?~i', $links, $addr);
		preg_match_all('~(https?://)?([a-z0-9]*\.)?([a-z2-7]{16}).onion(/[^\s><"]*)?~i', $phishing_links, $phishing_addr);
		$count=count($addr[3]);
		if($count===count($phishing_addr[3])){ //only run with same data set
			for($i=0; $i<$count; ++$i){
				if($addr[3][$i]!==$phishing_addr[3][$i]){
					$address=strtolower($addr[3][$i]);
					$phishing_address=strtolower($phishing_addr[3][$i]);
					$md5=md5($phishing_address, true);
					$select->execute([$md5]);
					if(!$select->fetch(PDO::FETCH_NUM)){
						$insert->execute([$phishing_address, $md5, time()]);
					}
					$phishings->execute([$md5, $address]);
				}
			}
		}
	}
}
