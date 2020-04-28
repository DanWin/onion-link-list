<?php
include('../common_config.php');
try{
        $db=new PDO('mysql:host=' . DBHOST . ';dbname=' . DBNAME . ';charset=utf8mb4', DBUSER, DBPASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_WARNING, PDO::ATTR_PERSISTENT=>true]);
}catch(PDOException $e){
        die('No Connection to MySQL database!');
}
$stmt=$db->query("SELECT onions.address FROM onions LEFT JOIN phishing ON (phishing.onion_id=onions.id) WHERE onions.address!='' AND isnull(phishing.onion_id) AND onions.id>22439;");
$move=$db->prepare("UPDATE onions SET category=18, locked=1, description='Add injecting phishing clone of an existing site - SCAM' WHERE address=?;");
$ch=curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
//curl_setopt($ch, CURLOPT_HEADER, true);
while($tmp=$stmt->fetch(PDO::FETCH_NUM)){
	curl_setopt($ch, CURLOPT_URL, "http://".gethostbyname("$tmp[0].onion"));
	$response=curl_exec($ch);
	if($response==='<!-- <meta http-equiv="refresh"content="0; url=http://o2nlo5zjoxp25kfv.onion"> -->
'){
		$move->execute($tmp);
		echo " - SCAM - moved";
	}
}
curl_close($ch);
