window.bp = window.bp || {};

(function() {

    function renderIntegrations() {
        var defaultOptions = {
            previewParent: $( '.bb-integrations-section-listing' ),
            data: [
                {
                    "type": "title",
                    "text": "Ad Manager"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/Advanced-Ads.png",
                    "int_type": "Compatible",
                    "title": "Advanced Ads",
                    "desc": "Manage and optimize your ads in WordPress"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/Advanced-Ads-Add-ons-e1588826603796.png",
                    "int_type": "Compatible",
                    "title": "Advanced Ads Add-Ons",
                    "desc": "Support for almost all Advanced Ads Add-ons"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/09/Advanced-Ads-BuddyBoss-Integration.png",
                    "int_type": "Third-party",
                    "title": "Advanced Ads Pro - BuddyBoss Integration",
                    "desc": "Connect BuddyBoss with Advanced Ads Pro"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/03/AWCP.png",
                    "int_type": "Compatible",
                    "title": "AWP Classifieds",
                    "desc": "Add a classified ads section to your WordPress site"
                },
                {
                    "type": "title",
                    "text": "Affiliate Management"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/WP-Affiliate-Manager.png",
                    "int_type": "Compatible",
                    "title": "WP Affiliate Manager",
                    "desc": "Recruit, manage, track and pay your affiliates"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/WP-Affilate-Manager-Add-ons.png",
                    "int_type": "Compatible",
                    "title": "WP Affiliate Manager Add-ons",
                    "desc": "Support for almost all WP Affiliate Manager Add-ons"
                },
                {
                    "type": "title",
                    "text": "Anti-spam"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/Social_Login.png",
                    "int_type": "Compatible",
                    "title": "Social Login",
                    "desc": "Fully-customizable plugin that integrates with your existing login/registration system"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/05/Thrive-Comments.png",
                    "int_type": "Compatible",
                    "title": "Thrive Comments",
                    "desc": "A superior WordPress comments plugin"
                },
                {
                    "type": "title",
                    "text": "Automation"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/06/AutomatorWP.png",
                    "int_type": "Compatible",
                    "title": "AutomatorWP",
                    "desc": "Create automated workflows for your WordPress website"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/06/AutomatorWP-Add-ons.png",
                    "int_type": "Third-party",
                    "title": "AutomatorWP + BuddyBoss Integration",
                    "desc": "Connect BuddyBoss with AutomatorWP"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/06/AutomatorWP-Add-ons.png",
                    "int_type": "Compatible",
                    "title": "AutomatorWP Add-ons",
                    "desc": "Support for almost all AutomatorWP Add-ons"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2023/04/Suretrigger-512x512-logo-624x624.png",
                    "int_type": "Third-party",
                    "title": "SureTriggers",
                    "desc": "We help you connect your apps and automate your business."
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/uncanny-automator-mascot-icon-4-624x624.png",
                    "int_type": "Third-party",
                    "title": "Uncanny Automator",
                    "desc": "Set triggers and actions to automate stuff on your WordPress"
                },
                {
                    "type": "title",
                    "text": "bbPress"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "bbPress Auto Block Spammers",
                    "desc": "Block spammers from using your forums"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/02/rtMedia-for-WordPress-BuddyPress-and-bbPress.png",
                    "int_type": "Compatible",
                    "title": "rtMedia for WordPress, BuddyPress, and bbPress",
                    "desc": "Media solution for your WordPress, BuddyPress, and bbPress sites"
                },
                {
                    "type": "title",
                    "text": "BuddyBoss App"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2023/01/blockli-624x624.jpeg",
                    "int_type": "Third-party",
                    "title": "Blockli",
                    "desc": "Beautiful Screens For Your BuddyBoss App"
                },
                {
                    "type": "title",
                    "text": "BuddyPress"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "Block, Suspend, Report for BuddyPress",
                    "desc": "Allow users to block and report other members, and allow administrators to suspend users"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BP Auto Group Join",
                    "desc": "Automatically join new and existing BuddyPress members to BuddyPress Groups"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BP Bulk Delete",
                    "desc": "Bulk delete BuddyPress Activity, Message and Notifications"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BP Email Assign Templates",
                    "desc": "Override the default BuddyPress email template"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BP Local Avatars",
                    "desc": "Create Gravatar Avatars and store them locally"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BP Maps for Members",
                    "desc": "Add your BuddyPress member locations and maps to your website"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BP Messages Tool",
                    "desc": "Manage messages sent and received by your BuddyPress users"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BP Profile Shortcodes Extra",
                    "desc": "Display a range of aspects from member profiles and groups using shortcodes"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BP Simple Private",
                    "desc": "Hide content from non-logged in users"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BP xProfile Location",
                    "desc": "Populate and validate the address fields for members"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Third-party",
                    "title": "Buddy User Notes",
                    "desc": "Let your members create notes and reminders from their Profile page"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BuddyBlog",
                    "desc": "Easy frontend blogging with BuddyPress"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyBoss_Plugin.png",
                    "int_type": "Compatible",
                    "title": "BuddyBoss Media",
                    "desc": "Upload photos, create and manage albums for BuddyPress profiles and groups"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BuddyDrive",
                    "desc": "Let community members share files or folders with ease"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyForms_Members.png",
                    "int_type": "Compatible",
                    "title": "BuddyForms Members",
                    "desc": "Extension for the BuddyForms form builder plugin"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BuddyMessageUX",
                    "desc": "Allow users to send messages without leaving the profile page"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BuddyNotes",
                    "desc": "Let your members create notes from their Profile page"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BuddyPages",
                    "desc": "Add custom pages to BuddyPress groups and member profiles"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BuddyPress Analytics",
                    "desc": "Track users' visits across your BuddyPress website"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BuddyPress Clear Notifications",
                    "desc": "Allow your users to clear all the notifications in one click"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BuddyPress Compliments",
                    "desc": "Add a smart way for BuddyPress members to interact with each other via compliments"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BuddyPress Confirm Actions",
                    "desc": "Ask the user to confirm before cancelling friendship/leaving group/unfollowing other users"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BuddyPress Deactivate Account",
                    "desc": "Allows users to deactivate/reactivate their account"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BuddyPress Default Cover Photo",
                    "desc": "Replace the default BuddyPress cover photo "
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BuddyPress Default Group Tab",
                    "desc": "Control the default landing component/page of a group"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BuddyPress Edit Activity",
                    "desc": "Let BuddyPress members edit their activity posts and replies on the front-end"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BuddyPress Editable Activity",
                    "desc": "Allow users to edit their activity and activity comments easily "
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BuddyPress Extended Friendship Request",
                    "desc": "Allow users to send a personalized message with the BuddyPress friendship requests"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BuddyPress Featured Members",
                    "desc": "Display the list of featured users as list or slider"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/01/LearnDash_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BuddyPress for LearnDash",
                    "desc": "Turn your online courses site into a social education platform"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BuddyPress Friendship Restrictions",
                    "desc": "Restrict BuddyPress Friendship features"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BuddyPress Global Search",
                    "desc": "Let your members search through every BuddyPress component"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BuddyPress Group Email Subscription",
                    "desc": "Email subscriptions for BuddyPress Groups"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BuddyPress Group Tabs Creator Pro",
                    "desc": "Create and manage unlimited BuddyPress Group tabs and sub-tabs"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BuddyPress Lock Unlock Activity",
                    "desc": "Allow users to lock/open their activity feeds for commenting"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BuddyPress Moderation Tools",
                    "desc": "Add a Report Abuse and other moderation functionality to BuddyPress "
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BuddyPress Poke",
                    "desc": "Allow your BuddyPress users to poke each other like Facebook"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BuddyPress Power SEO",
                    "desc": "Enable SEO functionality for BuddyPress"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BuddyPress Private Message Rate Limiter",
                    "desc": "Restrict users from sending a large number of messages"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BuddyPress Profile Visibility Manager",
                    "desc": "Allow users to manage their account privacy"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BuddyPress Recent Profile Visitors",
                    "desc": "Show most recent profile visitors and most popular users"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BuddyPress Registration Options",
                    "desc": "Prevent users and bots from accessing your BuddyPress or bbPress components until they are approved"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BuddyPress Simple Events",
                    "desc": "Allow members to create, edit and delete Events from their profile page"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BuddyPress Simple Terms And Conditions",
                    "desc": "Add an opt-in checkbox to the BuddyPress registration form"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BuddyPress User Badges",
                    "desc": "Add badge functionality to BuddyPress based communities"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyBoss_Plugin.png",
                    "int_type": "Compatible",
                    "title": "BuddyPress User Blog",
                    "desc": "Personal blog space for your members"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BuddyPress User Circles",
                    "desc": "Allow users to create user lists"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BuddyPress User Contact Form",
                    "desc": "Let users have a contact form on their profile"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Third-party",
                    "title": "BuddyPress User Profile Tabs Creator Pro",
                    "desc": "Create and manage BuddyPress user profile tabs "
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BuddyPress User Testimonials",
                    "desc": "Allow users to leave recommendations/testiomonials for other users"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "BuddyPress Xprofile Custom Field Types",
                    "desc": "Add essential field types to BuddyPress profile"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "Conditional Profile Fields for BuddyPress",
                    "desc": "Set conditions for the hiding/showing profile fields based conditional logic"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/01/GamiPress-BuddyPress-Integration.png",
                    "int_type": "Compatible",
                    "title": "GamiPress – BuddyPress Group Leaderboard",
                    "desc": "Add a leaderboard in BuddyPress groups "
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/01/GamiPress-BuddyPress-Integration.png",
                    "int_type": "Compatible",
                    "title": "GamiPress + BuddyPress Integration",
                    "desc": "Gamify your BuddyPress community website"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/05/GeoDirectory-BuddyPress-Integration.png",
                    "int_type": "Third-party",
                    "title": "GeoDirectory + BuddyPress Integration",
                    "desc": "Create a hybrid listings directory and social network"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/08/Groundhogg-BuddyPress-Integration.png",
                    "int_type": "Third-party",
                    "title": "Groundhogg - BuddyBoss Integration",
                    "desc": "Combine the power of BuddyBoss and Groundhogg"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "Invite Anyone",
                    "desc": "Allow your user to  send email invites to groups and the site"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/02/LifterLMS-BuddyPress-Integration.png",
                    "int_type": "Compatible",
                    "title": "LifterLMS - BuddyPress Integration",
                    "desc": "Display the LifterLMS Student Dashboard page content on a user's BuddyPress profile"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "Limit BuddyPress Groups Per User",
                    "desc": "Restrict the number of groups a can create on your BuddyPress site"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/MediaPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "MediaMark",
                    "desc": "Watermark solution for MediaPress add-on"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/MediaPress.png",
                    "int_type": "Compatible",
                    "title": "MediaPress",
                    "desc": "Modern media gallery solution for WordPress"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/MediaPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "MediaPress Add-ons",
                    "desc": "Support for almost all MediaPress Add-ons"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/MediaPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "MediaPress Featured Content",
                    "desc": "Let your users showcase their BuddyPress media"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/MediaPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "MediaPress Media Moderator",
                    "desc": "Keep your media managed with the moderation tools"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "MediaPress Paid Memberships Pro Restrictions",
                    "desc": "Add restrictions based on membership levels for MediaPress"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/MediaPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "MediaPress User Watermark",
                    "desc": "Allow users to add a watermark on media"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/MemberPress-BuddyPress.png",
                    "int_type": "Compatible",
                    "title": "MemberPress + BuddyPress",
                    "desc": "Integrate powerful social features of BuddyPress with MemberPress"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/myCRED-Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "myCRED BuddyPress Charges",
                    "desc": "Charge your BuddyPress/BuddyBoss users for event triggers"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/myCRED-Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "myCred for BuddyPress Compliments",
                    "desc": "Let BuddyPress users send each other compliments or eGifts"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "One Click Mark Spammer",
                    "desc": "Let site administrators mark users as a spammer with a single click "
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/Restrict-Content-Pro-Add-ons.png",
                    "int_type": "Compatible",
                    "title": "Restrict Content Pro - BuddyPress Integration",
                    "desc": "Connect BuddyPress with Restrict Content Pro"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "Restrictions for BuddyPress",
                    "desc": "BuddyPress area restrictions WordPress plugin"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2022/04/Smart-User-Slug-Hider.png",
                    "int_type": "Compatible",
                    "title": "Smart User Slug Hider",
                    "desc": "Enhance the security of your BuddyBoss site by hiding usernames in the User Profile URLs of BuddyBoss members."
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "Social Articles",
                    "desc": "Let your users create posts directly from their BuddyPress profiles"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "Verified Member for BuddyPress",
                    "desc": "Display a twitter-like ‘verified’ badge on a user's profile"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/01/WC4BP-WooCommerce-BuddyPress-Integration.png",
                    "int_type": "Compatible",
                    "title": "WC4BP - WooCommerce BuddyPress Integration",
                    "desc": "Integrate your WooCommerce store with BuddyPress community"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/05/WishList-Member-BuddyBoss-Integration-Copy.png",
                    "int_type": "Third-party",
                    "title": "WishList Member + BuddyBoss Platform Integration",
                    "desc": "Connect BuddyBoss Platform with WishList Member"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/WP-Fusion-BuddyPress-Integration.png",
                    "int_type": "Third-party",
                    "title": "WP Fusion + BuddyPress / BuddyBoss",
                    "desc": "Send new BuddyPress users to your connected CRM"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/03/WP-Project-Manager.png",
                    "int_type": "Compatible",
                    "title": "WP Project Manager - BuddyPress Integration",
                    "desc": "WordPress Project Management Plugin"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "WP ULike",
                    "desc": "Allow your visitors to like and unlike pages, posts, comments, bbPress & BuddyPress activities"
                },
                {
                    "type": "title",
                    "text": "CRM"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/WP-Fusion.png",
                    "int_type": "Compatible",
                    "title": "WP Fusion",
                    "desc": "Synchronize your WordPress users with leading CRMs and marketing automation systems"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/WP-Fusion-BuddyPress-Integration.png",
                    "int_type": "Third-party",
                    "title": "WP Fusion + BuddyBoss App",
                    "desc": "WP fusion and BuddyBoss App allows you to customize in-app purchases and push notifications based on tags"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/WP-Fusion-Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "WP Fusion Add-ons",
                    "desc": "Support for almost all WP Fusion Add-ons"
                },
                {
                    "type": "title",
                    "text": "Custom Login"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/01/LoginPress.png",
                    "int_type": "Compatible",
                    "title": "LoginPress",
                    "desc": "Tranform your boring wp-login.php login page into a beautiful customized login experience"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/01/LoginPress_AddOns.png",
                    "int_type": "Compatible",
                    "title": "LoginPress Add-ons",
                    "desc": "Support for almost all LoginPress Add-ons"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/03/WordPress-Social-Login.png",
                    "int_type": "Compatible",
                    "title": "WordPress Social Login, Social Sharing",
                    "desc": "Let your visitors login, comment, share and optionally auto-register from their favorite social login apps"
                },
                {
                    "type": "title",
                    "text": "Custom Redirect"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/03/Coming-Soon-Page-Under-Construction-Maintenance-Mode.png",
                    "int_type": "Compatible",
                    "title": "Coming Soon Page, Under Construction & Maintenance Mode",
                    "desc": "Create simple Coming Soon Page, Under Construction or Maintenance Mode Page"
                },
                {
                    "type": "title",
                    "text": "Dynamic Content"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/11/If-So.png",
                    "int_type": "Compatible",
                    "title": "If-So",
                    "desc": "Dynamic content WordPress plugin"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/11/If-So-Add-ons.png",
                    "int_type": "Compatible",
                    "title": "If-So Add-ons",
                    "desc": "Support for almost all If-So Add-ons "
                },
                {
                    "type": "title",
                    "text": "eCommerce"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2022/01/WC-Vendors-Pro.png",
                    "int_type": "Third-party",
                    "title": "WC Vendors Pro",
                    "desc": "Create a multivendor marketplace and earn commission from every sale"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/WooCommerce.png",
                    "int_type": "Official",
                    "title": "WooCommerce",
                    "desc": "Open-source eCommerce plugin for WordPress"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/WooCommerce-Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "WooCommerce Add-ons",
                    "desc": "Support for almost all WooCommerce Add-ons"
                },
                {
                    "type": "title",
                    "text": "Emails"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/02/Email-Subscribers-Newsletters.png",
                    "int_type": "Compatible",
                    "title": "Email Subscribers & Newsletters",
                    "desc": "A simple and effective newsletter system"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2021/12/FluentCRM.png",
                    "int_type": "Third-party",
                    "title": "FluentCRM",
                    "desc": "With the BuddyBoss and FluentCRM integration, turn your online community members into your email subscribers and start email marketing automation for your online community."
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/07/MailPoet.png",
                    "int_type": "Compatible",
                    "title": "MailPoet",
                    "desc": "Emails and newsletters plugin for WordPress"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/02/Newsletter.png",
                    "int_type": "Compatible",
                    "title": "Newsletter",
                    "desc": "A newsletter and email marketing system"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/03/SOCIAL-SUBSCRIBE-BOX.png",
                    "int_type": "Compatible",
                    "title": "Social Subscribe Box",
                    "desc": "Let your users subscribe to your MailChimp newsletter"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/05/Thrive-Leads.png",
                    "int_type": "Compatible",
                    "title": "Thrive Leads",
                    "desc": "All-in-one list-building solution"
                },
                {
                    "type": "title",
                    "text": "Events"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/mec-logo-icon.png",
                    "int_type": "Compatible",
                    "title": "Modern Events Calendar",
                    "desc": "Responsive, mobile-friendly, and comprehensive events management plugin"
                },
                {
                    "type": "title",
                    "text": "Forms"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyForms_Members.png",
                    "int_type": "Compatible",
                    "title": "BuddyForms",
                    "desc": "Contact, Registration, Post form builder & frontend editor"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/Contact-Form-7.png",
                    "int_type": "Compatible",
                    "title": "Contact Form 7",
                    "desc": "WordPress plugin for creating lead generating forms"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/GravityForms.png",
                    "int_type": "Compatible",
                    "title": "Gravity Forms",
                    "desc": "Premium Form Builder Plugin "
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/GravityForms_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "Gravity Forms Add-ons",
                    "desc": "Support for almost all Gravity Forms Add-ons"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/Ninja-Forms.png",
                    "int_type": "Compatible",
                    "title": "Ninja Forms",
                    "desc": "Easy and powerful form builder plugin "
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/Ninja-Forms-Add-ons.png",
                    "int_type": "Compatible",
                    "title": "Ninja Forms Add-ons",
                    "desc": "Support for almost all Ninja Forms Add-ons"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/WP-Fluent-Forms.png",
                    "int_type": "Compatible",
                    "title": "WP Fluent Forms",
                    "desc": "Customizable drag-and-drop WordPress contact form plugin"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/WPForms.png",
                    "int_type": "Compatible",
                    "title": "WPForms",
                    "desc": "Drag-and-drop WordPress form builder plugin "
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/WPForms-Add-ons.png",
                    "int_type": "Compatible",
                    "title": "WPForms Add-ons",
                    "desc": "Support for almost all WPForms Add-ons"
                },
                {
                    "type": "title",
                    "text": "Gamification"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/GamiPress.png",
                    "int_type": "Official",
                    "title": "GamiPress",
                    "desc": "Gamification solution for your WordPress site"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/10/GamiPress-BuddyBoss-Integration.png",
                    "int_type": "Third-party",
                    "title": "GamiPress + BuddyBoss Integration",
                    "desc": "Gamify your BuddyBoss community website"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/02/GamiPress-LifterLMS-Integration.png",
                    "int_type": "Compatible",
                    "title": "GamiPress + LifterLMS Integration",
                    "desc": "Gamify your LifterLMS-powered online courses website"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/GamiPress-Add-Ons.png",
                    "int_type": "Official",
                    "title": "GamiPress Add-ons",
                    "desc": "Support for almost all GamiPress Add-ons"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/GamiPress-Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "GamiPress Leaderboards",
                    "desc": "Easily create, configure and add leaderboards on your website"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/myCRED.png",
                    "int_type": "Compatible",
                    "title": "myCRED",
                    "desc": "Points management system for your WordPress site"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/myCRED-Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "myCRED Add-ons",
                    "desc": "Support for almost all myCRED Add-ons"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/Rating_Widget.png",
                    "int_type": "Compatible",
                    "title": "RatingWidget: Star Review System",
                    "desc": "Popular, GDPR compliant, Five Star Review System"
                },
                {
                    "type": "title",
                    "text": "Job Listings"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/WP_Job_Manager.png",
                    "int_type": "Official",
                    "title": "WP Job Manager",
                    "desc": "WordPress Job Listings Plugin"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/WP_Job_Manager_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "WP Job Manager Add-ons",
                    "desc": "Support for almost all WP Job Manager Add-ons"
                },
                {
                    "type": "title",
                    "text": "Listings"
                },
                {
                    "type": "item",
                    "int_type": "Third-party",
                    "title": "Directorist",
                    "desc": "Bring a modern directory to your online community"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/05/GeoDirectory-1.jpg",
                    "int_type": "Compatible",
                    "title": "GeoDirectory",
                    "desc": "A lightweight yet rocket-fast business directory WordPress plugin"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/05/GeoDirectory-Add-ons.png",
                    "int_type": "Compatible",
                    "title": "GeoDirectory Add-ons",
                    "desc": "Support for almost all GeoDirectory Add-ons"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2022/12/Spaces-Engine-624x624.png",
                    "int_type": "Third-party",
                    "title": "Spaces Engine",
                    "desc": "All-in-One Directory Solution For Your BuddyBoss Community"
                },
                {
                    "type": "title",
                    "text": "Live Streaming"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2023/04/WPStream-624x351.png",
                    "int_type": "Third-party",
                    "title": "WP Stream",
                    "desc": "Video Streaming for WordPress"
                },
                {
                    "type": "title",
                    "text": "LMS"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/01/LearnDash_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "Content Cloner",
                    "desc": "Clone LearnDash courses with a click of a button"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/LearnDash_Add-Ons.png",
                    "int_type": "Official",
                    "title": "LearnDash Course Grid",
                    "desc": "Customizable course grids for LearnDash"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/LearnDash.png",
                    "int_type": "Official",
                    "title": "LearnDash LMS",
                    "desc": "The go-to choice for people creating (and selling) online courses."
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/LearnDash_Add-Ons.png",
                    "int_type": "Official",
                    "title": "LearnDash LMS Add-ons",
                    "desc": "Support for almost all LearnDash Add-ons"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/01/LearnDash_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "LearnDash Notes",
                    "desc": "On-site note taking system"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/01/LearnDash_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "LearnDash Private Sessions",
                    "desc": "Provide personalized coaching to LearnDash students"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/02/LifterLMS.png",
                    "int_type": "Official",
                    "title": "LifterLMS",
                    "desc": "A powerful WordPress LMS software for Experts, Coaches & Entrepreneurs"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/02/LifterLMS_AddOns.png",
                    "int_type": "Compatible",
                    "title": "LifterLMS Add-ons",
                    "desc": "Support for almost all LifterLMS Add-ons"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/01/LearnDash_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "Multiple Instructors for LearnDash",
                    "desc": "Allow users to create their own LearnDash courses"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/LearnDash.png",
                    "int_type": "Compatible",
                    "title": "ProPanel by LearnDash",
                    "desc": "Manage your LearnDash activity"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/06/Tutor-LMS.jpg",
                    "int_type": "Official",
                    "title": "Tutor LMS",
                    "desc": "A lightweight, robust WordPress LMS plugin"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/01/LearnDash_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "Uncanny LearnDash Toolkit",
                    "desc": "Build better LearnDash sites"
                },
                {
                    "type": "title",
                    "text": "Marketing"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/08/Groundhogg.png",
                    "int_type": "Third-party",
                    "title": "Groundhogg",
                    "desc": "A freemium marketing automation WordPress plugin"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/08/Groundhogg-Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "Groundhogg Add-ons",
                    "desc": "Support for almost all Groundhogg Add-ons"
                },
                {
                    "type": "title",
                    "text": "Media Gallery"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/MediaPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "MediaPress Downloadable Media",
                    "desc": "Let visitors/users download any media file"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/MediaPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "MediaPress Upload Terms of Service",
                    "desc": "Configure terms of service agreement for uploading media on your site"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2022/04/wp-offload-media-1.png",
                    "int_type": "Compatible",
                    "title": "WP Offload Media",
                    "desc": "With the BuddyBoss and WP Offload Media integration, you can automatically offload user-generated content uploaded by BuddyBoss users."
                },
                {
                    "type": "title",
                    "text": "Membership Plugins"
                },
                {
                    "type": "item",
                    "int_type": "Third-party",
                    "title": "Digital Access Pass",
                    "desc": "BuddyBoss and Digital Access Pass (DAP) integration ensures you can create an advanced Membership & Community Site."
                },
                {
                    "type": "item",
                    "int_type": "Third-party",
                    "title": "Memberium",
                    "desc": "A premium membership plugin that connects your WordPress site to Keap and ActiveCampaign"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/MemberPress.png",
                    "int_type": "Official",
                    "title": "MemberPress",
                    "desc": "The “All-In-One” Membership Plugin for WordPress"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/MemberPress_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "MemberPress Add-ons",
                    "desc": "Support for almost all MemberPress Add-ons"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/Paid_Memberships_Pro.png",
                    "int_type": "Compatible",
                    "title": "Paid Memberships Pro",
                    "desc": "A complete membership solution WordPress site"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/Paid_Memberships_Pro_Add-Ons.png",
                    "int_type": "Compatible",
                    "title": "Paid Memberships Pro Add-ons",
                    "desc": "Support for almost all Paid Memberships Pro add-ons"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/Restrict-Content-Pro.png",
                    "int_type": "Third-party",
                    "title": "Restrict Content",
                    "desc": "A full-featured, powerful membership solution for WordPress"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/Restrict-Content-Pro-Add-ons.png",
                    "int_type": "Compatible",
                    "title": "Restrict Content Pro Add-Ons",
                    "desc": "Support for almost all Restrict Content Pro add-ons"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/05/WishList-Member.png",
                    "int_type": "Third-party",
                    "title": "WishList Member",
                    "desc": "Premium membership software solution"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/05/WishList-Member-Add-ons.png",
                    "int_type": "Compatible",
                    "title": "WishList Member Add-ons",
                    "desc": "Support for almost all WishList Member Add-ons"
                },
                {
                    "type": "title",
                    "text": "Page Builder"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/Element-Pack.png",
                    "int_type": "Compatible",
                    "title": "Element Pack for Elementor",
                    "desc": "An essential add-on for Elementor Page Builder"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/Elementor.png",
                    "int_type": "Official",
                    "title": "Elementor Page Builder",
                    "desc": "World’s leading WordPress page builder"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/05/Thrive-Architect.png",
                    "int_type": "Compatible",
                    "title": "Thrive Architect",
                    "desc": "Fastest and most intuitive visual editor for WordPress"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/WPBakery_Page_Builder.png",
                    "int_type": "Compatible",
                    "title": "WPBakery Page Builder",
                    "desc": "Drag-and-drop frontend and backend editor"
                },
                {
                    "type": "title",
                    "text": "Polls"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/07/AnsPress.png",
                    "int_type": "Compatible",
                    "title": "AnsPress",
                    "desc": "A developer friendly, question and answer plugin for WordPress"
                },
                {
                    "type": "title",
                    "text": "Popup Builder"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/Popup-Builder.png",
                    "int_type": "Compatible",
                    "title": "Popup Builder",
                    "desc": "Create and manage unlimited promotion modal popups"
                },
                {
                    "type": "title",
                    "text": "Project Management"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/03/WP-Project-Manager.png",
                    "int_type": "Compatible",
                    "title": "WP Project Manager",
                    "desc": "WordPress Project Management Plugin"
                },
                {
                    "type": "title",
                    "text": "SEO"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/text-logo.svg",
                    "int_type": "Compatible",
                    "title": "All in One SEO",
                    "desc": "Optimize your WordPress site for SEO"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/01/Rank_Math_SEO.png",
                    "int_type": "Compatible",
                    "title": "Rank Math SEO",
                    "desc": "A ground-breaking, free SEO plugin"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/logo-square_purple.svg",
                    "int_type": "Compatible",
                    "title": "SEOPress",
                    "desc": "A powerful WordPress SEO plugin"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/Yoast-SEO.png",
                    "int_type": "Compatible",
                    "title": "Yoast SEO",
                    "desc": "The favorite WordPress SEO plugin of millions of users worldwide"
                },
                {
                    "type": "title",
                    "text": "Social"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/Social-Media-Share-Buttons-Social-Sharing-Icons.png",
                    "int_type": "Compatible",
                    "title": "Social Media Share Buttons & Social Sharing Icons",
                    "desc": "Add share icons for RSS, email, social media platforms and custom social buttons to your website"
                },
                {
                    "type": "title",
                    "text": "Support Ticketing"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2021/12/Fluent-Support.png",
                    "int_type": "Third-party",
                    "title": "Fluent Support",
                    "desc": "A self-hosted support ticketing system with unlimited tickets, support agents, users, products, tags, and channels"
                },
                {
                    "type": "title",
                    "text": "Translation"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/Loco-Translate.png",
                    "int_type": "Compatible",
                    "title": "Loco Translate",
                    "desc": "In-browser editing of WordPress translation files"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/TranslatePress-Multilingual.png",
                    "int_type": "Compatible",
                    "title": "TranslatePress - Multilingual",
                    "desc": "Translate your WordPress site directly from the front-end"
                },
                {
                    "type": "item",
                    "logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/01/WPML.jpg",
                    "int_type": "Third-party",
                    "title": "WPML",
                    "desc": "Powerful enough for corporate sites, yet simple for blogs, easily build multilingual sites"
                }
            ],
            searchQuery: null,
            category: 'all',
            searchType: 'all',
            page: 1,
        }

        function render( renderOptions ) {
            // Search Query
            if(renderOptions.searchQuery !== null) {
                renderOptions.data = defaultOptions.data.filter(function(item) {
                    return item.title.toLowerCase().includes(renderOptions.searchQuery.toLowerCase());
                });
            }
            
            // Integration Type
            if(renderOptions.searchType !== 'all') {
                renderOptions.data = defaultOptions.data.filter(function(item) {
                    return item.int_type === renderOptions.searchType;
                });
            }

            // Pagination
            var itemsPerPage = 30;
            var currentPage = renderOptions.page;
            var startIndex = (currentPage - 1) * itemsPerPage;
            var endIndex = startIndex + itemsPerPage;
            // Get items for the current page
            var itemsToDisplay = renderOptions.data.slice(startIndex, endIndex);
            renderOptions.data = itemsToDisplay;

            // Link Preview Template
            var tmpl = $('#tmpl-bb-integrations').html();

            // Compile the template
            var compiled = _.template(tmpl);

            var html = compiled( renderOptions );

            if( renderOptions.previewParent ) {
                renderOptions.previewParent.html( html );
            }
        }

        render( defaultOptions );

        $( 'input[name="integrations_collection"]' ).on( 'change', function(e) {
            var int_type = $(e.currentTarget).val();
            Object.assign( defaultOptions, { searchType: int_type, page: 1 } );
            render( defaultOptions );
        });

        $( 'input[name="search_integrations"]' ).on( 'keyup', function(e) {
            var query = $(e.currentTarget).val();
            Object.assign( defaultOptions, { searchQuery: query, page: 1 } );
            render( defaultOptions );
        });
    }

    renderIntegrations();

}());