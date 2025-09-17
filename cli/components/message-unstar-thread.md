#	wp bp message unstar-thread

Unstar a thread.

## OPTIONS

&lt;thread-id&gt;
: Thread ID to unstar.

--user-id=&lt;user&gt;
: User that is unstarring the thread. Accepts either a user_login or a numeric ID.

## EXAMPLE

    $ wp bp message unstar-thread 212 --user-id=another_user_login
    Success: Thread was successfully unstarred.
