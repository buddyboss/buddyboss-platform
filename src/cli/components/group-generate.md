#	wp bp group generate

Generate random groups.

## OPTIONS

[--count=&lt;number&gt;]
: How many groups to generate.
\---
default: 100
\---

[--status=&lt;status&gt;]
: The status of the generated groups. Specify public, private, hidden, or mixed.
\---
default: public
\---

[--creator-id=&lt;creator-id&gt;]
: ID of the group creator.
\---
default: 1
\---

[--enable-forum=&lt;enable-forum&gt;]
: Whether to enable legacy bbPress forums.
\---
default: 0
\---

## EXAMPLES

    $ wp bp group generate --count=50
    $ wp bp group generate --count=5 --status=mixed
    $ wp bp group generate --count=10 --status=hidden --creator-id=30
