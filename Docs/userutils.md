# The UserUtils class
The UserUtils class extends the Database class (which extends the Database class) and holds primary methods of the system especially relating specifically to users and their data.

## Methods
The methods in this class primarily focus on users and manipulating their data.

Every method is public, and should be used by you as a developer utilizing this repository in your user-focused application.

### checkBan
This checks whether or not the specified user is listed as banned in the database (without having been appealed).

#### Arguments
- `$username` The user's username you want to search for. It does not need to be set, but if it is then a second search is conducted for the useranme as well as the username.

#### Return
This returns only a boolean - which is `$thing` in the method.

`true` if the user is not banned or if the ban is appealed.

`false` if they are ban and not appealed.