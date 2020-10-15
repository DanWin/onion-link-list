<?php
// Executed daily via cronjob - checks for new sites.
require_once(__DIR__.'/../common_config.php');
try{
	$db=new PDO('mysql:host=' . DBHOST . ';dbname=' . DBNAME, DBUSER, DBPASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_WARNING, PDO::ATTR_PERSISTENT=>PERSISTENT]);
}catch(PDOException $e){
	die($I['nodb']);
}
$ch=curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, USERAGENT);
curl_setopt($ch, CURLOPT_PROXY, PROXY);
curl_setopt($ch, CURLOPT_PROXYTYPE, 7);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 25);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_ENCODING, '');
$onions=[];
$scanned_onions=[];

//sources to get links from
check_links($onions, $ch, 'http://q3lgwxinynjxkor6wghr6hrhlix7fquja3t25phbagqizkpju36fwdyd.onion/list.php');
check_links($onions, $ch, 'https://tt3j2x4k5ycaa5zt.onion.link/antanistaticmap/stats/yesterday');
check_links($onions, $ch, 'https://tt3j2x4k5ycaa5zt.onion.sh/antanistaticmap/stats/yesterday');
check_links($onions, $ch, 'https://tt3j2x4k5ycaa5zt.tor2web.io/antanistaticmap/stats/yesterday');
check_links($onions, $ch, 'http://visitorfi5kl7q7i.onion/address/');
check_links($onions, $ch, 'http://dhosting4xxoydyaivckq7tsmtgi4wfs3flpeyitekkmqwu4v4r46syd.onion/list.php');
check_links($onions, $ch, 'http://3bbaaaccczcbdddz.onion/discover');
check_links($onions, $ch, 'http://tor66sezptuu2nta.onion/fresh');
check_links($onions, $ch, 'https://crt.sh/?q=.onion&exclude=expired&deduplicate=Y');
check_links($onions, $ch, 'http://darkeyeb643f2syd.onion/');
check_links($onions, $ch, 'http://darktorhvabc652txfc575oendhykqcllb7bh7jhhsjduocdlyzdbmqd.onion/hidden-wiki-onion-deepweb-tor-links-darknet.html');
check_links($onions, $ch, 'http://vladhz5tmikfgxzfa2nk7nxah7x5msa5z5ygb75xb5nsizmeht2dazyd.onion/list.php');
check_links($onions, $ch, 'http://raptortiabg7uyez.onion/');

//add them to the database
add_onions($onions, $db);
//delete links that were not seen within a month
$db->exec('DELETE FROM ' . PREFIX . "onions WHERE address!='' AND timediff>2419200 AND lasttest-timeadded>2419200;");

function check_links(&$onions, &$ch, $link_to_check, $scan_children = false, &$scanned_onoins = []){
	curl_setopt($ch, CURLOPT_URL, $link_to_check);
	$links=curl_exec($ch);
	if(preg_match_all('~(https?://)?([a-z0-9]*\.)?([a-z2-7]{16}|[a-z2-7]{56}).onion(/[^\s><"]*)?~i', $links, $addr)){
		if($scan_children){
			$mh = curl_multi_init();
			$curl_handles = [];
		}
		foreach($addr[3] as $link){
			$link=strtolower($link);
			$md5=md5($link, true);
			$onions[$md5]=$link;
			if($scan_children && empty($scanned_onions[$md5])){
				$scanned_onions[$md5]=$link;
				$ch_child=curl_init();
				curl_setopt($ch_child, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch_child, CURLOPT_USERAGENT, USERAGENT);
				curl_setopt($ch_child, CURLOPT_PROXY, PROXY);
				curl_setopt($ch_child, CURLOPT_PROXYTYPE, 7);
				curl_setopt($ch_child, CURLOPT_CONNECTTIMEOUT, 25);
				curl_setopt($ch_child, CURLOPT_TIMEOUT, 30);
				curl_setopt($ch_child, CURLOPT_ENCODING, '');
				curl_setopt($ch_child, CURLOPT_URL, "http://$link.onion");
				curl_multi_add_handle($mh, $ch_child);
				$curl_handles []= $ch_child;
//				check_links($onions, $ch, "http://$link.onion", $scan_children, $scanned_onions);
			}
		}
		if($scan_children){
			//execute the multi handle
			do {
				$status = curl_multi_exec($mh, $active);
				if ($active) {
					// Wait a short time for more activity
					curl_multi_select($mh);
				}
			} while ($active && $status == CURLM_OK);
			foreach($curl_handles as $handle){
				$content = curl_multi_getcontent($handle);
				if(preg_match_all('~(https?://)?([a-z0-9]*\.)?([a-z2-7]{16}|[a-z2-7]{56}).onion(/[^\s><"]*)?~i', $content, $addr)){
					foreach($addr[3] as $link){
						$link=strtolower($link);
						$md5=md5($link, true);
						if(empty($onions[$md5])){
							$onions[$md5]=$link;
						}
					}
				}
				curl_multi_remove_handle($mh, $handle);
			}
			curl_multi_close($mh);
		}
	}
}

function add_onions(&$onions, $db){
//	$update=$db->prepare('UPDATE ' . PREFIX . "onions SET address = '', locked=1, description=CONCAT(description, ' - SCAM'), category=15 WHERE md5sum=? AND address!='';");
	$stmt=$db->query('SELECT md5sum FROM ' . PREFIX . 'onions;');
	while($tmp=$stmt->fetch(PDO::FETCH_NUM)){
		if(isSet($onions[$tmp[0]])){
			unset($onions[$tmp[0]]);
//			$update->execute($tmp);
		}
	}
	$time=time();
	$insert=$db->prepare('INSERT INTO ' . PREFIX . 'onions (address, md5sum, timeadded) VALUES (?, ?, ?);');
	$db->beginTransaction();
	foreach($onions as $md5=>$addr){
		$insert->execute([$addr, $md5, $time]);
	}
	$db->commit();
}
