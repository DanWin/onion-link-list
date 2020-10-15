<?php
require_once(__DIR__.'/../common_config.php');
try{
        $db=new PDO('mysql:host=' . DBHOST . ';dbname=' . DBNAME . ';charset=utf8mb4', DBUSER, DBPASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_WARNING, PDO::ATTR_PERSISTENT=>true]);
}catch(PDOException $e){
        die('No Connection to MySQL database!');
}
$stmt=$db->prepare("SELECT null FROM onions WHERE address = ?;");
$move=$db->prepare("UPDATE onions SET category=18, locked=1, description=CONCAT(description, ' - Part of scam network - SCAM') WHERE address = ? AND locked=0;");
$insert=$db->prepare('INSERT INTO onions (address, md5sum, timeadded, locked, description, category) VALUES (?, ?, ?, 1, "Part of scam network - SCAM", 18);');
for($i = 1; $i < 213; ++$i){
	$ch=curl_init();
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_PROXY, '127.0.0.1:9050');
	curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5_HOSTNAME);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_NOBODY, true);
	curl_setopt($ch, CURLOPT_URL, "http://kenimar6g7h2z75m.onion/go.php?id=$i");
	$response=curl_exec($ch);
	$curl_info=curl_getinfo($ch);
	$header_size = $curl_info['header_size'];
	$header = substr($response, 0, $header_size);
	$body = substr($response, $header_size);
	curl_close($ch);
	if(preg_match('~(https?://)?([a-z0-9]*\.)?([a-z2-7]{16}|[a-z2-7]{56}).onion(/[^\s><"]*)?~i', $header, $addr)){
		$stmt->execute([$addr[3]]);
		if($stmt->fetch()){
			$move->execute([$addr[3]]);
			echo "SCAM - moved - $addr[3] - ";
		}else{
			$insert->execute([$addr[3], md5($addr[3], true), time()]);
			echo "SCAM - added - $addr[3] - ";
		}
	}
	echo "$i\n";
}
