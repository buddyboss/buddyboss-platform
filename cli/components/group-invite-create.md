#	wp bp group invite create

Invite a member to a group.

## OPTIONS

[--group-id=&lt;group&gt;]
: Identifier for the group. Accepts either a slug or a numeric ID.

[--user-id=&lt;user&gt;]
: Identifier for the user. Accepts either a user_login or a numeric ID.

[--inviter-id=&lt;user&gt;]
: Identifier for the inviter. Accepts either a user_login or a numeric ID.

[--<field>=&lt;value&gt;]
: One or more parameters to pass. See groups_invite_user()

[--silent]
: Whether to silent the invite creation.

## EXAMPLES

    $ wp bp group invite add --group-id=40 --user-id=10 --inviter-id=1331
    Success: Member invited to the group.

    $ wp bp group invite create --group-id=40 --user-id=admin --inviter-id=804
    Success: Member invited to the group.
