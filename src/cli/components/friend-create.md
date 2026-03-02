#	wp bp friend create

Create a new friendship.

## OPTIONS

&lt;initiator&gt;
: ID of the user who is sending the friendship request. Accepts either a user_login or a numeric ID.

&lt;friend&gt;
: ID of the user whose friendship is being requested. Accepts either a user_login or a numeric ID.

[--force-accept]
: Whether to force acceptance.

[--silent]
: Whether to silent the message creation.

[--porcelain]
: Return only the friendship id.

## EXAMPLES

    $ wp bp friend create user1 another_use
    Success: Connection successfully created.

    $ wp bp friend create user1 another_use --force-accept
    Success: Connection successfully created.
