#	wp bp group invite reject

Reject a group invitation.

## OPTIONS

--group-id=&lt;group&gt;
: Identifier for the group. Accepts either a slug or a numeric ID.

--user-id=&lt;user&gt;
: Identifier for the user. Accepts either a user_login or a numeric ID.

## EXAMPLES

    $ wp bp group invite reject --group-id=3 --user-id=10
    Success: Member invitation rejected.

    $ wp bp group invite reject --group-id=foo --user-id=admin
    Success: Member invitation rejected.
