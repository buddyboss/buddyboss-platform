#	wp bp group member ban

Ban a member from a group.

## OPTIONS

--group-id=&lt;group&gt;
: Identifier for the group. Accepts either a slug or a numeric ID.

--user-id=&lt;user&gt;
: Identifier for the user. Accepts either a user_login or a numeric ID.

## EXAMPLES

    $ wp bp group member ban --group-id=3 --user-id=10
    Success: Member banned from the group.

    $ wp bp group member ban --group-id=foo --user-id=admin
    Success: Member banned from the group.
