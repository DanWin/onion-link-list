<?php
require_once(__DIR__.'/../common_config.php');
try{
	$db=new PDO('mysql:host=' . DBHOST . ';dbname=' . DBNAME . ';charset=utf8mb4', DBUSER, DBPASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_WARNING, PDO::ATTR_PERSISTENT=>true]);
}catch(PDOException $e){
	die(_('No database connection!'));
}
$stmt=$db->query("SELECT onions.address FROM onions LEFT JOIN phishing ON (phishing.onion_id=onions.id) WHERE onions.address!='' AND onions.locked=0 AND isnull(phishing.onion_id);");
$move=$db->prepare("UPDATE onions SET category=18, locked=1, description='Part of scam network - SCAM', timechanged=? WHERE address=?;");
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
	echo "$onion[address].onion";
	if(preg_match('~Last-Modified:\sFri,\s21\sDec\s2018\s17:30:54\sGMT\r\n~', $header)){
		echo " - SCAM - moved";
		$move->execute([time(), $onion['address']]);
	}
	echo "\n";
}
