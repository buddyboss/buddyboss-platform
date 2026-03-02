#	wp bp message unstar

Unstar a message.

## OPTIONS

&lt;message-id&gt;
: Message ID to unstar.

--user-id=&lt;user&gt;
: User that is unstarring the message. Accepts either a user_login or a numeric ID.

## EXAMPLE

    $ wp bp message unstar 212 --user-id=another_user_login
    Success: Message was successfully unstarred.
