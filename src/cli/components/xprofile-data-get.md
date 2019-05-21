#	wp bp xprofile data get

Get profile data for a user.

## OPTIONS

--user-id=&lt;user&gt;
: Identifier for the user. Accepts either a user_login or a numeric ID.

[--field-id=&lt;field&gt;]
: Identifier for the field. Accepts either the name of the field or a numeric ID.

[--format=&lt;format&gt;]
: Render output in a particular format.
 \---
default: table

options:
  - table
  - json
  - haml
\---

[--multi-format=&lt;multi-format&gt;]
: The format for array data.
 \---
default: array

options:
  - array
  - comma
\---

## EXAMPLES

    $ wp bp xprofile data get --user-id=45 --field-id=120
    $ wp bp xprofile data see --user-id=user_test --field-id=Hometown --multi-format=comma
