<?php
/*
* Onion Link List - Automated import of new onion sites
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

// Executed every 24 hours via cron - checks for new sites.
include('common_config.php');
try{
	$db=new PDO('mysql:host=' . DBHOST . ';dbname=' . DBNAME, DBUSER, DBPASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_WARNING, PDO::ATTR_PERSISTENT=>PERSISTENT]);
}catch(PDOException $e){
	die($I['nodb']);
}
$ch=curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_PROXY, PROXY);
curl_setopt($ch, CURLOPT_PROXYTYPE, 7);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 25);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$onions=[];

//sources to get links from
check_links($onions, $ch, 'https://tt3j2x4k5ycaa5zt.onion.to/antanistaticmap/stats/yesterday');
check_links($onions, $ch, 'https://tt3j2x4k5ycaa5zt.tor2web.org/antanistaticmap/stats/yesterday');
check_links($onions, $ch, 'http://tt3j2x4k5ycaa5zt.onion/onions.php?format=text');
check_links($onions, $ch, 'http://skunksworkedp2cg.onion/sites.txt');
check_links($onions, $ch, 'http://7cbqhjnlkivmigxf.onion/');
check_links($onions, $ch, 'http://visitorfi5kl7q7i.onion/address/');

//add them to the database
add_onions($onions, $db);
//delete links that were not seen within a month
$db->exec('DELETE FROM ' . PREFIX . "onions WHERE address!='' AND timediff>2419200 AND lasttest-timeadded>2419200;");

function check_links(&$onions, &$ch, $link){
	curl_setopt($ch, CURLOPT_URL, $link);
	$links=curl_exec($ch);
	if(preg_match_all('~(https?://)?([a-z0-9]*\.)?([a-z2-7]{16}).onion(/[^\s><"]*)?~i', $links, $addr)){
		foreach($addr[3] as $link){
			$link=strtolower($link);
			$onions[md5($link, true)]=$link;
		}
	}
}

function add_onions(&$onions, $db){
	$stmt=$db->query('SELECT md5sum FROM ' . PREFIX . 'onions;');
	while($tmp=$stmt->fetch(PDO::FETCH_NUM)){
		if(isSet($onions[$tmp[0]])){
			unset($onions[$tmp[0]]);
		}
	}
	$time=time();
	$insert=$db->prepare('INSERT INTO ' . PREFIX . 'onions (address, md5sum, timeadded) VALUES (?, ?, ?);');
	$db->beginTransaction();
	foreach($onions as $md5=>$addr){
		$insert->execute([$addr, $md5, $time]);
	}
	$db->commit();
}
?>
