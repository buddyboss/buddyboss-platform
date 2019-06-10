#	wp bp group member promote

Promote a member to a new status within a group.

## OPTIONS

--group-id=&lt;group&gt;
: Identifier for the group. Accepts either a slug or a numeric ID.

--user-id=&lt;user&gt;
: Identifier for the user. Accepts either a user_login or a numeric ID.

--role=&lt;role&gt;
: Group role to promote the member (mod, admin).

## EXAMPLES

    $ wp bp group member promote --group-id=3 --user-id=10 --role=admin
    Success: Member promoted to new role successfully.

    $ wp bp group member promote --group-id=foo --user-id=admin --role=mod
    Success: Member promoted to new role successfully.
