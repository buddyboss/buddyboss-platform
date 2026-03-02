#	wp bp message delete-thread

Delete thread(s) for a given user.

## OPTIONS

&lt;thread-id&gt;...
: Thread ID(s).

--user-id=&lt;user&gt;
: Identifier for the user. Accepts either a user_login or a numeric ID.

[--yes]
: Answer yes to the confirmation message.

## EXAMPLES

    $ wp bp message delete-thread 500 687867 --user-id=40
    Success: Thread successfully deleted.

    $ wp bp message delete-thread 564 5465465 456456 --user-id=user_logon --yes
    Success: Thread successfully deleted.
