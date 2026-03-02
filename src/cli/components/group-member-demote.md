#	wp bp group member demote

Demote user to the 'member' status.

## OPTIONS

--group-id=&lt;group&gt;
: Identifier for the group. Accepts either a slug or a numeric ID.

--user-id=&lt;user&gt;
: Identifier for the user. Accepts either a user_login or a numeric ID.

## EXAMPLES

    $ wp bp group member demote --group-id=3 --user-id=10
    Success: User demoted to the "member" status.

    $ wp bp group member demote --group-id=foo --user-id=admin
    Success: User demoted to the "member" status.
