<?php
/*
This file is part of Zbee/UserSystem.

Zbee/UserSystem is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Zbee/UserSystem is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Zbee/UserSystem.  If not, see <http://www.gnu.org/licenses/>.
*/

#These constants are used to connect to your MySQL database.
define("DB_PREFACE", "us_");                 #A preface to database table names
define("DB_LOCATION", "127.0.0.1");          #The location of the database
define("DB_USERNAME", "root");               #The username for the database
define("DB_PASSWORD", "");                   #The password for the database
define("DB_DATABASE", "us");                 #The name of the database

#These constants are used for URLs and cookies.
define("SITENAME", "examplecom");            #Name of your site (no symbols)
define("URL_PREFACE", "http");               #If http or https is used
define("DOMAIN_SIMPLE", "example.com");      #The root url of your website
define("DOMAIN", "accounts.example.com");    #The url holding the system
define("ACTIVATE_PG", "Example/activate");   #Activation page relative to DOMAIN
define("RECOVERY_PG", "Example/recover");    #Recovery page relative to DOMAIN
define("TWOSTEP_PG", "Example/twostep");     #Two step page relative to DOMAIN

#Optional security constants that can be left as-is.
define("ENCRYPTION", false);                 #Whether or not encryption is used
define("LOCKOUT_ATTEMPTS", 4);               #Number of attempts before temp-ban
define("USERNAME_LOGIN", true);              #If users can login with usernames

define("PASSWORDLESS_EMAIL_LOGIN", false);   #If users can login with just email
define("CONFIRM_USER_EXISTENCE", true);      #If US confirms that users exist
define("PGP_USER_INTERACTION", false);       #If users can have emails encrypted

#Google reCAPTCHA on forms; any level above 0 requires valid keys to work.
define("RECAPTCHA_LEVEL", 0);                #Level of use of reCAPTCHA (0-3)
putenv("RECAPTHCA_SITE_KEY=");               #Your reCATPCHA Site Key
putenv("RECAPTCHA_SECRET=");                 #Your reCAPTCHA Secret

require_once("Utils.php");
require_once("Database.php");
require_once("UserUtils.php");
require_once("UserSystem.php");

if (!isset($tests)) $UserSystem = new UserSystem (DB_DATABASE);
