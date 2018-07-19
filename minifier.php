<?php
# PHP MySQL to MySQLi migration shim library minifier
# ---------------------------------------------------
#
# purpose: remove # comments, line feeds, returns, white space and tabs
# loads 'mysql-shim.php', writes minified code to 'mysql-shim-min.php'
#
# author/editor: Robert Klebe, dotpointer
# initial author: Tony Russo
#
# licensing: see LICENSE file
#
# changelog
# 2018-06-06 19:40:00 - reformatting and editing license
# 2018-07-19 19:41:19 - indentation change, tab to 2 spaces

# regular expressions to minify
$patterns = array(
  '/^.*#(.*)$/Um',			# comments (does not remove /* */ because not used)
  '/\n/Um',					# line feeds
  '/\r/Um',					# returns
  '/^\s+|\s+$|\s+(?=\s)/Um',	# whitespace
  '/(<\?php)/Umi',			/* add space after <?php */
  '/(\?>)/Umi',				/* add space before ?>   */
  '/\t/Um'					# tabs
);

# replacements
$replacements = array(
  '',			# no comments
  '',			# no line feeds
  '',			# no returns
  '',			# no whitespace
  '${1} ',	/* add space after <?php */
  ' ${1}',	/* add space before ?>   */
  ''			# no tabs
);

# load source code
$code = file_get_contents('mysql-shim.php');

# minify
$code = preg_replace($patterns, $replacements, $code);

# write minified code
file_put_contents('mysql-shim.min.php', $code);
?>
