<?php

#session("bob")
#Will get the whole user array for the user "bob"
function session($session = false) { #Function to get all information about a user and return it in an array
	require "config.php"; #Needs this to connect to the sql database
	if (!$session) { #If the function was used without a username stated
		$session = $_COOKIE[$sitename];
		$time    = strtotime( '+30 days' );
		
		$query = mysql_query("SELECT * FROM userblobs WHERE code='$session' AND date<'$time' AND action='session'"); #Find blobs where the code is equal to the session, is not expired, and is a session blob
		$numrows = mysql_num_rows($query);
		while($value = mysql_fetch_array($query)) { $username = $value['user']; }
		
		if ($numrows === 1) { #If there was a blob that matched perfectly
			$query = mysql_query("SELECT * FROM users WHERE username='$username'"); #Finds the user in the users table with the username attached to the blob
			while($value = mysql_fetch_array($query)) {
				return $value; #Return the whole array of the found user
			}
		} else {
			return false; #If there's no matching user, tell the system it broke
		}
	} else { #If the function was used with a username declared
		$query = mysql_query("SELECT * FROM users WHERE username='$session'");  #Find the user with the provided username
		$numrows = mysql_num_rows($query);
		if ($numrows === 1) { #If there is a matching user 
			while($value = mysql_fetch_array($query)) {
				return $value; #Return the whole array of the found user
			}
		} else {
			return false; #If there's no matching user, tell the system it broke
		}
	}
}

?>