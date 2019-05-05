#Troubleshooting

There are a few steps to problem solving any website issue. This guide will walk you through them one at a time.


### Sections<a name="sections"></a>
*   Identify the Problem
*   Establish a Theory of Probable Cause
*   Test Your Theory
*   Implement Solution
*   Verify Full Functionality

Identify the Problem<a name="Identify-the-Problem"></a>
--------------------

When troubleshooting it is imperative to document everything. Without knowing the steps taken to reproduce a problem how do you explain your problem? Gather the following information:

*   Main website domain name (e.g. buddyboss.com)
*   Who is hosting your website (e.g. GoDaddy, HostGator, Bluehost, etc.)
*   What are your server software versions.
    *   PHP
    *   MySQL
    *   WordPress
*   Check Apache Module mod\_rewrite is installed
*   Check PHP GD or imagick modules are installed
*   Check AllowOverride set to All in folders where .htaccess is located

Next you should be able to explain:

*   What page you were on (e.g. https://demos.buddyboss.com/platform-learndash/groups/private-nature-lovers-group/members/all-members/).
*   What were your actions or what did you click on?
*   What were you trying to do?
*   What error text was displayed?

Instead of writing everything down you can take screenshot or take video of the error replicating process. There are many web browser plugins to choose from the help. ([Nimbus](https://nimbusweb.me/), [Loom](https://home/buddyboss/public_html.useloom.com/), [Awesome Screenshot](https://home/buddyboss/public_html.awesomescreenshot.com/), etc.)

You also need to become familiar with your server error\_log file(s). Many times you can simply log into your website via FTP and see the error\_log that was created. To clean things up can delete the error\_log and repeat the steps to get the error. Look at the first few lines of the error and find the first file that is mentioned, there will also be a function reference and a line number. From this information you can conclude what program is causing errors on your website.

Establish a Theory of Probable Cause<a name="Establish-a-Theory-of-Probable-Cause"></a>
------------------------------------

By now you should be able to fully reproduce the error and explain to anyone not familiar with your website how you came to get it. From identifying the problem you can now test against the files and functions discovered.

Test Your Theory<a name="Test-Your-Theory"></a>
----------------

Now the real work begins. Navigate to your admin Dashboard. Start disabling plugins or components that may be causing the issue. Typically a plugin is going to be the first probable cause of any BuddyBoss Platform error. You also may need to disable other plugins that were not listed in the error\_log. Each time you disable one or more plugins check to see if you can still reproduce the error.

If you have added any custom code to the BuddyBoss Platform please remove it temporarily to eliminate it as a cause.

Try to change the theme of your website to the default WordPress theme. At the time of writing, the current theme was Twenty Nineteen. Still getting an error?

After you have disabled all plugins (excluding BuddyBoss Platform) and are still getting an error start by disabling each component of BuddyBoss Platform.

Have you disabled everything and are still getting an error? We want to hear from you! You probably aren't the only admin getting this error.

Implement Solution<a name="Implement-Solution"></a>
------------------

After you have the solution you must implement it. Sometimes a solution will be technical or beyond your abilities. If that is the case you will need to ask a friend to help, hire someone to do it for you or wait for it to be implemented in the next code revision. Many times the solution is easy for you to implement and is a copy and paste operation. Remember that when asking for help being nice goes a long way.

Verify Full Functionality<a name="Verify-Full-Functionality"></a>
-------------------------

Even experienced programmers make mistakes. After you implement your solution it is your job as admin to make sure everything on your site works as it should. Start with functions that are similar to the original issue and branch out until you verify no more errors on your site. Lastly, enjoy the satisfaction of crossing this issue off your list. Hopefully you will get better at troubleshooting the next time an error arises.