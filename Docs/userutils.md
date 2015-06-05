The UserUtils class is the class resting on top of the Database class which
holds the methods responsible for low-level user data manipulation.


# encrypt
This encrypts data for a specific user.

**Parameters**

- `$decrypted` The raw data you would like to encrypt
- `$username` The username of the user you would like to encrypt the data for

**Return**

This returns one of two different things, a string or false.

`false` is when the IV was created incorrectly, which will only ever be if
MCRYPT is not present, or when the user was not found.

A string is returned when encryption works, and it'll be the encrypted string
including 32 characters on the end which is an md5 of the original data, used
by the decrypt function to verify that everyone whent smoothly.


# decrypt
This decrypts data from the encrypt function.

**Parameters**

- `$encrypted` The encrypted data you would like to decrypt
- `$username` The username of the user you would like to decrypt the data for

**Return**

This returns one of two different things, a string or false.

`false` is when the hash of the decrypted data does not match the hash of the
raw data included at the end of the encrypted data or when the user was not
found.

A string is returned when decryption works, and it'll be the decrypted string.