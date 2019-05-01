#Import Forums

Information about your previous forums database so that they can be converted. **Backup your database before proceeding.**

#### Supported Platforms

*   AEF
*   bbPress1
*   Drupal7
*   Example
*   FluxBB
*   Invision
*   Kunena1
*   Kunena2
*   Kunena3
*   Mingle
*   MyBB
*   Phorum
*   phpBB
*   PHPFox3
*   PHPWind
*   PunBB
*   SimplePress5
*   SMF
*   Vanilla
*   vBulletin
*   vBulletin3
*   XenForo
*   XMB

#### Configuring the Importer

*   Select Platform - Select the source platform that you are importing from.
*   Database Server - Leave as ‘localhost' unless your hosting provider requires you to use an IP address.
*   Database Port - Use default 3306 if unsure
*   Database Name - Name of the database with your old forum data
*   Database User - User for your database connection
*   Database Password - Password to access the database
*   Table Prefix - (If converting from BuddyPress Forums, use “wp\_bb\_” or your custom prefix)

#### Import Options

*   Rows Limit - How many database record to process at a time
*   Delay Time - How long to wait between processes
*   Convert Users - Attempt to import previous forum users
*   Start Over - Start fresh, from the beginning
*   Purge Previous Import - Purge data if an import failed and you want to remove incomplete data

NOTE: After importing forums you will need to use the [Repair Tools](#repair-forums) to correct stats.

[![import forums](https://www.buddyboss.com/resources/wp-content/uploads/2019/01/importforums-1024x804.jpg)](https://www.buddyboss.com/resources/wp-content/uploads/2019/01/importforums.jpg)