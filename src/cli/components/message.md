# wp bp message

Manage Messages.

## Examples
	
	$ wp bp message create --from=user1 --to=user2 --subject="Message Title" --content="We are ready"
	Success: Message successfully created.
	
	$ wp bp message create --from=user1 --to=user2 --subject="Message Title" --content="We are ready"
	Success: Message successfully created.
	
	$ wp bp message create --from=545 --to=313 --subject="Another Message Title" --content="Message OK"
	Success: Message successfully created.
	
	$ wp bp message delete-thread 500 687867 --user-id=40
	Success: Thread successfully deleted.
	
	$ wp bp message delete-thread 564 5465465 456456 --user-id=user_logon --yes
	Success: Thread successfully deleted.
	
	$ wp bp message get 5465
	$ wp bp message see 5454
	
	$ wp bp message list --user-id=544 --format=count
	10
	
	$ wp bp message list --user-id=user_login --count=3 --format=ids
	5454 45454 4545 465465
	
	$ wp bp message generate --thread-id=6465 --count=10
	$ wp bp message generate --count=100
	
	$ wp bp message star 3543 --user-id=user_login
	Success: Message was successfully starred.
	
	$ wp bp message unstar 212 --user-id=another_user_login
	Success: Message was successfully unstarred.
	
	$ wp bp message star-thread 212 --user-id=another_user_login
	Success: Thread was successfully starred.
	
	$ wp bp message unstar-thread 212 --user-id=another_user_login
	Success: Thread was successfully unstarred.
	
	$ wp bp message send-notice --subject="Important notice" --content="We need to improve"
	Success: Notice was successfully sent.
