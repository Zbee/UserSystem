# Methods
The Utils class is the base class and holds a bunch of essential methods for
dealing with input and creating or manipulating data.

The methods have input for user data because they don't do anything with the
data on their own, they accept the user input and use it that way.


# __construct
This is run when you create the class. It attempts to connect to the database,
and if it cannot then it stops everything by throwing errors

## Parameters
- `$dbname` The name of the database you want to use in this instanct of
 UserSystem

## Return
This either returns nothing or throws this error: "DB_* constants in config.php
failed to connect to a database. " and then whatever PDO has to add.


# encrypt
This encrypts data for a specific user.

## Parameters
- `$decrypted` The raw data you would like to encrypt
- `$username` The username of the user you would like to encrypt the data for

## Return
This returns one of two different things, a string or false.

`false` is when the IV was created incorrectly, which will only ever be if
MCRYPT is not present.

A string is returned when encryption works, and it'll be the encrypted string
including 32 characters on the end which is an md5 of the original data, used
by the decrypt function to verify that everyone whent smoothly.

## Notes
Presently if you just know the user's username and have this code then you
could decrypt any user data.

If this and decrypt were moved to UserUtils then it could look up the user's
salt and encrypt the data with that as well. However, that' require that the
salt does not change, which would be fine.


# decrypt
This decrypts data from the encrypt function.

## Parameters
- `$encrypted` The encrypted data you would like to decrypt
- `$username` The username of the user you would like to decrypt the data for

## Return
This returns one of two different things, a string or false.

`false` is when the hash of the decrypted data does not match the hash of the
raw data included at the end of the encrypted data. This means that something
happened when decryption that has resulted in different data, most likely that
being the key used (in this case, the username changed).

A string is returned when decryption works, and it'll be the decrypted string.