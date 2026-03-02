#	wp bp group member unban

Unban a member from a group.

## OPTIONS

--group-id=&lt;group&gt;
: Identifier for the group. Accepts either a slug or a numeric ID.

--user-id=&lt;user&gt;
: Identifier for the user. Accepts either a user_login or a numeric ID.

## EXAMPLES

    $ wp bp group member unban --group-id=3 --user-id=10
    Success: Member unbanned from the group.

    $ wp bp group member unban --group-id=foo --user-id=admin
    Success: Member unbanned from the group.
