#	wp bp message create

Add a message.

## OPTIONS

--from=&lt;user&gt;
: Identifier for the user. Accepts either a user_login or a numeric ID.

[--to=&lt;user&gt;]
: Identifier for the recipient. To is not required when thread id is set.
 Accepts either a user_login or a numeric ID.
\---
Default: Empty.
\---

--subject=&lt;subject&gt;
: Subject of the message.

--content=&lt;content&gt;
: Content of the message.

[--thread-id=&lt;thread-id&gt;]
: Thread ID.
\---
Default: false
\---

[--date-sent=&lt;date-sent&gt;]
: MySQL-formatted date.
\---
Default: current date.
\---

[--silent]
: Whether to silent the message creation.

[--porcelain]
: Return the thread id of the message.

## EXAMPLES

    $ wp bp message create --from=user1 --to=user2 --subject="Message Title" --content="We are ready"
    Success: Message successfully created.

    $ wp bp message create --from=545 --to=313 --subject="Another Message Title" --content="Message OK"
    Success: Message successfully created.
