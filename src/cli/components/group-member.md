#	wp bp group member

Manage Group Member.

## Examples

	$ wp bp group member add --group-id=3 --user-id=10
	Success: Added user #3 to group #3 as member.
	
	$ wp bp group member create --group-id=bar --user-id=20 --role=mod
	Success: Added user #20 to group #45 as mod.
	
	$ wp bp group member remove --group-id=3 --user-id=10
	Success: Member #10 removed from the group #3.
	
	$ wp bp group member delete --group-id=foo --user-id=admin
	Success: Member #545 removed from the group #12.
	
	$ wp bp group member list 3
	$ wp bp group member list my-group
	
	$ wp bp group member unban --group-id=3 --user-id=10
	Success: Member unbanned from the group.
	
	$ wp bp group member unban --group-id=foo --user-id=admin
	Success: Member unbanned from the group.
	
	$ wp bp group member ban --group-id=3 --user-id=10
	Success: Member banned from the group.
	
	$ wp bp group member ban --group-id=foo --user-id=admin
	Success: Member banned from the group.
	
	$ wp bp group member demote --group-id=3 --user-id=10
	Success: User demoted to the "member" status.
	
	$ wp bp group member demote --group-id=foo --user-id=admin
	Success: User demoted to the "member" status.
	
	$ wp bp group member promote --group-id=3 --user-id=10 --role=admin
	Success: Member promoted to new role successfully.
	
	$ wp bp group member promote --group-id=foo --user-id=admin --role=mod
	Success: Member promoted to new role successfully.
