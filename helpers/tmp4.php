<?php
require_once(__DIR__.'/../common_config.php');
try{
	$db=new PDO('mysql:host=' . DBHOST . ';dbname=' . DBNAME . ';charset=utf8mb4', DBUSER, DBPASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_WARNING, PDO::ATTR_PERSISTENT=>true]);
}catch(PDOException $e){
	die(_('No database connection!'));
}
$stmt=$db->query("SELECT onions.address FROM onions LEFT JOIN phishing ON (phishing.onion_id=onions.id) WHERE onions.address!='' AND onions.locked=0 AND isnull(phishing.onion_id);");
$move=$db->prepare("UPDATE onions SET category=18, locked=1, description='CP - SCAM', timechanged=? WHERE address=?;");
while($onion=$stmt->fetch(PDO::FETCH_ASSOC)){
	$ch=curl_init();
	set_curl_options($ch);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_NOBODY, true);
	curl_setopt($ch, CURLOPT_URL, "http://".gethostbyname("$onion[address].onion"));
	$response=curl_exec($ch);
	$curl_info=curl_getinfo($ch);
	$header_size = $curl_info['header_size'];
	$header = substr($response, 0, $header_size);
	$body = substr($response, $header_size);
	curl_close($ch);
	$time = time();
	echo "$onion[address].onion";
	if(preg_match('~Last-Modified:\sSat,\s03\sAug\s2019\s15:40:54\sGMT\r\n~', $header)){
		echo " - SCAM - moved";
		$move->execute([$time, $onion['address']]);
	}
	if(preg_match('~Last-Modified:\sWed,\s03\sJul\s2019\s19:53:24\sGMT\r\n~', $header)){
		echo " - SCAM - moved";
		$move->execute([$time, $onion['address']]);
	}
	if(preg_match('~Last-Modified:\sTue,\s30\sJul\s2019\s19:11:00\sGMT\r\n~', $header)){
		echo " - SCAM - moved";
		$move->execute([$time, $onion['address']]);
	}
	echo "\n";
}
