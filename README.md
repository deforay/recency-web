# Recency Web App


#### How do I get set up?
* Download the Source Code and put it into your server's root folder (www or htdocs).
* Create a database and load the schema: `mysql recency_web < sql/schema.sql`
* Copy `config/autoload/local.php.dist` to `config/autoload/local.php` and set the database parameters
* Copy `config/autoload/custom.local.php.dist` to `config/autoload/custom.local.php` and set this install's URLs, email account, InteLIS API token and cross-login settings
* Git ignores both copies. Never put a real password, salt or token in a tracked file: this repository is public
* Run `composer install`, then `composer migrate` to apply the schema changes made since `sql/schema.sql`
* Ensure that the apache rewrite module is enabled
* Create a virtual host pointing to the public folder of the source code. You can see an example below : 

```
<VirtualHost *:80>
   DocumentRoot "C:\wamp\www\recency-web\public"
   ServerName recency

   <Directory "C:\wamp\www\recency-web\public">
       Options Indexes MultiViews FollowSymLinks
       AllowOverride All
       Order allow,deny
       Allow from all
   </Directory>
</VirtualHost>
```

#### Next Steps
* Please add the Province, Districts, Cities manually in the database. In the near future, we will add UI to add these
* Once you have the application set up, you can visit the recency URL http://recency/ and log in with the credentials recencyadmin@example.com and 12345
* Now you can start adding Users, facilities and set up global config.

#### Who do I talk to?
You can reach us at amit (at) deforay (dot) com


