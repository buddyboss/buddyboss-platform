#	wp bp signup

Manage Signup.

## Examples

	$ wp bp signup list --format=ids
	
	$ wp bp signup list --number=100 --format=count
	
	$ wp bp signup list --number=5 --activation_key=ee48ec319fef3nn4
	
	$ wp bp signup get 123
	
	$ wp bp signup get foo@example.com
	
	$ wp bp signup get 123 --match-field=id
	
	$ wp bp signup generate --count=50
	
	$ wp bp signup delete 520
	Success: Signup deleted.
	
	$ wp bp signup delete 55654 54564 --yes
	Success: Signup deleted.
	
	$ wp bp signup create --user-login=test_user --user-email=teste@site.com
	Success: Successfully added new user signup (ID #345).
	
	$ wp bp signup activate ee48ec319fef3nn4
	Success: Signup activated, new user (ID #545).
	
	$ wp bp signup resend test@example.com
	Success: Email sent successfully.
