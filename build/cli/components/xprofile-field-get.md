#	wp bp xprofile field get

Get an XProfile field.

## OPTIONS

&lt;field-id&gt;
: Identifier for the field. Accepts either the name of the field or a numeric ID.

[--fields=&lt;fields&gt;]
: Limit the output to specific fields.
\---
Default: All fields.
\---

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

    $ wp bp xprofile field get 500
    $ wp bp xprofile field see 56 --format=json
