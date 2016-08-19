<?php
/*
* Onion Link List - Setup
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

include('common_config.php');
if(!extension_loaded('pdo_mysql')){
	die($I['pdo_mysqlextrequired']);
}
if(!extension_loaded('pcre')){
	die($I['pcreextrequired']);
}
if(!extension_loaded('json')){
	die($I['jsonextrequired']);
}
if(!extension_loaded('json')){
	die($I['curlextrequired']);
}
if(!extension_loaded('date')){
	die($I['dateextrequired']);
}
try{
	$db=new PDO('mysql:host=' . DBHOST . ';dbname=' . DBNAME, DBUSER, DBPASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_WARNING, PDO::ATTR_PERSISTENT=>PERSISTENT]);
}catch(PDOException $e){
	try{
		//Attempt to create database
		$db=new PDO('mysql:host=' . DBHOST, DBUSER, DBPASS, $options);
		if(false!==$db->exec('CREATE DATABASE ' . DBNAME)){
			$db=new PDO('mysql:host=' . DBHOST . ';dbname=' . DBNAME, DBUSER, DBPASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_WARNING, PDO::ATTR_PERSISTENT=>PERSISTENT]);
		}else{
			die($I['nodb']);
		}
	}catch(PDOException $e){
		die($I['nodb']);
	}
}
if(!@$db->query('SELECT * FROM ' . PREFIX . 'settings LIMIT 1;')){
	//create tables
	$db->exec('CREATE TABLE ' . PREFIX . 'onions (id int(10) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT, address varchar(16) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL, md5sum binary(16) NOT NULL UNIQUE, lasttest int(10) UNSIGNED NOT NULL, lastup int(10) UNSIGNED NOT NULL, timediff int(10) UNSIGNED NOT NULL, timeadded int(10) UNSIGNED NOT NULL, description varchar(20000) CHARACTER SET utf8 NOT NULL, category smallint(6) NOT NULL, locked smallint(6) NOT NULL, special int(10) UNSIGNED NOT NULL, INDEX(address), INDEX(lasttest), INDEX(timediff), INDEX(category), INDEX(special));');
	$db->exec('CREATE TABLE ' . PREFIX . 'phishing (id int(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT, onion_id int(10) UNSIGNED NOT NULL UNIQUE, original varchar(16) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL, FOREIGN KEY (onion_id) REFERENCES onions(id) ON DELETE CASCADE ON UPDATE CASCADE);');
	$db->exec('CREATE TABLE ' . PREFIX . 'settings (setting varchar(50) NOT NULL PRIMARY KEY, value varchar(20000) NOT NULL);');
	$db->exec('INSERT INTO ' . PREFIX . "settings (setting, value) VALUES ('version', '1');");
	echo "$I[succdbcreate]\n";
}else{
	echo "$I[statusok]\n";
}
?>
