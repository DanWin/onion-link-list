<?php
require_once(__DIR__.'/../common_config.php');
try{
	$db=new PDO('mysql:host=' . DBHOST . ';dbname=' . DBNAME . ';charset=utf8mb4', DBUSER, DBPASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_WARNING, PDO::ATTR_PERSISTENT=>true]);
}catch(PDOException $e){
	die('No Connection to MySQL database!');
}
$stmt=$db->query("SELECT onions.address FROM onions LEFT JOIN phishing ON (phishing.onion_id=onions.id) WHERE onions.address!='' AND isnull(phishing.onion_id) AND onions.id>22439;");
$move=$db->prepare("UPDATE onions SET category=18, locked=1, description='Add injecting phishing clone of an existing site - SCAM', timechanged=? WHERE address=?;");
$ch=curl_init();
set_curl_options($ch);
while($onion=$stmt->fetch(PDO::FETCH_ASSOC)){
	curl_setopt($ch, CURLOPT_URL, "http://".gethostbyname("$onion[address].onion"));
	$response=curl_exec($ch);
	if($response==='<!-- <meta http-equiv="refresh"content="0; url=http://o2nlo5zjoxp25kfv.onion"> -->
'){
		$move->execute([time(), $onion['address']]);
		echo " - SCAM - moved";
	}
}
curl_close($ch);
