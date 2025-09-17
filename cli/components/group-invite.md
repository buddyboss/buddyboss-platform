#	wp bp group invite

Manage Group Invites.

## Examples

	$ wp bp group invite add --group-id=40 --user-id=10 --inviter-id=1331
    Success: Member invited to the group.
    
    $ wp bp group invite create --group-id=40 --user-id=admin --inviter-id=804
    Success: Member invited to the group.
    
    $ wp bp group invite remove --group-id=3 --user-id=10
    Success: User uninvited from the group.
    
    $ wp bp group invite remove --group-id=foo --user-id=admin
    Success: User uninvited from the group.
    
    $ wp bp group invite list --user-id=30 --group-id=56
    
    $ wp bp group invite generate --count=50
    
    $ wp bp group invite accept --group-id=3 --user-id=10
    Success: User is now a "member" of the group.
    
    $ wp bp group invite accept --group-id=foo --user-id=admin
    Success: User is now a "member" of the group.
    
    $ wp bp group invite reject --group-id=3 --user-id=10
    Success: Member invitation rejected.
    
    $ wp bp group invite reject --group-id=foo --user-id=admin
    Success: Member invitation rejected.
    
    $ wp bp group invite delete --group-id=3 --user-id=10
    Success: Member invitation deleted from the group.
    
    $ wp bp group invite delete --group-id=foo --user-id=admin
    Success: Member invitation deleted from the group.
