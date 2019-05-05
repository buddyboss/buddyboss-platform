#Backing Up Your Database and Files

What if the unthinkable happened in the form of a hacker, virus or accident, could you restore your website? If not, you could be looking at hours or even days of headache to repair your once polished website. Many admins ignore backups because "it won't happen to them". They're wrong.

[Check out the WordPress manual database backup tutorial](https://codex.wordpress.org/Backing_Up_Your_Database)

Best Practices for File Backups<a name="Best-Practices-for-File-Backups"></a>
-------------------------------

*   Start from a baseline - As soon as you have your website live and initially configured you should run a full and complete backup. This will establish a baseline in case anything happens you have somewhere to start instead of doing it all over again.
*   Keep your backup in a safe place - Keep your backups in several locations. Online, offline, USB drive, give a copy to a friend or all of the above. You can never have too many backup options.
*   Don't replace your baseline - Online storage is cheap. Don't delete your old backups.
*   Backing up the backup - It's OK to backup your backup file. It will simply be nested in other backups and make certain those files aren't modified.
*   Incremental backups save space - Many hosting providers don't offer incremental backups these days due to the nature of low cost storage but this can be a good idea and easy to restore from. Only files that are edited will be added to incremental backup files.
*   Backup frequency - Even if you aren't adding more physical pages to your site any images, files or other uploads to your site would not be backed up. Usually every month is a good place to start but if your site has many members uploading new content every day you may need to increase your backup frequency.
*   Name your backups - Make sure you name your backups in way that YOU can identify what they are so you know which one to use.
*   Practice, practice, practice - Be comfortable with the backup and restore process. Make a sub-domain you can play with by copying your current website information to.
*   Don't be afraid to ask for help - If you made a mistake or if your website was compromised get help from a knowledgeable friend or your helpful hosting provider support representative.
*   Automate your backups - If your hosting provider offers a backup service consider asking for it. It may not seems like a worthwhile investment at first but when your site needs to be restored from a backup you will be grateful.

Best Practices for Database Backups<a name="Best-Practices-for-Database-Backups"></a>
-----------------------------------

Now for the tricky part. Databases can be difficult to navigate for admins not familiar with command line interfaces or linux. Don't let that scare you away, many hosting companies are trying to make database backups easy for admins.

*   Backup your database file to a location that will also be backed up by your file backup. This will hit two birds with one stone. Do your database backup to a website file location then run your file backup and, boom, both are backed up! Just make sure you don't put your database backup file on the open internet by accident.
*   Backup frequency - this depends on how much content you add to your website and how many members you have and how active they are. If you have over 100 active members you should consider daily backups.
*   cPanel backups - most hosting companies use a back-end software called cPanel. There is a fairly easy way to download your database file through the cPanel backup system. The files will automatically be named the same as the database with a timestamp. Last step is to save your backup in several places.
*   Automated backups are best - if you have the option to utilize automatic backups do it! Best to have it and not use it than need it and not have it.