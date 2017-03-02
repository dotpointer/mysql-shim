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
# 2017-02-27 01:47:00 TR - First version
# 2017-03-02 20:35:08 RK - Small edit of comments format, rename output file, turning returns to spaces
# 2017-03-02 22:19:00 TR - Bugfix, space before and after php tag

# Regular Expressions to minify
$patterns = array(
	'/^.*#(.*)$/Um', # Comments (does not remove /* */ because you don’t use them)
	'/\n/Um', # LineFeeds
	'/\r/Um', # Returns
	'/^\s+|\s+$|\s+(?=\s)/Um', # WhiteSpace
	'/(<\?php)/Umi', # Add space after <? php
	'/(\?>)/Umi', # Add space before ? >
	'/\t/Um'  # Tabs
);

# Replacements
$replacements = array(
	'', # No Comments
	'', # No LineFeeds
	'', # No Returns
	'', # No WhiteSpace
	'${1} ',# Add space after <? php
	' ${1}',# Add space before ? >
	'' # No Tabs
);

# Load soruce code
$code = file_get_contents('mysql-shim.php');

# Minify
$code = preg_replace( $patterns, $replacements, $code );

# Wrtie Minified code
file_put_contents( 'mysql-shim.min.php', $code );
?>
