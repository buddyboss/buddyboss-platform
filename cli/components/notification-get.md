#	wp bp notification get

Get specific notification.

## OPTIONS

&lt;notification-id&gt;
: Identifier for the notification.

[--fields=&lt;fields&gt;]
: Limit the output to specific fields.

[--format=&lt;format&gt;]
: Render output in a particular format.
 \---
default: table

options:
  - table
  - csv
  - ids
  - json
  - count
  - yaml
\---

## EXAMPLES

    $ wp bp notification get 500
    $ wp bp notification get 56 --format=json
