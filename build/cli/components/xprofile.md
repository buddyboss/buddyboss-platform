#	wp bp xprofile

Manage BuddyPress XProfile.

## EXAMPLES

	# Save a xprofile data to a user with its field and value.
	$ wp bp xprofile data set --user-id=45 --field-id=120 --value=teste
	Success: Updated XProfile field "Field Name" (ID 120) with value  "teste" for user user_login (ID 45).
	
	# Create a xprofile group.
	$ wp bp xprofile group create --name="Group Name" --description="Xprofile Group Description"
	Success: Created XProfile field group "Group Name" (ID 123).
	
	# List xprofile fields.
	$ wp bp xprofile field list
