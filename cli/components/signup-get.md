#	wp bp signup get

Get a signup.

## OPTIONS

&lt;signup-id&gt;
: Identifier for the signup. Can be a signup ID, an email address, or a user_login.

[--match-field=&lt;match-field&gt;]
: Field to match the signup-id to. Use if there is ambiguity between, eg, signup ID and user_login.
\---
options:
  - signup_id
  - user_email
  - user_login
\---

[--fields=&lt;fields&gt;]
: Limit the output to specific signup fields.

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

    $ wp bp signup get 123
    $ wp bp signup get foo@example.com
    $ wp bp signup get 123 --match-field=id
