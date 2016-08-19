<?php
/*
* Onion Link List - Automated up/down tests
*
* Copyright (C) 2016 Daniel Winzen <d@winzen4.de>
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// Executed every 15 minutes via cron - up/down checks
include('common_config.php');
try{
	$db=new PDO('mysql:host=' . DBHOST . ';dbname=' . DBNAME, DBUSER, DBPASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_WARNING, PDO::ATTR_PERSISTENT=>PERSISTENT]);
}catch(PDOException $e){
	die($I['nodb']);
}
$ch=curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, USERAGENT);
curl_setopt($ch, CURLOPT_PROXY, PROXY);
curl_setopt($ch, CURLOPT_PROXYTYPE, 7);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$online=$offline=$desc_online=$error=[];
$stmt=$db->prepare('SELECT address, category, md5sum, description FROM ' . PREFIX . "onions WHERE address!='' AND lasttest<(?-86400) ORDER BY lasttest LIMIT 50;");
$stmt->execute([time()]);
$onions=$stmt->fetchAll(PDO::FETCH_ASSOC);

//do tests
foreach($onions as $onion){
	curl_setopt($ch, CURLOPT_URL, "http://$onion[address].onion/");
	if(($site=curl_exec($ch))!==false){
		// update description to title, if not yet set
		if($onion['description']==='' && preg_match('~<title>([^<]+)</title>~i', $site, $match)){
			$desc=preg_replace("/(\r?\n|\r\n?)/", '<br>', htmlspecialchars(html_entity_decode(trim($match[1]))));
			$desc_online[]=[$desc, $onion['md5sum']];
		}
		$online[]=[time(), $onion['md5sum']];
		// checks for server errors, to move the address to a dedicated error category
//		if($onion['category']==0 && (curl_getinfo($ch, CURLINFO_HTTP_CODE)>=400 || $site==='')){
//			$error[]=[$onion['md5sum']];
//		}
	}else{
		$offline[]=[time(), $onion['md5sum'], time()];
	}
}
curl_close($ch);

// update database
$online_stmt=$db->prepare('UPDATE ' . PREFIX . 'onions SET lasttest=?, lastup=lasttest, timediff=0 WHERE md5sum=?');
$offline_stmt=$db->prepare('UPDATE ' . PREFIX . 'onions SET lasttest=?, timediff=lasttest-lastup WHERE md5sum=? AND lasttest<?');
$desc_online_stmt=$db->prepare('UPDATE ' . PREFIX . 'onions SET description=? WHERE md5sum=?');
//$error_stmt=$db->prepare('UPDATE ' . PREFIX . 'onions SET category=13 WHERE md5sum=?'); //in case of error, move the address to an error category - edit the category id to fit yours!
$db->beginTransaction();
foreach($online as $tmp){
	$online_stmt->execute($tmp);
}
foreach($desc_online as $tmp){
	$desc_online_stmt->execute($tmp);
}
foreach($offline as $tmp){
	$offline_stmt->execute($tmp);
}
//foreach($error as $tmp){
//	$error_stmt->execute($tmp);
//}
$db->commit();
?>
