#	wp bp group invite remove

Uninvite a user from a group.

## OPTIONS

--group-id=&lt;group&gt;
: Identifier for the group. Accepts either a slug or a numeric ID.

--user-id=&lt;user&gt;
: Identifier for the user. Accepts either a user_login or a numeric ID.

## EXAMPLES

    $ wp bp group invite remove --group-id=3 --user-id=10
    Success: User uninvited from the group.

    $ wp bp group invite remove --group-id=foo --user-id=admin
    Success: User uninvited from the group.
