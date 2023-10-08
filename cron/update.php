<?php
// Executed daily via cronjob - checks for new sites.
require_once(__DIR__.'/../common_config.php');
try{
	$db=new PDO('mysql:host=' . DBHOST . ';dbname=' . DBNAME, DBUSER, DBPASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_WARNING, PDO::ATTR_PERSISTENT=>PERSISTENT]);
}catch(PDOException $e){
	die(_('No database connection!'));
}
$ch=curl_init();
set_curl_options($ch);
curl_setopt($ch, CURLOPT_ENCODING, '');
$onions=[];
$scanned_onions=[];

//sources to get links from
check_links($onions, $ch, 'http://3bbad7fauom4d6sgppalyqddsqbf5u5p56b5k5uk2zxsy3d6ey2jobad.onion/discover');
check_links($onions, $ch, 'http://tor66sewebgixwhcqfnp5inzp5x5uohhdy3kvtnyfxc2e5mxiuh34iid.onion/fresh');
check_links($onions, $ch, 'https://crt.sh/?q=.onion&exclude=expired&deduplicate=Y');
check_links($onions, $ch, 'http://darkeyepxw7cuu2cppnjlgqaav6j42gyt43clcn4vjjf7llfyly5cxid.onion/');
check_links($onions, $ch, 'http://raptora2y6r3bxmjcd3xglr3tcakc6ezq3omyzbnvwahhpi27l3w4yad.onion/');
check_links($onions, $ch, 'http://darkeyepxw7cuu2cppnjlgqaav6j42gyt43clcn4vjjf7llfyly5cxid.onion/');
for($i=11; $i > 0; --$i){
	check_links($onions, $ch, 'https://onionlandsearchengine.com/discover?p='.$i);
}
check_links($onions, $ch, 'https://godnotaba.fun/');
check_links($onions, $ch, 'http://links.communzyxz3qfpum5tnvrfvvrr4jlosbq4mzeskigoionqqdylmlhmid.onion/?format=text');

//add them to the database
add_onions($onions, $db);
//delete links that were not seen within a month
$db->exec('DELETE FROM ' . PREFIX . "onions WHERE address!='' AND timediff>2419200 AND lasttest-timeadded>2419200;");

function check_links(array &$onions, $ch, string $link_to_check, bool $scan_children = false, array &$scanned_onoins = []): void
{
	curl_setopt($ch, CURLOPT_URL, $link_to_check);
	$links=curl_exec($ch);
	if(preg_match_all('~(https?://)?([a-z0-9]*\.)?([a-z2-7]{55}d).onion(/[^\s><"]*)?~i', $links, $addr)){
		$mh = null;
		$curl_handles = [];
		if($scan_children){
			$mh = curl_multi_init();
		}
		foreach($addr[3] as $link){
			$link=strtolower($link);
			$md5=md5($link, true);
			$onions[$md5]=$link;
			if($scan_children && empty($scanned_onions[$md5])){
				$scanned_onions[$md5]=$link;
				$ch_child=curl_init();
				set_curl_options($ch_child);
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
				if(preg_match_all('~(https?://)?([a-z0-9]*\.)?([a-z2-7]{55}d).onion(/[^\s><"]*)?~i', $content, $addr)){
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

function add_onions(&$onions, $db): void
{
	$stmt=$db->query('SELECT md5sum FROM ' . PREFIX . 'onions;');
	while($tmp=$stmt->fetch(PDO::FETCH_NUM)){
		if(isset($onions[$tmp[0]])){
			unset($onions[$tmp[0]]);
		}
	}
	$time=time();
	$insert=$db->prepare('INSERT INTO ' . PREFIX . 'onions (address, md5sum, timeadded, timechanged, description) VALUES (?, ?, ?, ?, "");');
	$db->beginTransaction();
	foreach($onions as $md5=>$addr){
		$insert->execute([$addr, $md5, $time, $time]);
	}
	$db->commit();
}
