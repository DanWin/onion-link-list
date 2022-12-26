<?php
require_once(__DIR__.'/../common_config.php');
try{
	$db=new PDO('mysql:host=' . DBHOST . ';dbname=' . DBNAME . ';charset=utf8mb4', DBUSER, DBPASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_WARNING, PDO::ATTR_PERSISTENT=>true]);
}catch(PDOException $e){
	die(_('No database connection!'));
}
$stmt=$db->query("SELECT onions.address FROM onions LEFT JOIN phishing ON (phishing.onion_id=onions.id) WHERE onions.address!='' AND onions.category!=15 AND isnull(phishing.onion_id) AND timeadded>1506800000;");
$move=$db->prepare("UPDATE onions SET category=15, locked=1, description='WARNING - This site will crash your browser with infinite iframes.', timechanged=? WHERE address=?;");
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
	if(preg_match("~HTTP/1\.1\s404\sNot\sFound\r\nContent-Type:\stext/plain;\scharset=utf-8\r\nX-Content-Type-Options:\snosniff\r\nDate: .* GMT\r\nContent-Length:\s19~", $header)){
		echo " - SCAM - moved";
		$move->execute([$time, $onion['address']]);
	}
	if(preg_match('~Expires:\sThu,\s19\sNov\s1981\s08:52:00\sGMT\r\n~', $header) && $body==='HTTP error'){
		echo " - SCAM - moved";
		$move->execute([$time, $onion['address']]);
	}
	if(preg_match('~Expires:\sThu,\s19\sNov\s1981\s08:52:00\sGMT\r\nCache-Control:\sno-store,\sno-cache,\smust-revalidate\r\nPragma: no-cache\r\nServer: anon\r\n~', $header)){
		echo " - SCAM - moved";
		$move->execute([$time, $onion['address']]);
	}
	if(preg_match('~Expires:\sThu,\s19\sNov\s1981\s08:52:00\sGMT\r\nCache-Control:\sno-store,\sno-cache,\smust-revalidate\r\nPragma: no-cache\r\ncontent-length: 0\r\n~', $header) && $body!==''){
		echo " - SCAM - moved";
		$move->execute([$time, $onion['address']]);
	}
	if(preg_match('~^HTTP/1\.1\s500\sInternal\sServer\sError\r\n~', $header) && $body===''){
		echo " - SCAM";
	}
	if(preg_match('~^HTTP/1\.1\s200\sOK\r\n~', $header) && $body==='404'){
		echo " - SCAM";
	}
	echo "\n";
}
