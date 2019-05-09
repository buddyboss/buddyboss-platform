#	wp bp message star-thread

Star a thread.

## OPTIONS

&lt;thread-id&gt;
: Thread ID to star.

--user-id=&lt;user&gt;
: User that is starring the thread. Accepts either a user_login or a numeric ID.

## EXAMPLE

    $ wp bp message star-thread 212 --user-id=another_user_login
    Success: Thread was successfully starred.
