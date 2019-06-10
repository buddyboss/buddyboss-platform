#	wp bp xprofile data set

Set profile data for a user.

## OPTIONS

--user-id=&lt;user&gt;
: Identifier for the user. Accepts either a user_login or a numeric ID.

--field-id=&lt;field&gt;
: Identifier for the field. Accepts either the name of the field or a numeric ID.

--value=&lt;value&gt;
: Value to set.

## EXAMPLE

    $ wp bp xprofile data set --user-id=45 --field-id=120 --value=teste
    Success: Updated XProfile field "Field Name" (ID 120) with value  "teste" for user user_login (ID 45).
