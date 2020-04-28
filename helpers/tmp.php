<?php
include('../common_config.php');
try{
        $db=new PDO('mysql:host=' . DBHOST . ';dbname=' . DBNAME . ';charset=utf8mb4', DBUSER, DBPASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_WARNING, PDO::ATTR_PERSISTENT=>true]);
}catch(PDOException $e){
        die('No Connection to MySQL database!');
}
$stmt=$db->query("SELECT onions.address FROM onions LEFT JOIN phishing ON (phishing.onion_id=onions.id) WHERE onions.address!='' AND onions.category!=15 AND onions.category!=18 AND isnull(phishing.onion_id) LIMIT 2100,10000;");
$move=$db->prepare("UPDATE onions SET category=18, locked=1 WHERE address=?;");
$ch=curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_HEADER, true);
while($tmp=$stmt->fetch(PDO::FETCH_NUM)){
curl_setopt($ch, CURLOPT_URL, "http://".gethostbyname("$tmp[0].onion"));
$response=curl_exec($ch);
$curl_info=curl_getinfo($ch);
$header_size = $curl_info['header_size'];
$header = substr($response, 0, $header_size);
$body = substr($response, $header_size);
curl_setopt($ch, CURLOPT_URL, "http://$tmp[0].onion");
$response2=curl_exec($ch);
$curl_info2=curl_getinfo($ch);
$header_size2 = $curl_info2['header_size'];
$header2 = substr($response2, 0, $header_size2);
$body2 = substr($response2, $header_size2);
echo $tmp[0];
if(preg_match('~Expires:\sThu,\s19\sNov\s1981\s08:52:00\sGMT\r\n~', $header)){
$move->execute($tmp);
echo " - SCAM - moved";
}
elseif(preg_match('~Expires: Sat, 17 Jun 2000 12:00:00 GMT\r\n~', $header)){
$move->execute($tmp);
echo " - SCAM - moved";
}
elseif(preg_match('~Last-Modified:\sWed,\s08\sJun\s1955\s12:00:00\sGMT\r\n~', $header)){
$move->execute($tmp);
echo " - SCAM - moved";
}
elseif(preg_match('~^HTTP/1\.1\s500\sInternal\sServer\sError\r\n~', $header) && $body==='' && preg_match('~^HTTP/1\.1\s500\sOK\r\n~', $header2)){
$move->execute($tmp);
echo " - SCAM - moved";
}
elseif(preg_match('~^HTTP/1\.1\s500\sInternal\sServer\sError\r\n~', $header) && $body==='' && preg_match('~Connection:\s\[object\sObject\]\r\n~', $header2)){
$move->execute($tmp);
echo " - SCAM - moved";
}
elseif(preg_match('~^HTTP/1\.1\s200\sOK\r\nServer:\snginx/1\.6\.2~', $header) && $body==='404'){
$move->execute($tmp);
echo " - SCAM - moved";
}
elseif(preg_match('~^HTTP/1\.1\s302\sFound\r\nLocation:\s/\r\n~', $header) && $body==='Found. Redirecting to /'){
$move->execute($tmp);
echo " - SCAM - moved";
}
elseif(preg_match('~^HTTP/1\.1\s503\sForwarding\sfailure~', $header)){
$move->execute($tmp);
echo " - SCAM - moved";
}
echo "\n";
}
curl_close($ch);
