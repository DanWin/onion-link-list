<?php
if(!extension_loaded('gettext')){
	die('The gettext extension of PHP is required. Please install it first.' . PHP_EOL);
}
require_once(__DIR__.'/common_config.php');
foreach(['pdo_mysql', 'json', 'pcre', 'curl', 'intl'] as $required_extension) {
	if ( ! extension_loaded( 'pdo_mysql' ) ) {
		die( sprintf(_( 'The %s extension of PHP is required. Please install it first.' ), $required_extension) . PHP_EOL );
	}
}
try{
	$db=new PDO('mysql:host=' . DBHOST . ';dbname=' . DBNAME . ';charset=utf8mb4', DBUSER, DBPASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_WARNING, PDO::ATTR_PERSISTENT=>PERSISTENT]);
}catch(PDOException $e){
	try{
		//Attempt to create database
		$db=new PDO('mysql:host=' . DBHOST . ';charset=utf8mb4', DBUSER, DBPASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_WARNING, PDO::ATTR_PERSISTENT=>PERSISTENT]);
		if(false!==$db->exec('CREATE DATABASE ' . DBNAME)){
			$db=new PDO('mysql:host=' . DBHOST . ';dbname=' . DBNAME . ';charset=utf8mb4', DBUSER, DBPASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_WARNING, PDO::ATTR_PERSISTENT=>PERSISTENT]);
		}else{
			die(_('No database connection!'));
		}
	}catch(PDOException $e){
		die(_('No database connection!'));
	}
}
if(!@$db->query('SELECT * FROM ' . PREFIX . 'settings LIMIT 1;')){
	//create tables
	$db->exec('CREATE TABLE ' . PREFIX . "captcha (id int(10) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT, time int(10) UNSIGNED NOT NULL, code char(5) NOT NULL) ENGINE=MEMORY;");
	$db->exec('CREATE TABLE ' . PREFIX . "onions (id int(10) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT, address varchar(56) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL, md5sum binary(16) NOT NULL UNIQUE, lasttest int(10) UNSIGNED NOT NULL DEFAULT '0', lastup int(10) UNSIGNED NOT NULL DEFAULT '0', timediff int(10) UNSIGNED NOT NULL DEFAULT '0', timeadded int(10) UNSIGNED NOT NULL DEFAULT '0', description text CHARACTER SET utf8mb4 NOT NULL, category smallint(6) NOT NULL DEFAULT '0', locked smallint(6) NOT NULL DEFAULT '0', special int(10) UNSIGNED NOT NULL DEFAULT '0', approved smallint(6) NOT NULL DEFAULT '0', timechanged int(10) UNSIGNED NOT NULL DEFAULT '0', INDEX(address), INDEX(lasttest), INDEX(timediff), INDEX(category), INDEX(special), INDEX(timechanged));");
	$db->exec('CREATE TABLE ' . PREFIX . 'phishing (onion_id int(10) UNSIGNED NOT NULL PRIMARY KEY, original varchar(56) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL, FOREIGN KEY (onion_id) REFERENCES onions(id) ON DELETE CASCADE ON UPDATE CASCADE);');
	$db->exec('CREATE TABLE ' . PREFIX . 'settings (setting varchar(50) NOT NULL PRIMARY KEY, value varchar(255) NOT NULL);');
	$stmt=$db->prepare('INSERT INTO ' . PREFIX . "settings (setting, value) VALUES ('version', ?);");
	$stmt->execute([DBVERSION]);
}else{
	$res=$db->query('SELECT value FROM ' . PREFIX . "settings WHERE setting='version';");
	$version=$res->fetch(PDO::FETCH_NUM)[0];
	if($version<2){
		$olddb=new PDO('mysql:host=' . DBHOST . ';dbname=' . DBNAME, DBUSER, DBPASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_WARNING, PDO::ATTR_PERSISTENT=>PERSISTENT]);
		$stmt=$olddb->query('SELECT onion_id, original  FROM ' . PREFIX . 'phishing;');
		$phishings=$stmt->fetchAll(PDO::FETCH_NUM);
		$stmt=$olddb->query('SELECT id, address, md5sum, lasttest, lastup, timediff, timeadded, description, category, locked, special FROM ' . PREFIX . 'onions;');
		$onions=$stmt->fetchAll(PDO::FETCH_NUM);
		$db->exec('DROP TABLE ' . PREFIX . 'phishing;');
		$db->exec('DROP TABLE ' . PREFIX . 'onions;');
		$db->exec('CREATE TABLE ' . PREFIX . 'onions (id int(10) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT, address varchar(16) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL, md5sum binary(16) NOT NULL UNIQUE, lasttest int(10) UNSIGNED NOT NULL, lastup int(10) UNSIGNED NOT NULL, timediff int(10) UNSIGNED NOT NULL, timeadded int(10) UNSIGNED NOT NULL, description text CHARACTER SET utf8mb4 NOT NULL, category smallint(6) NOT NULL, locked smallint(6) NOT NULL, special int(10) UNSIGNED NOT NULL, INDEX(address), INDEX(lasttest), INDEX(timediff), INDEX(category), INDEX(special));');
		$db->exec('CREATE TABLE ' . PREFIX . 'phishing (onion_id int(10) UNSIGNED NOT NULL PRIMARY KEY, original varchar(16) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL, FOREIGN KEY (onion_id) REFERENCES onions(id) ON DELETE CASCADE ON UPDATE CASCADE);');
		$stmt=$db->prepare('INSERT INTO ' . PREFIX . 'onions (id, address, md5sum, lasttest, lastup, timediff, timeadded, description, category, locked, special) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
		foreach($onions as $onion){
			$stmt->execute($onion);
		}
		$stmt=$db->prepare('INSERT INTO ' . PREFIX . 'phishing (onion_id, original) VALUES (?, ?);');
		foreach($phishings as $phishing){
			$stmt->execute($phishing);
		}
	}
	if($version<3){
		$db->exec('ALTER TABLE ' . PREFIX . 'onions CHANGE address address varchar(56) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL;');
		$db->exec('ALTER TABLE ' . PREFIX . 'phishing CHANGE original original varchar(56) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL;');
	}
	if($version<4){
		$db->exec("ALTER TABLE " . PREFIX . "onions CHANGE lasttest lasttest int(10) UNSIGNED NOT NULL DEFAULT '0', CHANGE lastup lastup int(10) UNSIGNED NOT NULL DEFAULT '0', CHANGE timediff timediff int(10) UNSIGNED NOT NULL DEFAULT '0', CHANGE timeadded timeadded int(10) UNSIGNED NOT NULL DEFAULT '0', CHANGE category category smallint(6) NOT NULL DEFAULT '0', CHANGE locked locked smallint(6) NOT NULL DEFAULT '0', CHANGE special special int(10) UNSIGNED NOT NULL DEFAULT '0'");
	}
	if($version<5){
		$db->exec('CREATE TABLE ' . PREFIX . "captcha (id int(10) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT, time int(10) UNSIGNED NOT NULL, code char(5) NOT NULL) ENGINE=MEMORY;");
	}
	if($version < 6){
		$db->exec('ALTER TABLE ' . PREFIX . "onions ADD approved smallint(6) NOT NULL DEFAULT '0';");
	}
	if($version < 7){
		$db->exec('ALTER TABLE ' . PREFIX . "onions ADD timechanged int(10) UNSIGNED NOT NULL DEFAULT '0';");
	}
	if($version < 8){
		$db->exec('ALTER TABLE ' . PREFIX . "settings CHANGE `value` `value` varchar(255) NOT NULL;");
	}
	$stmt=$db->prepare('UPDATE ' . PREFIX . "settings SET value=? WHERE setting='version';");
	$stmt->execute([DBVERSION]);
}
echo _('Status: OK').PHP_EOL;
