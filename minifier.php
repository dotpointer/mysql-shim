<?php

# PHP Minify Robert Klebe’s MySQLi migration shim library
# ------------------------------------------
# purpose: Remove #Comments, Line Feeds, Returns, Whate Space & Tabs
#
# author: Tony Russo
#
# licensing: Public domain, edit and share without permission.
#
# Loads ‘mysql-shim.php’
# Writes minified code to ‘mysql-shim-min.php’

# Changelog
# 2017-02-27 01:47:00 tr - First version 
# 2017-03-02 20:35:08 rk - Small edit of comments format, rename output file, turning returns to spaces

# Regular Expressions to minify
$patterns = array(
	'/^.*#(.*)$/Um', # Comments (does not remove /* */ because you don’t use them)
	'/\n/Um', # LineFeeds
	'/\r/Um', # Returns
	'/^\s+|\s+$|\s+(?=\s)/Um', # WhiteSpace
	'/\t/Um'  # Tabs
);

# Replacements
$replacements = array(
	'', # No Comments
	'', # No LineFeeds
	' ', # No Returns
	'', # No WhiteSpace
	''  # No Tabs
);

# Load soruce code
$code = file_get_contents('mysql-shim.php');

# Minify
$code = preg_replace( $patterns, $replacements, $code );

# Wrtie Minified code
file_put_contents( 'mysql-shim.min.php', $code );
?>
