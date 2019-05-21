#Translating BuddyBoss Platform

The ability to easily change embedded text, labels and messages is essential to admins. This page explains how to use a language translation file to customize the BuddyBoss Platform embedded text, labels and messages.

If this information or instruction is overly technical check out [WordPress translation recommendations](https://developer.wordpress.org/plugins/internationalization/localization/#translate-po-file).

Localization (l10n)<a name="localization"></a>
-------------------

Localization, sometimes abbreviated as l10n, describes the process of translating an internationalized plugin. Files associated with l10n are called POT (Portable Object Template). The BuddyBoss Platform is adding new languages all the time.

Changing Languages<a name="changing-languages"></a>
------------------

Navigate to Dashboard -> Settings -> General -> Site Language to select your language.

![site language](https://www.dropbox.com/s/yc0mdbkmrrpkb7l/sitelanguage.jpg?raw=1)

If BuddyBoss Platform components have not been translated into your language you will need to follow the instructions in the remainder of this page to complete the translation.

Translating Interface<a name="translating-interface"></a>
---------------------

Every translator program requires a POT file to read labels and messages that can be translated. This program will export/create a PO (Portable Object) file. Each PO file will build/create an MO (Machine Object) file. Admins can read PO files while computers/servers read MO files.

The most popular translating program is [Poedit](https://poedit.net/download). This program is open source for all major operating systems. Poedit is simple and easy to use. After installation open the program.

![poedit](https://www.dropbox.com/s/jfah1sgkfqbazjj/poedit.png?raw=1)

Click on the option to Create new translation.

If you have not yet extracted the contents of the BuddyBoss Platform zip file, please do that now. Navigate to the location of BuddyBoss Platform on your computer buddyboss-platform/languages/buddyboss-platform.pot.

![buddyboss platform pot file](https://www.dropbox.com/s/7teyx1ucc7ojdoq/filepath.png?raw=1)

Select your translation language.

![translation language](https://www.dropbox.com/s/fatdhf8x5c0y015/language.png?raw=1)

At this point you are ready to start translating each text by entering text into the translation field on the bottom of the program screen.

![](https://www.dropbox.com/s/7cwtm6ks6ms1hob/image-4-1024x846.png?raw=1)

Once you translate all fields you will need to save your work as buddyboss-platform-{locale}.po where {locale} is your language locale. Then compile to MO. For example, the locale for German is `de_DE`. From the code example above the German MO and PO files should be named `buddyboss-platform-de_DE.mo` and `buddyboss-platform-de_DE.po`.

![poedit save and compile to MO](https://www.dropbox.com/s/fyvtgsag2ionivf/poeditsavecompile-1024x844.jpg?raw=1)

Next, open your favorite [bp_docs_link text="FTP program" slug="getting-started/installation.md" anchors="manual-installation"] and upload the MO file to `wp-content/plugins/buddyboss-platform/languages/`.

If you haven't yet changed the WordPress Language Settings:

*   Go to `wp-admin/options-general.php` or Settings -> General
*   Select your language under Site Language
*   Go to `wp-admin/update-core.php` or Dashboard -> Updates

![dashboard updates](https://www.dropbox.com/s/46r0sqyeii7wwfd/dashboardupdates.jpg?raw=1)

*   Click Update translations, if available
*   Core translations files are downloaded, if available

At this point your site should be translated. If not, please try deleting your browser cache and refresh the browser window.