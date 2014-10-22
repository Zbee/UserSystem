<?php

if (!$all) { require "allFiles.php"; } #Include Functions if they're not already here

#currentURL()
#Gets the current page's full url
function currentURL() {
	return "//$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
}

#numberOfRows("users", "username", $enteredUsername)
#Would return the number of users with the entered username
function numberOfRows($table, $thing = false, $answer = false) {
	if (!$thing && !$answer) {
		$query = mysql_query("SELECT * FROM $table");
	} else {
		$query = mysql_query("SELECT * FROM $table WHERE $thing='$answer'");
	}

	$numrows = mysql_num_rows($query);
	return $numrows;
}

#insertUserBlob("bob", "rmt9c84htnqy54h78tcy54hmgtx", "2step")
#Would insert a user blob for "bob" with the code stated above, and set it as a 2step blob
function insertUserBlob($username, $hash, $action="session") {
	$time  = time();
	$query = mysql_query("INSERT INTO userblobs (user, code, action, date) VALUES ('$username', '$hash', '$action', '$time')");
}

#banCheck("127.0.0.1", "bob)
#Would check if "bob" at "127.0.0.1" is banned
function banCheck($ip, $username = false) {
	$query = mysql_query("SELECT * FROM ban WHERE ip='$ip'"); if (!$query) { return "sql"; }
	while($value = mysql_fetch_array($query)) {
		if ($value['appealed'] === "0") {
			$thing = true;
		} else {
			$thing = false;
		}
	}

	if ($username !== false) {
		$query2 = mysql_query("SELECT * FROM ban WHERE username='$username'"); if (!$query2) { return "sql"; }
		while($value = mysql_fetch_array($query2)) {
			if ($value['appealed'] == "0") {
				if ($thing === false) { $thing = true; } else {$thing = false; }
			} else {
				$thing = false;
			}
		}
	}

	return $thing;
}

#verifySession("rkjgncuguqix4bguiq4mbgu")
#Verifies the session with the stated code
function verifySession($session = false) {
	require "config.php";
	if (!$session) { $session = $_COOKIE[$sitename]; }
	$time    = strtotime( '+30 days' );
	$query   = mysql_query("SELECT * FROM userblobs WHERE code='$session' AND date<'$time' AND action='session'");
	$numrows = mysql_num_rows($query);
	while($value = mysql_fetch_array($query)) { $username = $value['user']; }

	$tamper  = substr($session, -32);

	if ($numrows === 1) {
		if (md5($username.substr($session, 0, 64)) === $tamper) {
			if (banCheck($_SERVER['REMOTE_ADDR']) == false) {
				return true;
			} else {
				return "ban";
			}
		} else {
			return "tamper";
			mysql_query("DELETE FROM userblobs WHERE code='$session' AND action='session' LIMIT 1");
		}
	} else {
		return "session";
	}
}

function sanitize($data) {
	return mysql_real_escape_string($data);
}

#redirect301("http://example.com")
#Would redirect the user or bot to "http://example.com" and set the correct HTTP error so the bot will follow the page
function redirect301($url) {
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: ".$url);
}

#encrypt("myPassword", "bob")
#Would encrypt "bob"'s "myPassword" text
function encrypt($decrypted, $username) {
	$user      = session($username);
	$salt      = $user['salt'];
	$key       = hash('SHA256', $salt, true);
	srand();
	$iv        = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC), MCRYPT_RAND);
	if (strlen($iv_base64 = rtrim(base64_encode($iv), '=')) != 22) return false;
	$encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $decrypted . md5($decrypted), MCRYPT_MODE_CBC, $iv));

	return $iv_base64 . $encrypted;
 }

#decrypt("fnmeuixf4hm98g45hgx849gx4hg98h598g", "bob")
#Would decrypt the stated string of "bob"'s
function decrypt($encrypted, $username) {
	$user      = session($username);
	$salt      = $user['salt'];
	$key       = hash('SHA256', $salt, true);
	$iv        = base64_decode(substr($encrypted, 0, 22) . '==');
	$encrypted = substr($encrypted, 22);
	$decrypted = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, base64_decode($encrypted), MCRYPT_MODE_CBC, $iv), "\0\4");
	$hash      = substr($decrypted, -32);
	$decrypted = substr($decrypted, 0, -32);

	if (md5($decrypted) != $hash) return false;
	return $decrypted;
 }


?>
