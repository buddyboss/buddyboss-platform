#	wp bp signup create

Add a signup.

## OPTIONS

[--user-login=&lt;user-login&gt;]
: User login for the signup.

[--user-email=&lt;user-email&gt;]
: User email for the signup.

[--activation-key=&lt;activation-key&gt;]
: Activation key for the signup. If none is provided, a random one will be used.

[--silent]
: Whether to silent the signup creation.

[--porcelain]
: Output only the new signup id.

## EXAMPLE

    $ wp bp signup create --user-login=test_user --user-email=teste@site.com
    Success: Successfully added new user signup (ID #345).
