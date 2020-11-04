<?php
require_once(__DIR__.'/../common_config.php');
$style = '.red{color:red} .green{color:green} .software-link{text-align:center;font-size:small} #maintable ,#maintable th,#maintable td{border: 1px solid black}';
send_headers([$style]);
try{
	$db=new PDO('mysql:host=' . DBHOST . ';dbname=' . DBNAME . ';charset=utf8mb4', DBUSER, DBPASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_WARNING, PDO::ATTR_PERSISTENT=>PERSISTENT]);
}catch(PDOException $e){
	http_send_status(500);
	die($I['nodb']);
}
asort($categories);
?>
<!DOCTYPE html><html lang="<?php echo $language; ?>"><head>
<title><?php echo $I['admintitle']; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name=viewport content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<link rel="canonical" href="<?php echo CANONICAL_URL . $_SERVER['SCRIPT_NAME']; ?>">
<style type="text/css"><?php echo $style; ?></style>
</head><body>
<h1><?php echo $I['admintitle']; ?></h1>
<?php
print_langs();

//check password
if(!isset($_POST['pass']) || $_POST['pass']!==ADMINPASS){
	echo "<form action=\"$_SERVER[SCRIPT_NAME]\" method=\"POST\">";
	echo "<input type=\"hidden\" name=\"lang\" value=\"$language\">";
	echo "<p><label>$I[password]: <input type=\"password\" name=\"pass\" size=\"30\" required autocomplete=\"current-password\"></label></p>";
	echo "<input type=\"submit\" name=\"action\" value=\"$I[login]\">";
	echo '</form>';
	if(isset($_POST['pass'])){
		echo "<p class=\"red\" role=\"alert\">$I[wrongpass]</p>";
	}
}else{
	$view_mode = isset($_POST['view_mode']) ? $_POST['view_mode'] : 'single';
	if(isset($_POST['switch_view_mode'])){
		$view_mode = $view_mode === 'single' ? 'multi' : 'single';
	}
	echo "<form action=\"$_SERVER[SCRIPT_NAME]\" method=\"POST\">";
	echo "<input type=\"hidden\" name=\"lang\" value=\"$language\">";
	echo "<input type=\"hidden\" name=\"pass\" value=\"$_POST[pass]\">";
	echo "<input type=\"hidden\" name=\"view_mode\" value=\"$view_mode\">";
	echo "<br><input type=\"submit\" name=\"switch_view_mode\" value=\"$I[switchviewmode]\"></form>";
	echo "<form action=\"$_SERVER[SCRIPT_NAME]\" method=\"POST\">";
	echo "<input type=\"hidden\" name=\"lang\" value=\"$language\">";
	echo "<input type=\"hidden\" name=\"pass\" value=\"$_POST[pass]\">";
	echo "<input type=\"hidden\" name=\"view_mode\" value=\"$view_mode\">";
	if($view_mode === 'single') {
		echo "<p><label>$I[link]: <input name=\"addr\" size=\"30\" value=\"";
		if ( isset( $_REQUEST[ 'addr' ] ) ) {
			echo htmlspecialchars( $_REQUEST[ 'addr' ] );
		}
		echo '" required autofocus></label></p>';
	} else {
		echo '<table id="maintable"><tr><th>Select</th><th>Address</th><th>Description</th><th>Category</th><th>Status</th></tr>';
		$stmt=$db->query('SELECT address, description, category, approved, locked FROM ' . PREFIX . "onions WHERE address!='';");
		while($onion = $stmt->fetch(PDO::FETCH_ASSOC)){
			echo '<tr><td><input type="checkbox" name="addr[]" value="'.$onion['address'].'"></td><td><a href="http://'.$onion['address'].'.onion" rel="noopener">'.$onion['address'].'.onion</a></td>';
			echo "<td>$onion[description]</td><td>{$categories[$onion['category']]}</td><td>Approved: $onion[approved]<br>Locked: $onion[locked]</td></tr>";
		}
		echo '</table>';
	}
	echo "<p><label>$I[cloneof]: <input type=\"text\" name=\"original\" size=\"30\"";
	if(isset($_REQUEST['original'])){
		echo ' value="'.htmlspecialchars($_REQUEST['original']).'"';
	}
	echo '></label></p>';
	echo "<p><label>$I[bitcoins]: <input type=\"text\" name=\"btc\" size=\"30\"";
	if(isset($_REQUEST['btc'])){
		echo ' value="'.htmlspecialchars($_REQUEST['btc']).'"';
	}
	echo '></label></p>';
	echo "<p><label for=\"desc\">$I[adddesc]:</label> <br><textarea id=\"desc\" name=\"desc\" rows=\"2\" cols=\"30\">";
	if(!empty($_REQUEST['desc'])){
		echo htmlspecialchars(trim($_REQUEST['desc']));
	}elseif(isset($_REQUEST['addr']) && is_string($_REQUEST['addr'])){
		if(preg_match('~(^(https?://)?([a-z2-7]{16}|[a-z2-7]{56})(\.onion(/.*)?)?$)~i', trim($_REQUEST['addr']), $addr)){
			$addr=strtolower($addr[3]);
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
	if(isset($_REQUEST['cat']) && $_REQUEST['cat']<count($categories) && $_REQUEST['cat']>=0){
		$category=$_REQUEST['cat'];
	}
	if(!isset($category)){
		$category=count($categories);
	}
	echo "<p><label>$I[category]: <select name=\"cat\">";
	foreach($categories as $cat=>$name){
		echo "<option value=\"$cat\"";
		if($category==$cat || ($cat===0 && $category>=count($categories))){
			echo ' selected';
		}
		echo ">$name</option>";
	}
	echo '</select></label></p>';
	echo '<input type="submit" name="action" value="None" hidden>';
	echo '<table><tr>';
	echo "<td><input type=\"submit\" name=\"action\" value=\"$I[remove]\"></td>";
	echo "<td><input type=\"submit\" name=\"action\" value=\"$I[lock]\"></td>";
	echo "<td><input type=\"submit\" name=\"action\" value=\"$I[promote]\"></td>";
	echo "<td><input type=\"submit\" name=\"action\" value=\"$I[phishing]\"></td>";
	echo '</tr><tr>';
	echo "<td><input type=\"submit\" name=\"action\" value=\"$I[readd]\"></td>";
	echo "<td><input type=\"submit\" name=\"action\" value=\"$I[unlock]\"></td>";
	echo "<td><input type=\"submit\" name=\"action\" value=\"$I[unpromote]\"></td>";
	echo "<td><input type=\"submit\" name=\"action\" value=\"$I[unphishing]\"></td>";
	echo '</tr><tr>';
	echo "<td><input type=\"submit\" name=\"action\" value=\"$I[update]\"></td>";
	if(REQUIRE_APPROVAL) {
		echo "<td><input type=\"submit\" name=\"action\" value=\"$I[reject]\"></td>";
		echo "<td><input type=\"submit\" name=\"action\" value=\"$I[approve]\"></td>";
	}
	echo '</tr></table>';
	echo '</form><br>';

	if(!empty($_POST['addr'])){
		$addrs = is_array($_POST['addr']) ? $_POST['addr'] : [$_POST['addr']];
		foreach ($addrs as $addr_single) {
			if ( ! preg_match( '~(^(https?://)?([a-z2-7]{16}|[a-z2-7]{56})(\.onion(/.*)?)?$)~i', trim( $addr_single ), $addr ) ) {
				echo "<p class=\"red\" role=\"alert\">$I[invalonion]</p>";
			} else {
				$addr = strtolower( $addr[ 3 ] );
				$md5 = md5( $addr, true );
				if ( $_POST[ 'action' ] === $I[ 'remove' ] ) { //remove address from public display
					$db->prepare( 'UPDATE ' . PREFIX . "onions SET address='', locked=1, approved=-1 WHERE md5sum=?;" )->execute( [ $md5 ] );
					echo "<p class=\"green\" role=\"alert\">$I[succremove]</p>";
				} elseif ( $_POST[ 'action' ] === $I[ 'lock' ] ) { //lock editing
					$db->prepare( 'UPDATE ' . PREFIX . 'onions SET locked=1, approved=1 WHERE md5sum=?;' )->execute( [ $md5 ] );
					echo "<p class=\"green\"> role=\"alert\"$I[succlock]</p>";
				} elseif ( $_POST[ 'action' ] === $I[ 'readd' ] ) { //add onion back, if previously removed
					$db->prepare( 'UPDATE ' . PREFIX . 'onions SET address=?, locked=1, approved=1 WHERE md5sum=?;' )->execute( [ $addr, $md5 ] );
					echo "<p class=\"green\" role=\"alert\">$I[succreadd]</p>";
				} elseif ( $_POST[ 'action' ] === $I[ 'unlock' ] ) { //unlock editing
					$db->prepare( 'UPDATE ' . PREFIX . 'onions SET locked=0, approved=1 WHERE md5sum=?;' )->execute( [ $md5 ] );
					echo "<p class=\"green\" role=\"alert\">$I[succunlock]</p>";
				} elseif ( $_POST[ 'action' ] === $I[ 'promote' ] ) { //promote link for payed time
					$stmt = $db->prepare( 'SELECT special FROM ' . PREFIX . 'onions WHERE md5sum=?;' );
					$stmt->execute( [ $md5 ] );
					$specialtime = $stmt->fetch( PDO::FETCH_NUM );
					if ( $specialtime[ 0 ] < time() ) {
						$time = time() + ( ( $_POST[ 'btc' ] / PROMOTEPRICE ) * PROMOTETIME );
					} else {
						$time = $specialtime[ 0 ] + ( ( $_POST[ 'btc' ] / PROMOTEPRICE ) * PROMOTETIME );
					}
					$db->prepare( 'UPDATE ' . PREFIX . 'onions SET special=?, locked=1, approved=1 WHERE md5sum=?;' )->execute( [ $time, $md5 ] );
					printf( "<p class=\"green\" role=\"alert\">$I[succpromote]</p>", date( 'Y-m-d H:i', $time ) );
				} elseif ( $_POST[ 'action' ] === $I[ 'unpromote' ] ) { //remove promoted status
					$db->prepare( 'UPDATE ' . PREFIX . 'onions SET special=0 WHERE md5sum=?;' )->execute( [ $md5 ] );
					echo "<p class=\"green\" role=\"alert\">$I[succunpromote]</p>";
				} elseif ( $_POST[ 'action' ] === $I[ 'update' ] ) { //update description
					$stmt = $db->prepare( 'SELECT * FROM ' . PREFIX . 'onions WHERE md5sum=?;' );
					$stmt->execute( [ $md5 ] );
					if ( $category === count( $categories ) ) {
						$category = 0;
					}
					if ( ! isset( $_POST[ 'desc' ] ) ) {
						$desc = '';
					} else {
						$desc = trim( $_POST[ 'desc' ] );
						$desc = htmlspecialchars( $desc );
						$desc = preg_replace( "/(\r?\n|\r\n?)/", '<br>', $desc );
					}
					if ( ! $stmt->fetch( PDO::FETCH_ASSOC ) ) { //not yet there, add it
						$stmt = $db->prepare( 'INSERT INTO ' . PREFIX . 'onions (address, description, md5sum, category, timeadded, locked, approved) VALUES (?, ?, ?, ?, ?, 1, 1);' );
						$stmt->execute( [ $addr, $desc, $md5, $category, time() ] );
						echo "<p class=\"green\" role=\"alert\">$I[succadd]</p>";
					} elseif ( $desc != '' ) { //update description+category
						$stmt = $db->prepare( 'UPDATE ' . PREFIX . 'onions SET description=?, category=?, locked=1, approved=1 WHERE md5sum=?;' );
						$stmt->execute( [ $desc, $category, $md5 ] );
						echo "<p class=\"green\" role=\"alert\">$I[succupddesc]</p>";
					} elseif ( $category != 0 ) { //only update category
						$stmt = $db->prepare( 'UPDATE ' . PREFIX . 'onions SET category=?, locked=1, approved=1 WHERE md5sum=?;' );
						$stmt->execute( [ $category, $md5 ] );
						echo "<p class=\"green\" role=\"alert\">$I[succupdcat]!</p>";
					} else { //no description or category change and already known
						echo "<p class=\"green\" role=\"alert\">$I[alreadyknown]</p>";
					}
				} elseif ( $_POST[ 'action' ] === $I[ 'phishing' ] ) {//mark as phishing clone
					if ( $_POST[ 'original' ] !== '' && ! preg_match( '~(^(https?://)?([a-z2-7]{16}|[a-z2-7]{56})(\.onion(/.*)?)?$)~i', $_POST[ 'original' ], $orig ) ) {
						echo "<p class=\"red\" role=\"alert\">$I[invalonion]</p>";
					} else {
						if ( isset( $orig[ 3 ] ) ) {
							$orig = strtolower( $orig[ 3 ] );
						} else {
							$orig = '';
						}
						if ( $orig !== $addr ) {
							$stmt = $db->prepare( 'INSERT INTO ' . PREFIX . 'phishing (onion_id, original) VALUES ((SELECT id FROM ' . PREFIX . 'onions WHERE address=?), ?);' );
							$stmt->execute( [ $addr, $orig ] );
							$stmt = $db->prepare( 'UPDATE ' . PREFIX . 'onions SET locked=1, approved=1 WHERE address=?;' );
							$stmt->execute( [ $addr ] );
							echo "<p class=\"green\" role=\"alert\">$I[succaddphish]</p>";
						} else {
							echo "<p class=\"red\" role=\"alert\">$I[samephish]</p>";
						}
					}
				} elseif ( $_POST[ 'action' ] === $I[ 'unphishing' ] ) { //remove phishing clone status
					$stmt = $db->prepare( 'DELETE FROM ' . PREFIX . 'phishing WHERE onion_id=(SELECT id FROM ' . PREFIX . 'onions WHERE address=?);' );
					$stmt->execute( [ $addr ] );
					echo "<p class=\"green\" role=\"alert\">$I[succrmphish]</p>";
				} elseif ( $_POST[ 'action' ] === $I[ 'reject' ] ) { //lock editing
					$db->prepare( 'UPDATE ' . PREFIX . 'onions SET approved=-1 WHERE md5sum=?;' )->execute( [ $md5 ] );
					echo "<p class=\"green\" role=\"alert\">$I[succreject]</p>";
				} elseif ( $_POST[ 'action' ] === $I[ 'approve' ] ) { //lock editing
					$db->prepare( 'UPDATE ' . PREFIX . 'onions SET approved=1 WHERE md5sum=?;' )->execute( [ $md5 ] );
					echo "<p class=\"green\" role=\"alert\">$I[succapprove]</p>";
				} else { //no specific button was pressed
					echo "<p class=\"red\" role=\"alert\">$I[noaction]</p>";
				}
			}
		}
	}
}
?>
<br><p class="software-link"><a target="_blank" href="https://github.com/DanWin/onion-link-list" rel="noopener">Onion Link List - <?php echo VERSION; ?></a></p>
</body></html>
