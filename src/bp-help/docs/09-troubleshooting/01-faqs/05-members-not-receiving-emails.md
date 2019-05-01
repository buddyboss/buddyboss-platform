#Members Not Receiving Emails

Certain hosting providers do not play nicely with the WordPress mail functions. There have been several plugins developed to workaround certain hosting providers but many have been abandoned. At the time of writing, we think this is the best solution:

[WP Mail SMTP](https://wordpress.org/plugins/wp-mail-smtp/) - fixes your email deliverability by reconfiguring the wp\_mail() PHP function to use a proper SMTP provider.