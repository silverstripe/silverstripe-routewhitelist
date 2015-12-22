# Route Whitelist
Provides a whitelist of known valid URL patterns in a SilverStripe website.

## Overview
This modules take the approach that while routing is a difficult problem, there is a subset of the routing problem that is 
quite easy to solve. So, while is a very difficult computation involving multiple database queries to figure out if a 
given URL is invalid and should result in a 404, or is valid and should be routed to a certain controller, it is much
easier to figure out that a given URL is definitely invalid and will under no circumstances result in a valid response.
This module does exactly that. It examines the first segment of a URL and very quickly returns a 404 response, if that 
first segment doesn't match any known controller, route, or top-level page.
  
## Requirements
The Route Whitelist relies on the Apache's htaccess system to compare the first segment to the URL. Nginx is currently 
not supported.

Route Whitelist also only works with SilverStripe installations in the domain route. So, a site running at: "myawesomewebsite.com" works
and "localhost:81" works, but "localhost/myawesomewebsite" doesn't work.

## Installation
To install this module run:

	composer require silverstripe/route-whitelist:*

To configure the module replace the standard SilverStripe .htaccess file with the file in routewhitelist/extra/htaccess. You can do this
 by running the following commands:

	cd myawesomewebsite
	cp routewhitelist/extra/htaccess .htaccess

Then run a ?flush=all to generate the whitelist. Don't worry, the module doesn't take effect until you generate the whitelist.

If you have modified the standard SilverStripe .htaccess file, insert the following snippet into your modified file 
at an appropriate location near the top of the mod_rewrite rules:

    #routewhitelist: send known invalid URLs straight to the 404 error page
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{DOCUMENT_ROOT}/routewhitelistcache/.htaccess -f
    RewriteCond %{REQUEST_URI} ^\/(.+?)(\/.*|\s*)$
    RewriteCond %{DOCUMENT_ROOT}/routewhitelistcache/%1 !-f 
	RewriteRule  .* assets/error-404.html [L,R=404]

