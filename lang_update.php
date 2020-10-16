<?php
$native = 'Deutsch'; // Native lanugae name
$english = 'German'; // English language name
$code = 'de'; // Language code
$I=[];
$T=[];
ob_start();
echo "<?php
/*
* Onion Link List - $english translation
*
* Copyright (C) 2016-2020 Daniel Winzen <daniel@danwin1210.me>
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

//Native language name: $native
\$T=[
";
if(file_exists("lang_$code.php")){
	include("lang_$code.php");
}
include('lang_en.php');
foreach($T as $id=>$value){
	if(isset($I[$id])){
		$I[$id]=$value;
	}
}
foreach($I as $id=>$value){
	echo "\t'$id' => '".str_replace("'", "\'", $value)."',\n";
}
echo "];\n";
$file=ob_get_clean();
file_put_contents("lang_$code.php", $file);
