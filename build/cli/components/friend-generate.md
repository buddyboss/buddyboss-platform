#	wp bp friend generate

Generate random friendships.

## OPTIONS

[--count=<number>]
: How many friendships to generate.
\---
default: 100
\---

[--initiator=<user>]
: ID of the first user. Accepts either a user_login or a numeric ID.

[--friend=<user>]
: ID of the second user. Accepts either a user_login or a numeric ID.

[--force-accept]
: Whether to force acceptance.

## EXAMPLES

    $ wp bp friend generate --count=50
    $ wp bp friend generate --initiator=121 --count=50
