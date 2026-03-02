#	wp bp friend

Manage BuddyBoss Connections.

## EXAMPLES

	$ wp bp friend create user1 another_use
	Success: Connection successfully created.
	
	$ wp bp friend create user1 another_use --force-accept
	Success: Connection successfully created.
	
	wp bp friend remove user1 another_user
	Success: Connection successfully removed.
	
	$ wp bp friend accept_invitation 2161
	Success: Connection successfully accepted.
	
	$ wp bp friend accept 2161
	Success: Connection successfully accepted.
	
	$ wp bp friend reject_invitation 2161
	Success: Connection successfully accepted.
	
	$ wp bp friend reject 2161 151 2121
	Success: Connection successfully accepted.
	
	$ wp bp friend check 2161 65465
	Success: Yes, they are friends.
	
	$ wp bp friend see 2121 65456
	Success: Yes, they are friends.
	
	$ wp bp friend list 65465 --format=ids
	
	$ wp bp friend list 2422 --format=count
	
	$ wp bp friend generate --count=50
	
	$ wp bp friend generate --initiator=121 --count=50
