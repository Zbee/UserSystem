The Utils class is the base class and holds a bunch of essential methods for
dealing with input and creating or manipulating data.

The methods have input for user data because they don't do anything with the
data on their own, they accept the user input and use it that way.


# __construct
This is run when you create the class. It attempts to connect to the database,
and if it cannot then it stops everything by throwing errors

**Parameters**

- `$dbname` The name of the database you want to use in this instanct of
 UserSystem

**Return**

This either returns nothing or throws this error: "DB_* constants in config.php
failed to connect to a database. " and then whatever PDO has to add.