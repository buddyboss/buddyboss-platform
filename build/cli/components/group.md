#	wp bp group

Manage BuddyBoss Groups.

## EXAMPLES

	$ wp bp group create --name="Totally Cool Group"
	Success: Group (ID 5465) created: http://example.com/groups/totally-cool-group/
	
	$ wp bp group create --name="Another Cool Group" --description="Cool Group" --creator-id=54 --status=private
	Success: Group (ID 6454)6 created: http://example.com/groups/another-cool-group/
	
	$ wp bp group generate --count=50
    
    $ wp bp group generate --count=5 --status=mixed
    
    $ wp bp group generate --count=10 --status=hidden --creator-id=30
    
    $ wp bp group get 500
    
    $ wp bp group get group-slug
    
    $ wp bp group delete 500
    Success: Group successfully deleted.
    
    $ wp bp group delete group-slug --yes
    Success: Group successfully deleted.
    
    $ wp bp group update 35 --description="What a cool group!" --name="Group of Cool People"
    
    $ wp bp group list --format=ids
    
    $ wp bp group list --format=count
    
    $ wp bp group list --user-id=123
    
    $ wp bp group list --user-id=user_login --format=ids
