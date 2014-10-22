<?php

require "loginSystem/allFiles.php"; #Include all other files so that any page can do any primary function of the system

$session = session(); #Use the session function to add all information about the user to the session variable

if (verifySession() !== true) { redirect301("//{$domain}?url=".currentURL()); } else { $error .= '<div class="alert alert-success">You\'re logged in: '.$session['username'].'</div>'; } #If the session was correctly verified, show them a message saying they're logged in. Otherwise, redirect them to the login page

if (isset($_GET['fail']) && $_GET['fail'] === "match") { $error = '<div class="alert alert-warning">Entered fields did not match.</div>'; }

?>
<!DOCTYPE html>
<html>
	<head>
		<title>Edit Page</title>
		<link href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css" rel="stylesheet">
		<script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
	</head>
	<body>
		<div class="container" style="margin-top:30px">
			<div class="col-md-4 col-md-offset-4">
				<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title">
						<strong>Edit Page</strong>
					</h3>
				</div>
				<div class="panel-body">
					<?php echo $error; ?>
					<form class="form" method="post" action="//<?php echo $domain.$system_location; ?>/edit.php?email&url=<?php echo urlencode(currentURL()); ?>">
					  <div class="well">
              <div class="form-group">
                <label class="control-label" for="email">New Email</label>
                <input type="email" class="form-control" name="ne">
              </div>
              <div class="form-group">
                <label class="control-label" for="email">Confirm New Email</label>
                <input type="email" class="form-control" name="cne">
              </div>
              <input type="submit" class="btn btn-default btn-block button btn-lg" value="Change email"></input>
            </div>
          </form>
          <form class="form" method="post" action="//<?php echo $domain.$system_location; ?>/edit.php?pass&url=<?php echo urlencode(currentURL()); ?>">
            <div class="well">
              <div class="form-group" id="Field1Group">
                <label class="control-label" for="Field1">New Password</label>
                <input type="password" id="Field1" class="form-control" name="np">
              </div>
              <div class="form-group" id="Field2Group">
                <label class="control-label" for="Field2">Confirm New Password</label>
                <input type="password" id="Field2" class="form-control" name="cnp">
              </div>
              <input type="submit" class="btn btn-default btn-block button btn-lg" value="Change Password"></input>
            </div>
          </form>
					<a href="//<?php echo $domain.$system_location; ?>/logout.php?url=<?php echo urlencode(currentURL()); ?>" class="btn btn-sm btn-default col-xs-6">Logout</a>
					<a href="//<?php echo $domain.$system_location; ?>/logout.php?all&url=<?php echo urlencode(currentURL()); ?>" class="btn btn-sm btn-default col-xs-6">Logout all sessions</a>
				</div>
				</div>
			</div>
		</div>
	</body>
</html>
