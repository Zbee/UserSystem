#UserSystem [![Build Status](https://travis-ci.org/Zbee/UserSystem.svg)](https://travis-ci.org/Zbee/UserSystem)

A ready to deploy user system that is both secure, and simple.

Runs with PHP 5.4.0+, and makes use of a MySQL database (5.6.20 used in development).

##Todo

* Implement $DATABASE_PREFACE

* Re-make all config variables as constants

* Documentation

* Example site

* Add support for using Authy

##Installation & Use

After downloading the [archive file](https://github.com/Zbee/UserSystem/archive/master.zip) you can add the `/UserSystem/ ` and `/Example/` directories to your own site and the example site will work as soon as you update the database information in `/UserSystem/config.php`.

For more detailed information please see the [documentation](https://zbee.github.io/UserSystem/documentation).

##Features

* 2-step Logins

* User Settings

* Email Verification

* Password Recovery

* Multi-standard compliancy

##Security

* The hashing and transfer methods are all FIPS 140-2 Level 2 compliant (adapted for software; uses a DoD-approved hashing function, tamper-checking on sessions, and removal of possibly tampered-with sessions).

* Uses a system I like to refer to as a blob system, which is basically thus: The cookie is simply a very long string (more than a googol (3.2e184) of possibilities) that is then stored in the database and hard-coded to the user who owns the code. Furthermore, all blobs that can be are destroyed after use, and have expiration dates.

* Can easily be configured (through the straight-forward configuration file) to use an AES-compliant encryption system for emails and IPs that are stored with the user.

* Has a very simple 2Step Authentication system that will delay login of users with it activated and require them to follow a link in an email sent to them, again utilizing the blob system.

* SSL (https://) connections are supported with no extra configuration.

* The logout file has conventions in place to allow for users to destroy every session tied to their account, increasing their ability to keep their own accounts as secure as possible.

* Rainbow and Lookup tables are halted in their tracks by use of very secure salts which are utilized in the hashing of user's passwords. These salts are recreated every time the user changes their password. In addition to that, the salts alone have nearly a googol (7.6e94) possibilities.

* SQL injection attacks are stopped upfront by sanitizing user input. There's also a fall-back with how the PHP and MySQL commands work together, which does not allow for multiple SQL queries to take place in the same PHP function, disabling an attacker from editing data, grabbing data, or removing data from a database.

* The system uses up-to-date methods of communicating with the MySQL server (PDO, not mysql_*) keeping with the times and automatically rendering the system invulnerable to many old attacks.

##Simplicity

* I've included an example site for every account action necessary, removing any strenuous recreation of what a page should have on it.

* It's incredibly simple to fetch and display user information, all you have to do is validate the session and the needed data will be returned.

* All of the options for the login system are nicely wrapped up in a simple configuration file.

* There is no code you have to create for yourself for the most part, the system was created with simplicity in mind.

* The database structure was also created with simplicity in mind, and leaves plenty of room for extra doo-dads.

* Stores valuable data that can be used in many other systems and is vital for many circumstances. Data such as IPs, one previous password, one previous email, the first email, the first username, when they last logged on, when they last changed their password, when they last changed their email, and more!

##License
Author: Ethan Henderson [ethan@zbee.me](mailto:ethan@zbee.me) [https://twitter.com/zbee_](https://twitter.com/zbee_)

Licensed under AOL [http://aol.nexua.org](http://aol.nexua.org)
