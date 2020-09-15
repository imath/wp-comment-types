# WP Comment Types

This plugin is a WIP about implementing WordPress Comment Types. It's a follow up of the WordPress Core trac ticket [#35214](https://core.trac.wordpress.org/ticket/35214).

## Plugin organization

To make it easier to eventually move progressively classes or functions from this plugin to WordPress Core, the plugin is organized like WordPress where directories and files have the same names:

- `/wp-admin`
	- `/includes`
  	- admin pages
- `/wp-includes`
	- function & class files.

Into the `/inc` directory there are some hooks/functions to adapt WordPress behavior to plugin's needs and an example of comment type to test the registration functions and the edits that we need to do in Admin screens and into the front-end.

## Questions? You want to help make it happen?

Don't hesitate to join the [#core-comments](https://wordpress.slack.com/messages/core-comments) w.org slack's channel to discuss about WP Comment Types.
