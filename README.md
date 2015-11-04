# Route Whitelist

Provides a whitelist of known valid URL patterns.

This modules take the approach that while routing is a difficult problem, there is a subset of the routing problem that is 
quite easy to solve. So, while is a very difficult computation involving multiple database queries to figure out if a 
given URL is invalid and should result in a 404, or is valid and should be routed to a certain controller, it is much
easier to figure out that a given URL is definitely invalid and will under no circumstances result in a valid response.
This module does exactly that. It examines the first segment of a URL and very quickly returns a 404 response, if that 
first segment doesn't match any known controller, route, or top-level page.
  
## Requirements
The Route Whitelist relies on the Apache's htaccess system to compare the first segment fo the URL. Nginx is currently 
not supported.

## Installation
To install the module copy it into your SilverStripe folder, then replace the standard SilverStripe .htaccess file 
with the file in routewhitelist/extra/htaccess and run a ?flush=all.
 
If you have modifed the standard SilverStripe .htaccess file, insert the following snippet into your modified file 
at an appropriate location:

    # Route Whitelist: send known invalid URLs straight to the 404 error page
    RewriteCond %{REQUEST_URI} !^/.?$
    RewriteCond %{REQUEST_URI} ^/(.*?)/.*$
    RewriteCond %{DOCUMENT_ROOT}/routewhitelistcache/%1 !-f [NC]
	RewriteRule  .* /assets/error-404.html [L]
	
