#	wp bp friend check

Check whether two users are friends.

## OPTIONS

&lt;user&gt;
: ID of the first user. Accepts either a user_login or a numeric ID.

&lt;friend&gt;
: ID of the other user. Accepts either a user_login or a numeric ID.

## EXAMPLES

    $ wp bp friend check 2161 65465
    Success: Yes, they are friends.

    $ wp bp friend see 2121 65456
    Success: Yes, they are friends.
