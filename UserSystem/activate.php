<?php
require "allFiles.php"; #Include Functions if they're not already here

if (isset($_GET['blob'])) { #If there's a blob in the url
	if (isset($_GET['url'])) { $url = urldecode($_GET['url']); } elseif (!empty($_GET['url'])) { $url = urldecode($_POST['url']); } else { $url = "//".$domaint; } #Set up a url if it's not already done
	$query = mysql_query("SELECT * FROM userblobs WHERE code='".$_GET['blob']."' AND date<'".strtotime('+1 days')."' AND action='activate'"); #Find a valid blob is found in the blob table
	$numrows = mysql_num_rows($query);
	if ($numrows === 1) { #If there was a valid blob found
		while($value = mysql_fetch_array($query)) { $username = $value['user']; $uname = $username; } #Grab the username of the blob
		$ip     = $_SERVER['REMOTE_ADDR'];
		if ($encrypt === true) { $ip = encrypt($ip, $uname); } #Encrypt the IP if necessary
		$query  = mysql_query("UPDATE users SET last_logged_in='".time()."', ip='".$ip."', activated='1' WHERE username='".$username."'"); #Update when the user was logged in and activate them
		mysql_query("DELETE FROM userblobs WHERE code='".$_GET['blob']."' AND action='activate' LIMIT 1"); #Delete the activation blob
		$hash   = hash("sha256", $uname.substr(str_shuffle(str_repeat("12345678905", 7)), 0, 7)); #Create a new blob
		$hash   = $hash.md5($uname.$hash); #Make the blob tamper checkable
		insertUserBlob($uname, $hash); #Insert the session blob
		setcookie($sitename, $hash, strtotime('+30 days'), "/", $domain_simple); #Set the cookie that will make the user be logged in
		redirect301($url); #Redirect to the desired url
	} else {
    redirect301("//".$domain."?ActivateFail");
  }
} else {
  redirect301("//".$domain."?ActivateMissing");
}
?>