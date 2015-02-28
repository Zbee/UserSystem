# The configuration file
The configuration file serves to both establish your settings and preferences and be a sort of autoloader for the system.
When you include the configuration file, it will set your settings and load all the different classes of the system and initialize it.


## Settings
The various settings you can use here are universal throughout the system and is your one stop for settings, you won't have to change anything inside the classes in the actual code.

### MySQL settings
These settings are used to connect to your MySQL database and is used in most methods and is run in a construction of a PDO object everytime the UserSystem is initialized.

- `DB_PREFACE` This is any preface you would like for the tables created and used by the system.
If you were to set this to be `us_` (as is the default) each table would have that prepended to its name giving you something like `us_users`.

- `DB_LOCATION` This is the address the MySQL server is located at. This will often times just be `localhost` (if so, change it from `localhost` to `127.0.0.1` to force TCP), but you can use remote servers here as well; whether it be an IP address or an actual domain, it just needs to work in a [PDO DSN](http://php.net/manual/en/pdo.construct.php).

- `DB_USERNAME` This is the username used to access your MySQL server. The default is `root` but you could have other users set up as well.

- `DB_PASSWORD` This is the password used with the username to access your MySQL server. By default there is no password set for `root`, but you may have a password set for `root` or for your own user.

- `DB_DATABASE` This is the database that will be used to hold your tables. You will have to create a table or use an existing one, the recommended one is `us` and that will keep the UserSystem tables and data separate from the rest of your site's information, but it can work just as well if the UserSystem tables are bunched in with many other of your own tables. When you run the setup file the system will attempt to create this database if it doesn't exist; however it will not attempt to create the database when you construct the UserSystem. Therefore, if you want to use a different database for just a few lines of script, you'll need to initialize an intentionally empty UserSystem (`new UserSystem ("")`), create the database (`$UserSystem->DATABASE->query("CREATE DATABASE test")`), and then use that database with a new initialization of UserSystem (`new UserSystem ("test")`).

### URL settings
These settings are used in displaying links to your users, setting cookies for your site, and can be used by you in your code avoiding hard links to the UserSystem location.

- `SITENAME` This is the name of your website, it is used in sentences like `Your password has changed on SITENAME` and is used to name the cookie `$_COOKIE[SITENAME]` so it just needs to be letters and numbers, no spaces. For example, if your site was `The Face Magazine` you may want to use `FaceMagazine`, that will make cookies function, and avoid looking terrible.

- `URL_PREFACE` This is the protocol used in links to your site. This is used exclusively in displaying links to your users like this: `URL_PREFACE://example.com`.

- `DOMAIN_SIMPLE` This is the root URL of your entire website. This is used for the creation (and destruction) of cookies; it needs to be the root URL so that if your UserSystem is on `accounts.example.com` your users can stay logged in even if they are later using `example.com` or `blog.example.com`.

- `DOMAIN` This is the URL to where the UserSystem resides. If you have the UserSystem on `accounts.example.com` your would want to put exactly that for this setting. This is used just for displaying links to your users such as: `URL_PREFACE://DOMAIN/activate-your-account`.

- `SYSTEM_LOC` This is the location of the UserSystem code relative to the `DOMAIN` setting. If your UserSystem is on `accounts.example.com` (that would be the `DOMAIN`) but your code is in the `/UserSystem/` folder, you'd want to put `/UserSystem`. It is used like this: `URL_PREFACE://DOMAIN SYSTEM_LOC`, so if you want the code in the root of your directory, you can just set this setting to `/`.

- `ACTIVATE_PG` The location of the page you set up for user activation relative to the `DOMAIN` setting. This is only used for displaying links to users like this: `URL_PREFACE://DOMAIN/ACTIVATE_PG`. So if your activation script is `activate.php` then you'll use exactly that; if your activation script is `/activate/index.php` you can simply put `activate` for this setting.

- `RECOVERY_PG` This is the same as the `ACTIVATE_PG` setting, but for the user account recovery script.

- `TWOSTEP_PG` This is the same as the `ACTIVATE_PG` setting, but for the script to finish logging users in if they have two step authentication enabled.

### Extra settings
These settings are for extra features that aren't even required to be changed for the system to work just as you'd like

- `ENCRYPTION` This is just a boolean of whether or not your would like the system to encrypt some of your users' data in the MySQL tables with AES encryption.

- `AUTHY_2STEP` (Not currently employed) This is just a boolean of whether or not you would like for Authy to be used for two step authentication instead of the standard email-to-the-user-to-finish-logging-in system. This requires an Authy API key, the ability to send SMS (undecided how the system will send these), and storing of users' phone numbers.

- `RECAPTCHA_LEVEL` (Not currently employed) This is an integer between 0 and 3 (inclusive: 0, 1, 2, or 3) that dictates how often reCAPTCHAs come up for users. 0 = never; 1 = at registration; 2 = registration + logging in; 3 = registration + login + setting changes. You'll have to be mindful of what you set this to, because you're responsible for placing the reCAPTCHA forms on the pages that will need the reCAPTCHAs, the system will be checking if the reCAPTCHA was right, and if you didn't provide one then users' actions will fail 100% of the time.

The following settings are only even necessary if you turned on 1 of the previous 2 settings.

- `AUTHY_API_KEY` (Not currently employed, is environment variable, so you could set it elsewhere) This is your API key from Authy. This is only necessary if you enabled the `AUTHY_2STEP` setting.

- `RECAPTCHA_SITE_KEY` (Not currently employed, is environment variable, so you could set it elsewhere) This is your site key from reCAPTCHA. This is only necessary if you set `RECAPTHCA_LEVEL` to >= 1.

- `RECAPTCHA_SECRET` (Not currently employed, is environment variable, so your could set it elsewhere) This is your secret key from reCAPTCHA. This is only necessary if you set `RECAPTCHA_LEVEL` to >= 1.

## Loader
Once your settings are all set, the file then includes the 3 parts of the UserSystem: Utils (base functions for the system), Database (the database functions for the system), and UserSystem (all the other functions for users) and (unless you're currently running tests with the system) starts an instance of the UserSystem in the `$UserSystem` variable using all your default options.