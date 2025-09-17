#	wp bp group member remove

Remove a member from a group.

## OPTIONS

--group-id=&lt;group&gt;
: Identifier for the group. Accepts either a slug or a numeric ID.

--user-id=&lt;user&gt;
: Identifier for the user. Accepts either a user_login or a numeric ID.

## EXAMPLES

    $ wp bp group member remove --group-id=3 --user-id=10
    Success: Member #10 removed from the group #3.

    $ wp bp group member delete --group-id=foo --user-id=admin
    Success: Member #545 removed from the group #12.
