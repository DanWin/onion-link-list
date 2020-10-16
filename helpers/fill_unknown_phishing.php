<?php
require_once(__DIR__.'/../common_config.php');
try{
	$db=new PDO('mysql:host=' . DBHOST . ';dbname=' . DBNAME, DBUSER, DBPASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_WARNING, PDO::ATTR_PERSISTENT=>PERSISTENT]);
}catch(PDOException $e){
	die('No Connection to MySQL database!');
}
$ch=curl_init();
set_curl_options($ch);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
$online=$offline=$desc_online=$error=[];
$stmt=$db->prepare("SELECT address FROM onions INNER JOIN phishing ON (phishing.onion_id=onions.id) WHERE address!='' AND phishing.original='';");
$stmt->execute([time()]);
$onions=$stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt=$db->prepare('UPDATE phishing, onions SET phishing.original=? WHERE phishing.onion_id=onions.id AND onions.address=?;');

//do tests
foreach($onions as $onion){
	curl_setopt($ch, CURLOPT_URL, "http://$onion[address].onion/");
	if(($site=curl_exec($ch))!==false){
		preg_match('~(https?://)?([a-z0-9]*\.)?([a-z2-7]{16}|[a-z2-7]{56}).onion(/[^\s><"]*)?~i', $site, $addr);
		if($addr[3]!='' && $addr[3]!==$onion['address']){
			echo "scam: $onion[address] - original: $addr[3]\n";
			$stmt->execute([$addr[3], $onion['address']]);
		}
	}
}
curl_close($ch);
