#	wp bp message star

Star a message.

## OPTIONS

&lt;message-id&gt;
: Message ID to star.

--user-id=&lt;user&gt;
: User that is starring the message. Accepts either a user_login or a numeric ID.

## EXAMPLE

    $ wp bp message star 3543 --user-id=user_login
    Success: Message was successfully starred.
