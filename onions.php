<?php
/*
* Onion Link List - Main listing script
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

if($_SERVER['REQUEST_METHOD']==='HEAD'){
	exit; // ignore headers, no further processing needed
}
include('common_config.php');
try{
	$db=new PDO('mysql:host=' . DBHOST . ';dbname=' . DBNAME, DBUSER, DBPASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_WARNING, PDO::ATTR_PERSISTENT=>PERSISTENT]);
}catch(PDOException $e){
}
date_default_timezone_set('UTC');
//select output format
if(!isset($_REQUEST['format'])){
	send_html();
}elseif($_REQUEST['format']==='text'){
	send_text();
}elseif($_REQUEST['format']==='json'){
	send_json();
}else{
	send_html();
}

function send_html(){
	global $I, $categories, $db, $language;
	header('Content-Type: text/html; charset=UTF-8');
	asort($categories);
	//sql for special categories
	$special=[
		$I['all']=>"address!='' AND id NOT IN (SELECT onion_id FROM " . PREFIX . 'phishing) AND timediff<604800',
		$I['lastadded']=>"address!='' AND id NOT IN (SELECT onion_id FROM " . PREFIX . 'phishing)',
		$I['offline']=>"address!='' AND id NOT IN (SELECT onion_id FROM " . PREFIX . 'phishing) AND timediff>604800'
	];
	if(!isSet($_REQUEST['pg'])){
		$_REQUEST['pg']=1;
	}else{
		settype($_REQUEST['pg'], 'int');
	}
	if($_REQUEST['pg']>0){
		$_REQUEST['newpg']=1;
	}else{
		$_REQUEST['newpg']=0;
	}
	echo '<!DOCTYPE html><html><head>';
	echo "<title>$I[title]</title>";
	echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
	echo '<meta name=viewport content="width=device-width, initial-scale=1">';
	echo '<style type="text/css">.red{color:red;} .green{color:green;} .up{background-color:#008000;} .down{background-color:#FF0000;} .promo{outline:medium solid #FFD700;} .list{display: inline-block; padding: 0px; margin: 0px;} .list li{display:inline;} .active{font-weight:bold;}</style>';
	echo '</head><body>';
	echo "<h2>$I[title]</h2>";
	print_langs();
	echo "<br><small>$I[format]: <a href=\"?format=text\">Text</a> <a href=\"?format=json\">JSON</a></small>";
	if(!isSet($db)){
		echo "<p><b class=\"red\">$I[error]:</b> $I[nodb]</p>";
		echo '</body></html>';
		exit;
	}
	//update onions description form
	echo "<table><tr valign=\"top\"><td><form action=\"$_SERVER[SCRIPT_NAME]\" method=\"POST\">";
	echo "<input type=\"hidden\" name=\"pg\" value=\"$_REQUEST[newpg]\">";
	echo "<input type=\"hidden\" name=\"lang\" value=\"$language\">";
	echo "<p>$I[addonion]: <br><input name=\"addr\" size=\"30\" placeholder=\"http://$_SERVER[HTTP_HOST]\" value=\"";
	if(isSet($_REQUEST['addr'])){
		echo htmlspecialchars($_REQUEST['addr']);
	}
	echo '" required></p>';
	echo "<p>$I[adddesc]: <br><textarea name=\"desc\" rows=\"2\" cols=\"30\">";
	if(!empty($_REQUEST['desc'])){//use posted description
		echo htmlspecialchars(trim($_REQUEST['desc']));
	}elseif(!empty($_REQUEST['addr'])){//fetch description from database
		if(preg_match('~(^(https?://)?([a-z0-9]*\.)?([a-z2-7]{16})(\.onion(/.*)?)?$)~i', trim($_REQUEST['addr']), $addr)){
			$addr=strtolower($addr[4]);
			$md5=md5($addr, true);
			$stmt=$db->prepare('SELECT description, category FROM ' . PREFIX . 'onions WHERE md5sum=?;');
			$stmt->execute([$md5]);
			if($desc=$stmt->fetch(PDO::FETCH_ASSOC)){
				$category=$desc['category'];
				echo str_replace('<br>', "\n", $desc['description']);
			}
		}
	}
	echo '</textarea></p>';
	if(isSet($_REQUEST['cat']) && $_REQUEST['cat']<(count($categories)+count($special)+1) && $_REQUEST['cat']>=0){
		settype($_REQUEST['cat'], 'int');
		$category=$_REQUEST['cat'];
	}
	if(!isSet($category)){
		$category=count($categories);
	}
	echo "<p>$I[category]: <select name=\"cat\">";
	foreach($categories as $cat=>$name){
		echo "<option value=\"$cat\"";
		if($category==$cat || ($cat===0 && $category>=count($categories))){
			echo ' selected';
		}
		echo ">$name</option>";
	}
	echo '</select></p>';
	echo "<input type=\"submit\" name=\"action\" value=\"$I[update]\"></form></td>";
	//search from
	echo "<td><form action=\"$_SERVER[SCRIPT_NAME]\" method=\"post\">";
	echo "<input type=\"hidden\" name=\"pg\" value=\"$_REQUEST[newpg]\">";
	echo "<input type=\"hidden\" name=\"lang\" value=\"$language\">";
	echo "<p>$I[search]: <br><input name=\"q\" size=\"30\" placeholder=\"$I[searchterm]\" value=\"";
	if(isSet($_REQUEST['q'])){
		echo htmlspecialchars($_REQUEST['q']);
	}
	echo '" required></p>';
	echo "<input type=\"submit\" name=\"action\" value=\"$I[search]\"></form></td>";
	echo '</tr></table><br>';
	//List special categories
	echo "<ul class=\"list\"><li>$I[specialcat]:</li>";
	$cat=count($categories);
	$pages=1;
	foreach($special as $name=>$query){
		if($cat===count($categories)+1){
			$num[0]=100;
		}else{
			$num=$db->query('SELECT COUNT(*) FROM ' . PREFIX . "onions WHERE $query;")->fetch(PDO::FETCH_NUM);
		}
		if($category==$cat){
			echo " <li class=\"active\"><a href=\"?cat=$cat&amp;pg=$_REQUEST[newpg]&amp;lang=$language\">$name ($num[0])</a></li>";
			$pages=ceil($num[0]/100);
		}else{
			echo " <li><a href=\"?cat=$cat&amp;pg=$_REQUEST[newpg]&amp;lang=$language\">$name ($num[0])</a></li>";
		}
		++$cat;
	}
	$num=$db->query('SELECT COUNT(*) FROM ' . PREFIX . 'phishing, ' . PREFIX . 'onions WHERE ' . PREFIX . "onions.id=onion_id AND address!='' AND timediff<604800;")->fetch(PDO::FETCH_NUM);
	if($category==$cat){
		echo " <li class=\"active\"><a href=\"?cat=$cat&amp;lang=$language\">$I[phishingclones] ($num[0])</a></li>";
	}else{
		echo " <li><a href=\"?cat=$cat&amp;lang=$language\">$I[phishingclones] ($num[0])</a></li>";
	}
	$num=$db->query('SELECT COUNT(*) FROM ' . PREFIX . "onions WHERE address='';")->fetch(PDO::FETCH_NUM);
	echo " <li>$I[removed] ($num[0])</li></ul><br><br>";
	//List normal categories
	echo "<ul class=\"list\"><li>$I[categories]:</li>";
	$stmt=$db->prepare('SELECT COUNT(*) FROM ' . PREFIX . "onions WHERE category=? AND address!='' AND id NOT IN (SELECT onion_id FROM " . PREFIX . 'phishing) AND timediff<604800;');
	foreach($categories as $cat=>$name){
		$stmt->execute([$cat]);
		$num=$stmt->fetch(PDO::FETCH_NUM);
		if($category==$cat){
			echo " <li class=\"active\"><a href=\"?cat=$cat&amp;pg=$_REQUEST[newpg]&amp;lang=$language\">$name ($num[0])</a></li>";
			$pages=ceil($num[0]/100);
		}else{
			echo " <li><a href=\"?cat=$cat&amp;pg=$_REQUEST[newpg]&amp;lang=$language\">$name ($num[0])</a></li>";
		}
	}
	echo '</ul><br><br>';
	if($_SERVER['REQUEST_METHOD']==='POST' && !empty($_REQUEST['addr'])){
		if(!preg_match('~(^(https?://)?([a-z0-9]*\.)?([a-z2-7]{16})(\.onion(/.*)?)?$)~i', trim($_REQUEST['addr']), $addr)){
			echo "<p class=\"red\">$I[invalonion]</p>";
			echo "<p>$I[valid]: http://tt3j2x4k5ycaa5zt.onion</p>";
		}else{
			$addr=strtolower($addr[4]);
			$md5=md5($addr, true);
			$stmt=$db->prepare('SELECT locked FROM ' . PREFIX . 'onions WHERE md5sum=?;');
			$stmt->execute([$md5]);
			$stmt->bindColumn(1, $locked);
			if($category==count($categories)){
				$category=0;
			}
			if(!isSet($_POST['desc'])){
				$desc='';
			}else{
				$desc=trim($_POST['desc']);
				$desc=htmlspecialchars($desc);
				$desc=preg_replace("/(\r?\n|\r\n?)/", '<br>', $desc);
			}
			if(!$stmt->fetch(PDO::FETCH_BOUND)){//new link, add to database
				$stmt=$db->prepare('INSERT INTO ' . PREFIX . 'onions (address, description, md5sum, category, timeadded) VALUES (?, ?, ?, ?, ?);');
				$stmt->execute([$addr, $desc, $md5, $category, time()]);
				echo "<p class=\"green\">$I[succadd]</p>";
			}elseif($locked==1){//locked, not editable
				echo "<p class=\"red\">$I[faillocked]</p>";
			}elseif($desc!==''){//update description
				$stmt=$db->prepare('UPDATE ' . PREFIX . 'onions SET description=?, category=? WHERE md5sum=?;');
				$stmt->execute([$desc, $category, $md5]);
				echo "<p class=\"green\">$I[succupddesc]</p>";
			}elseif($category!=0){//update category only
				$stmt=$db->prepare('UPDATE ' . PREFIX . 'onions SET category=? WHERE md5sum=?;');
				$stmt->execute([$category, $md5]);
				echo "<p class=\"green\">$I[succupdcat]</p>";
			}else{//nothing changed and already known
				echo "<p class=\"green\">$I[alreadyknown]</p>";
			}
		}
	}
	if($pages>1 && empty($_REQUEST['q'])){
		$pagination=get_pagination($category, $pages);
		echo $pagination;
	}else{
		$pagination='';
	}
	if(!empty($_REQUEST['q'])){//run search query
		$stmt=$db->prepare('SELECT address, lasttest, lastup, timeadded, description, locked, special FROM ' . PREFIX . "onions WHERE address!='' AND id NOT IN (SELECT onion_id FROM " . PREFIX . 'phishing) AND timediff<604800 AND (description LIKE ? OR address LIKE ?) ORDER BY address;');
		$query=htmlspecialchars($_REQUEST['q']);
		$query="%$query%";
		$stmt->execute([$query, $query]);
		$table=get_table($stmt, $numrows);
		printf("<p><b>$I[searchresult]</b></p>", $_REQUEST['q'], $numrows);
		echo $table;
	}elseif($category>=count($categories)+count($special)){//show phishing clones
		print_phishing_table();
	}elseif($category>=count($categories)){//show special categories
		$tmp=$category-count($categories);
		foreach($special as $name=>$query){
			if($tmp===0) break;
			--$tmp;
		}
		if($category-count($categories)===1){
			$query.=' ORDER BY id DESC LIMIT 100';
		}else{
			$query.=' ORDER BY address';
			if($_REQUEST['pg']>0){
				$offset=100*($_REQUEST['pg']-1);
				$query.=" LIMIT 100 OFFSET $offset";
			}
		}
		$stmt=$db->query('SELECT address, lasttest, lastup, timeadded, description, locked, special FROM ' . PREFIX . "onions WHERE $query;");
		echo get_table($stmt, $numrows, true);
	}else{//show normal categories
		if($_REQUEST['pg']>0){
			$offset=100*($_REQUEST['pg']-1);
			$offsetquery=" LIMIT 100 OFFSET $offset";
		}else{
			$offsetquery='';
		}
		$stmt=$db->prepare('SELECT address, lasttest, lastup, timeadded, description, locked, special FROM ' . PREFIX . "onions WHERE address!='' AND id NOT IN (SELECT onion_id FROM " . PREFIX . "phishing) AND category=? AND timediff<604800 ORDER BY address$offsetquery;");
		$stmt->execute([$category]);
		echo get_table($stmt, $numrows, true);
	}
	echo '<br>';
	echo $pagination;
	echo '<br><p style="text-align:center;font-size:small;"><a target="_blank" href="https://github.com/DanWin/onion-link-list">Onion Link List - ' . VERSION . '</a></p>';
	echo '</body></html>';
}

function get_table(PDOStatement $stmt, &$numrows=0, $promoted=false){
	global $I, $db, $language;
	$time=time();
	ob_start();
	echo "<table border=\"1\"><tr><th>$I[link]</th><th>$I[description]</th><th>$I[editdesc]</th><th>$I[lasttested]</th><th>$I[lastup]</th><th>$I[timeadded]</th><th>$I[testnow]</th></tr>";
	if($promoted){//print promoted links at the top
		$time=time();
		$promo=$db->prepare('SELECT address, lasttest, lastup, timeadded, description, locked, special FROM ' . PREFIX . "onions WHERE special>? AND address!='' AND id NOT IN (SELECT onion_id FROM " . PREFIX . 'phishing) AND timediff<604800 ORDER BY address;');
		$promo->execute([$time]);
		while($link=$promo->fetch(PDO::FETCH_ASSOC)){
			if($link['lastup']===$link['lasttest']){
				$class='up';
			}else{
				$class='down';
			}
			if($link['lastup']==0){
				$lastup=$I['never'];
			}else{
				$lastup=date('Y-m-d H:i:s', $link['lastup']);
			}
			if($link['lasttest']==0){
				$lasttest=$I['never'];
			}else{
				$lasttest=date('Y-m-d H:i:s', $link['lasttest']);
			}
			$timeadded=date('Y-m-d H:i:s', $link['timeadded']);
			echo "<tr class=\"$class promo\"><td><a href=\"http://$link[address].onion\" target=\"_blank\">$link[address].onion</a></td><td>$link[description]</td><td>-</td><td>$lasttest</td><td>$lastup</td><td>$timeadded</td><td><form target=\"_blank\" method=\"post\" action=\"test.php\"><input name=\"addr\" value=\"$link[address]\" type=\"hidden\"><input name=\"lang\" value=\"$language\" type=\"hidden\"><input value=\"$I[test]\" type=\"submit\"></form></td></tr>";
		}
	}
	while($link=$stmt->fetch(PDO::FETCH_ASSOC)){
		if($link['lastup']===$link['lasttest']){
			$class='up';
		}else{
			$class='down';
		}
		if($link['lastup']==0){
			$lastup=$I['never'];
		}else{
			$lastup=date('Y-m-d H:i:s', $link['lastup']);
		}
		if($link['lasttest']==0){
			$lasttest=$I['never'];
			$class='';
		}else{
			$lasttest=date('Y-m-d H:i:s', $link['lasttest']);
		}
		$timeadded=date('Y-m-d H:i:s', $link['timeadded']);
		if($link['special']>$time){
			$class.=' promo';
		}
		if($link['locked']==1){
			$edit='-';
		}else{
			$edit="<form target=\"_blank\"><input name=\"addr\" value=\"$link[address]\" type=\"hidden\"><input type=\"hidden\" name=\"pg\" value=\"$_REQUEST[newpg]\"><input type=\"hidden\" name=\"lang\" value=\"$language\"><input value=\"$I[edit]\" type=\"submit\"></form>";
		}
		echo "<tr class=\"$class\"><td><a href=\"http://$link[address].onion\" target=\"_blank\">$link[address].onion</a></td><td>$link[description]</td><td>$edit</td><td>$lasttest</td><td>$lastup</td><td>$timeadded</td><td><form target=\"_blank\" method=\"post\" action=\"test.php\"><input name=\"addr\" value=\"$link[address]\" type=\"hidden\"><input type=\"hidden\" name=\"lang\" value=\"$language\"><input value=\"$I[test]\" type=\"submit\"></form></td></tr>";
		++$numrows;
	}
	echo '</table>';
	return ob_get_clean();
}

function print_phishing_table(){
	global $I, $db;
	echo "<table border=\"1\"><tr><th>$I[link]</th><th>$I[cloneof]</th><th>$I[lastup]</th></tr>";
	$stmt=$db->query('SELECT address, original, lasttest, lastup FROM ' . PREFIX . 'onions, ' . PREFIX . 'phishing WHERE ' . PREFIX . "onions.id=onion_id AND address!='' ORDER BY onions.address AND timediff<604800;");
	while($link=$stmt->fetch(PDO::FETCH_ASSOC)){
		if($link['lastup']===$link['lasttest']){
			$class='up';
		}else{
			$class='down';
		}
		if($link['lastup']==0){
			$lastup=$I['never'];
		}else{
			$lastup=date('Y-m-d H:i:s', $link['lastup']);
		}
		if($link['original']!==''){
			$orig="<a href=\"http://$link[original].onion\" target=\"_blank\">$link[original].onion</a>";
		}else{
			$orig=$I['unknown'];
		}
		echo "<tr class=\"$class\"><td>$link[address].onion</td><td>$orig</td><td>$lastup</td></tr>";
	}
	echo '</table>';
}

function send_text(){
	global $db;
	if(!isSet($db)){
		die("$I[error]: $I[nodb]");
	}
	header('Content-Type: text/plain; charset=UTF-8');
	$stmt=$db->query('SELECT address FROM ' . PREFIX . "onions WHERE address!='' AND id NOT IN (SELECT onion_id FROM " . PREFIX . 'phishing) AND timediff<604800 ORDER BY address;');
	while($tmp=$stmt->fetch(PDO::FETCH_NUM)){
		echo "$tmp[0].onion\n";
	}
}

function send_json(){
	global $db, $categories;
	if(!isSet($db)){
		die("$I[error]: $I[nodb]");
	}
	header('Content-Type: application/json;');
	$data=['categories'=>$categories];
	$stmt=$db->query('SELECT address, category, description, locked, lastup, lasttest, timeadded FROM ' . PREFIX . "onions WHERE address!='' AND id NOT IN (SELECT onion_id FROM " . PREFIX . 'phishing) AND timediff<604800 ORDER BY address;');
	$data['onions']=$stmt->fetchALL(PDO::FETCH_ASSOC);
	$stmt=$db->query('SELECT md5sum FROM ' . PREFIX . "onions WHERE address='';");
	while($tmp=$stmt->fetch(PDO::FETCH_ASSOC)){
		$data['removed'][]=bin2hex($tmp['md5sum']);
	}
	$stmt=$db->query('SELECT address, original FROM ' . PREFIX . 'onions, ' . PREFIX . 'phishing WHERE onion_id=' . PREFIX . "onions.id AND address!='' AND timediff<604800 ORDER BY address;");
	$data['phishing']=$stmt->fetchALL(PDO::FETCH_ASSOC);
	echo json_encode($data);
}

function get_pagination($category, $pages){
	global $I, $language;
	ob_start();
	echo "<ul class=\"list\"><li>$I[pages]:</li>";
	if($_REQUEST['pg']==0){
		echo " <li class=\"active\"><a href=\"?cat=$category&amp;pg=0&amp;lang=$language\">$I[all]</a></li>";
	}else{
		echo " <li><a href=\"?cat=$category&amp;pg=0&amp;lang=$language\">$I[all]</a></li>";
	}
	for($i=1; $i<=$pages; ++$i){
		if($_REQUEST['pg']==$i){
			echo " <li class=\"active\"><a href=\"?cat=$category&amp;pg=$i&amp;lang=$language\">$i</a></li>";
		}else{
			echo " <li><a href=\"?cat=$category&amp;pg=$i&amp;lang=$language\">$i</a></li>";
		}
	}
	echo "</ul><br><br>";
	return ob_get_clean();
}
?>
