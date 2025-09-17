#	wp bp message get

Get a message.

## OPTIONS

&lt;message-id&gt;
: Identifier for the message.

[--fields=&lt;fields&gt;]
: Limit the output to specific fields.

[--format=&lt;format&gt;]
: Render output in a particular format.
\---
default: table

options:
  - table
  - json
  - haml
\---

## EXAMPLES

    $ wp bp message get 5465
    $ wp bp message see 5454
