<?php
require_once(__DIR__.'/../common_config.php');
$style = '.red{color:red} .green{color:green}';
send_headers([$style]);
echo '<!DOCTYPE html><html><head>';
echo "<title>$I[testtitle]</title>";
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
echo '<meta name="author" content="Daniel Winzen">';
echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
echo '<style type="text/css">'.$style.'</style>';
echo '</head><body>';
echo '<h1>Online-Test</h1>';
print_langs();
echo "<p>$I[testdesc]</p>";
echo "<form action=\"$_SERVER[SCRIPT_NAME]\" method=\"POST\">";
echo "<input type=\"hidden\" name=\"lang\" value=\"$language\">";
echo "<p>$I[link]: <br><input name=\"addr\" size=\"30\" value=\"";
if(isset($_REQUEST['addr'])){
	echo htmlspecialchars($_REQUEST['addr']);
}else{
	echo "http://$_SERVER[HTTP_HOST]";
}
echo '" required></p>';
echo "<input type=\"submit\" name=\"action\" value=\"$I[test]\"></form><br>";
if(!empty($_REQUEST['addr'])){
	if(ob_get_level()>0){
		ob_end_flush();
	}
	try{
		$db=new PDO('mysql:host=' . DBHOST . ';dbname=' . DBNAME . ';charset=utf8mb4', DBUSER, DBPASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_WARNING, PDO::ATTR_PERSISTENT=>PERSISTENT]);
	}catch(PDOException $e){
		die('No DB connection');
	}
	if(!preg_match('~(^(https?://)?([a-z0-9]*\.)?([a-z2-7]{16}|[a-z2-7]{56})(\.onion(/.*)?)?$)~i', trim($_REQUEST['addr']), $addr)){
		echo "<p class=\"red\">$I[invalonion]</p>";
	}else{
		$ch=curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_USERAGENT, USERAGENT);
		curl_setopt($ch, CURLOPT_PROXY, PROXY);
		curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5_HOSTNAME);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_URL, "http://$addr[4].onion/");
		curl_setopt($ch, CURLOPT_HTTPHEADER, ["Host: $addr[4].onion", 'User-Agent: Mozilla/5.0 (Windows NT 10.0; rv:68.0) Gecko/20100101 Firefox/68.0', 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', 'Accept-Language: en-US,en;q=0.5', 'Accept-Encoding: gzip, deflate', 'Connection: keep-alive', 'Upgrade-Insecure-Requests: 1']);
		$addr=strtolower($addr[4]);
		$md5=md5($addr, true);
		//display warning, if a phishing clone was tested
		$phishing=$db->prepare('SELECT original FROM ' . PREFIX . 'phishing, ' . PREFIX . 'onions WHERE address=? AND onion_id=' . PREFIX . 'onions.id;');
		$phishing->execute([$addr]);
		if($orig=$phishing->fetch(PDO::FETCH_NUM)){
			printf("<p class=\"red\">$I[testphishing]</p>", "<a href=\"http://$orig[0].onion\">$orig[0].onion</a>");
		}
		$scam=$db->prepare('SELECT null FROM ' . PREFIX . 'onions WHERE md5sum=? AND category=15 AND locked=1;');
		$scam->execute([$md5]);
		if($scam->fetch(PDO::FETCH_NUM)){
			echo "<p class=\"red\">Warning: This is a known scam!</p>";
		}
		$stmt=$db->prepare('SELECT null FROM ' . PREFIX . 'onions WHERE md5sum=? AND timediff=0 AND lasttest>?;');
		$stmt->execute([$md5, time()-60]);
		if($stmt->fetch(PDO::FETCH_NUM)){
			echo "<p class=\"green\">$I[testonline]</p>";
		}elseif(($content=curl_exec($ch))!==false){
			$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$header = substr($content, 0, $header_size);
			$content = substr($content, $header_size);
			if(isset($db)){
				//update entry in database
				$stmt=$db->prepare('SELECT null FROM ' . PREFIX . 'onions WHERE md5sum=?;');
				$stmt->execute([$md5]);
				if(!$stmt->fetch(PDO::FETCH_NUM)){
					$db->prepare('INSERT INTO ' . PREFIX . 'onions (address, md5sum, timeadded) VALUES (?, ?, ?);')->execute([$addr, $md5, time()]);
				}
				$db->prepare('UPDATE ' . PREFIX . 'onions SET lasttest=?, lastup=lasttest, timediff=0 WHERE md5sum=?;')->execute([time(), $md5]);
				if(preg_match('~window\.location\.replace\("http://'.$addr.'.onion/(.*?)"\)~', $content, $matches)){
					curl_setopt($ch, CURLOPT_URL, "http://$addr.onion/".$matches[1]);
					$content=curl_exec($ch);
				}
				if(preg_match('~^refresh:.*url=(https?://[^;\s]+).*?$~m', $header, $matches)){
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
					curl_setopt($ch, CURLOPT_URL, $link_to_check);
					$content=curl_exec($ch);
				}
				blacklist_scams($addr, $content);
			}
			echo "<p class=\"green\">$I[testonline]</p>";
		}else{
			if(isset($db)){
				$time=time();
				$db->prepare('UPDATE ' . PREFIX . 'onions SET lasttest=?, timediff=lasttest-lastup WHERE md5sum=? AND lasttest<?;')->execute([$time, $md5, $time]);
			}
			echo "<p class=\"red\">$I[testoffline]</p>";
		}
		curl_close($ch);
	}
}
echo '<br><p class="software-link"><a target="_blank" href="https://github.com/DanWin/onion-link-list" rel="noopener">Onion Link List - ' . VERSION . '</a></p>';
echo '</body></html>';
