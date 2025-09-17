#	wp bp group invite delete

Delete a group invitation.

## OPTIONS

--group-id=&lt;group&gt;
: Identifier for the group. Accepts either a slug or a numeric ID.

--user-id=&lt;user&gt;
: Identifier for the user. Accepts either a user_login or a numeric ID.

## EXAMPLES

    $ wp bp group invite delete --group-id=3 --user-id=10
    Success: Member invitation deleted from the group.

    $ wp bp group invite delete --group-id=foo --user-id=admin
    Success: Member invitation deleted from the group.
