#Web Hosting

When hosting BuddyBoss Platform, you need to have some special considerations as it is a highly dynamic web application. It is not like a blog website where you can easily cache all the pages. On a social network, your users are logging in throughout the day and posting content, meaning the page content needs to always remain fresh. You should not be using page caching plugins such as W3 Total Cache, as your users will get stale content. For this reason it is important to invest in a proper server setup that can handle all of the database queries from the application.

I am going to list some options, starting with the less expensive options, and then moving up to the more expensive/complex options which are more suitable to enterprise/high traffic sites.

Keep in mind that there are a lot of hosting solutions available, and many people will have their own opinions on this. This article is based on our own experience with our sites and our client’s websites, and what has worked for us in the past.

##KnownHost – VPS

###https://www.knownhost.com/managed-vps.html

We have had great experiences with KnownHost. They offer a lot of value for the money, with very high specced machines and excellent customer support, for a price equal or less than competitors for the same hardware. Their support staff are all server admins who can investigate your site for speed issues and implement improvements at the server end for you. And they have a lot of higher tier options, so they are a solution you can grow with over time. I would suggest starting with the second VPS “Standard” tier on the link above. A VPS is a Virtual Private Server, which is just one step below having your own dedicated server. If the site is still having performance issues they can easily bump you up to the next VPS tier if needed. 

Make sure to ask KnownHost to increase the maximum number of PHP parallel processes, which will improve speed substantially. You can send them [this article](https://www.kinamo.be/en/support/faq/determining-the-correct-number-of-child-processes-for-php-fpm-on-nginx) for reference.

##KnownHost – Dedicated Server

###https://www.knownhost.com/dedicated-servers.html

If you want even higher performance than the VPS options, you can choose to bump to KnownHost’s dedicated servers. This will provide an entire server just to your web application, meaning you are not sharing any resources with any other sites. Any of the dedicated server options should be adequate for most sites, as they are offering extremely powerful hardware. And just like the VPS options, their support staff will help you with server questions and optimizations.

Make sure to ask KnownHost to increase the maximum number of PHP parallel processes, which will improve speed substantially. You can send them [this article](https://www.kinamo.be/en/support/faq/determining-the-correct-number-of-child-processes-for-php-fpm-on-nginx) for reference.

##AWS (Amazon)

###https://aws.amazon.com/

If you anticipate that your site will have extremely high traffic, you might choose to set it up on AWS. This allows for nearly unlimited expansion of resources and will provide the best performance of all the options in this article (if configured properly). Huge enterprise applications such as Uber are on AWS, so for sure it can handle your website.

However this is not a good solution for a small company or individual. AWS is very complex and does not provide managed support, so you really need to have a server admin on your staff to properly take advantage of this. Additionally, this is the most expensive of the options. You could easily spend $500/month or more. It is best suited for larger companies and enterprise clients who can invest in a server admin to implement and manage the AWS setup.  

Our own [public demos](https://demos.buddyboss.com/online-communities/) are hosted on AWS. Every time you view one of our product demos, a fresh database is created just for you. Meaning at any moment, potentially hundreds of instances of our demos are being loaded at the same time, and they still load quickly. So yes, AWS is very powerful and expandable when configured properly.

I would recommend starting with a single EC2 (the server) from the [M4 class](https://aws.amazon.com/about-aws/whats-new/2015/06/introducing-m4-instances-and-lower-amazon-ec2-instance-prices/).

These are very good servers for hosting WordPress sites. If you outgrow it, just bump to the next M4 tier. You can purchase [Reserved Pricing](https://aws.amazon.com/ec2/pricing/reserved-instances/pricing/) which will reduce your costs by 30-60% yearly.

Make sure to ask your server admin to increase the maximum number of PHP parallel processes, which will improve speed substantially. You can send them [this article](https://www.kinamo.be/en/support/faq/determining-the-correct-number-of-child-processes-for-php-fpm-on-nginx) for reference.
