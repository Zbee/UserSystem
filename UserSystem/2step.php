<?php
require "allFiles.php"; #Include Functions if they're not already here
if (isset($_GET['url'])) { $url = urldecode($_GET['url']); } elseif (!empty($_GET['url'])) { $url = urldecode($_GET['url']); } else { $url = "//".$domain; } #Setup a redirect url
$query = mysql_query("SELECT * FROM userblobs WHERE code='".$_GET['blob']."' AND date<'".strtotime('+3 days')."' AND action='2Step' AND ip='".$_SERVER['REMOTE_ADDR']."'"); #Find a valid blob
$numrows = mysql_num_rows($query);
if ($numrows === 1) { #If there's a valid blob
	while($value = mysql_fetch_array($query)) { $username = $value['user']; $uname = $username; } #Get the username
	$ip     = $_SERVER['REMOTE_ADDR'];
	if ($encrypt === true) { $ip = encrypt($ip, $uname); } #Encrypt the IP if necessary
	$query  = mysql_query("UPDATE users SET last_logged_in='".time()."', old_last_logged_in='".userGet("last_logged_in", $uname)."', ip='".$ip."' WHERE username='".$username."'"); #Tell the database the user logged in
	$hash   = hash("sha256", $uname."session from 2step".substr(str_shuffle(str_repeat("1234567890", 7)), 1, 50)); #Create a session blob
	$hash   = $hash.md5($uname.$hash); #Make the blob tamper checkable
	insertUserBlob($uname, $hash); #Insert the session blob
	mysql_query("DELETE FROM userblobs WHERE code='".$_GET['blob']."' AND action='2Step' LIMIT 1"); #Delete the 2step blob
	setcookie($sitename, $hash, strtotime('+30 days'), "/", $domain_simple); #Set the session cookie
	redirect301($url); #Redirect to the url
} else {
  redirect301("//".$domain."?2StepFail");
}
?>