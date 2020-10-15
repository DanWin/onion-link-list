<?php
require_once('../common_config.php');
try{
        $db=new PDO('mysql:host=' . DBHOST . ';dbname=' . DBNAME . ';charset=utf8mb4', DBUSER, DBPASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_WARNING, PDO::ATTR_PERSISTENT=>true]);
}catch(PDOException $e){
        die('No Connection to MySQL database!');
}
$stmt=$db->query("SELECT onions.address FROM onions LEFT JOIN phishing ON (phishing.onion_id=onions.id) WHERE onions.address!='' AND onions.category!=15 AND isnull(phishing.onion_id) AND timeadded>1506800000;");
$move=$db->prepare("UPDATE onions SET category=15, locked=1, description='WARNING - This site will crash your browser with infinite iframes.' WHERE address=?;");
while($tmp=$stmt->fetch(PDO::FETCH_NUM)){
$ch=curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_PROXY, '127.0.0.1:9050');
curl_setopt($ch, CURLOPT_PROXYTYPE, 7);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_URL, "http://".gethostbyname("$tmp[0].onion"));
$response=curl_exec($ch);
$curl_info=curl_getinfo($ch);
$header_size = $curl_info['header_size'];
$header = substr($response, 0, $header_size);
$body = substr($response, $header_size);
curl_close($ch);
//if(preg_match('~Location:\s/\r\n~', $header)){
echo "$tmp[0].onion";
if(preg_match("~HTTP/1\.1\s404\sNot\sFound\r\nContent-Type:\stext/plain;\scharset=utf-8\r\nX-Content-Type-Options:\snosniff\r\nDate: .* GMT\r\nContent-Length:\s19~", $header)){
echo " - SCAM - moved";
$move->execute($tmp);
}
if(preg_match('~Expires:\sThu,\s19\sNov\s1981\s08:52:00\sGMT\r\n~', $header) && $body==='HTTP error'){
echo " - SCAM - moved";
$move->execute($tmp);
}
if(preg_match('~Expires:\sThu,\s19\sNov\s1981\s08:52:00\sGMT\r\nCache-Control:\sno-store,\sno-cache,\smust-revalidate\r\nPragma: no-cache\r\nServer: anon\r\n~', $header)){
echo " - SCAM - moved";
$move->execute($tmp);
}
if(preg_match('~Expires:\sThu,\s19\sNov\s1981\s08:52:00\sGMT\r\nCache-Control:\sno-store,\sno-cache,\smust-revalidate\r\nPragma: no-cache\r\ncontent-length: 0\r\n~', $header) && $body!==''){
echo " - SCAM - moved";
$move->execute($tmp);
}
if(preg_match('~^HTTP/1\.1\s500\sInternal\sServer\sError\r\n~', $header) && $body===''){
echo " - SCAM";
}
if(preg_match('~^HTTP/1\.1\s200\sOK\r\n~', $header) && $body==='404'){
echo " - SCAM";
}
echo "\n";
}
