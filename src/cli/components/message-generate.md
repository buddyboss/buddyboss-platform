#	wp bp message generate

Generate random messages.

## OPTIONS

[--thread-id=&lt;thread-id&gt;]
: Thread ID to generate messages against.
\---
default: false
\---

[--count=&lt;number&gt;]
: How many messages to generate.
\---
default: 20
\---

## EXAMPLES

    $ wp bp message generate --thread-id=6465 --count=10
    $ wp bp message generate --count=100
