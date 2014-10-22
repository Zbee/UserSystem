<?phpob_start();require "config.php";if (!$systemIncluded) { require "UserSystem.php"; }$UserSystem = new UserSystem (DATABASE, OPTIONS);
