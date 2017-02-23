<?php
	# test file for mysql shim library
	# by robert klebe, dotpointer

	# changelog
	# 2016-02-24 15:27:05 - first version
	# 2016-12-26 15:08:00 - renaming file from mysql.test.php to
	#	mysql-shim.test.php
	# 2017-02-20 18:06:00 - bugfix, renaming inclusion of library file
	# 2017-02-20 20:10:00 - bugfix, invalid connection credentials
	#	resulted in error, printing help on connect error, thanks to Tony Russo
	#	for finding it
	# 2017-02-20 22:19:21 - adding tests for mysql_query,
	#	mysql_unbuffered_query, mysql_num_rows, mysql_pconnect etc.
	# 2017-02-21 18:56:20 - rewriting error handling
	# 2017-02-22 00:52:08 - Making it possible to test native PHP functions by
	#	request from Tony Russo and to exclude the shim from the test. Adding
	#	checks for function existence in test and correcting return value
	#	validations to native PHP 5.6.3 function return values.
	# 2017-02-22 20:43:16 - skipping but reporting functions that does not exist
	# 2017-02-23 22:31:21 - adding constants check, editing parameters

	# functions required to run the test:
	# mysql_query, mysql_error, mysql_connect


$sqllog = array();
$skipped = 0;
$missing_constants = 0;

if(defined('E_DEPRECATED')) {
    error_reporting(E_ALL &~ E_DEPRECATED);
}

# a logging query function
function db_query($link, $line, $sql) {
	global $sqllog;

	# add to log
	$sqllog[] = $sql;

	# try to run it
	$r = mysql_query($sql, $link);

	# did it fail?
	if ($r === false) {
		error($line, mysql_error($link));
	}

	return $r;
}

function error($line, $error) {
	# get sql log
	global $sqllog;

	# print error
	echo "\n".'ERROR!'."\n";
	echo 'Line: '.$line."\n";
	echo 'Error: '.$error."\n";
	echo 'SQL log: '."\n";
	foreach ($sqllog as $line) {
		echo $line."\n";
	}
	die(1);
}

function version_notice($function, $requiredbytest) {
	global $skipped;
	$skipped += 1;
	echo '! SKIPPING '.$function.', does not exist, will not be tested.'."\n";

	# make sure it is not required by the test
	if ($requiredbytest) {
		echo 'Fatal error! This function is required by the test, cannot continue.'."\n";
		die(1);
	}
	return true;
}

# to print help
function print_help() {
	echo 'The following parameters can be specified: '."\n";
	echo '	-d <database> (optional, defaults to testdatabase12345, to be created and deleted)'."\n";
	echo '	-f to drop database if it exists'."\n";
	echo '	-h <hostname> (optional, defaults to localhost)'."\n";
	echo '	-H or --help show this help'."\n";
	echo '	-p <password> (optional, defaults to an empty string)'."\n";
	echo '	-u <username> (optional, defaults to root)'."\n";
	echo '	-y to continue without confirmation'."\n";
	echo '	-i to skip including shim library - tests native PHP MySQL functions if available'."\n";
	return true;
}

# get parameters
$opt = getopt('fyh:u:p:d:HiI', array('help'));

# get parameters
$confirmed	= isset($opt['y']);
$force		= isset($opt['f']);
$database 	= isset($opt['d']) && strlen($opt['d']) ? $opt['d'] : 'testdatabase12345';;
$help		= isset($opt['H']) || isset($opt['help']);
$host 		= isset($opt['h']) && strlen($opt['h']) ? $opt['h'] : 'localhost';
$includeskip= isset($opt['i']);
$password	= isset($opt['p']) ? $opt['p'] : '';
$username	= isset($opt['u']) && strlen($opt['u']) ? $opt['u'] : 'root';

# is help requested?
if ($help) {
	# print help
	print_help();
	die(0);
}

# print intro
echo 'Test of PHP MySQL to MySQLi migration shim library'."\n";
echo 'Uname: '.php_uname()."\n";
echo 'PHP version: '.phpversion()."\n";

# check if the shim exists
$shim_exists = file_exists('mysql-shim.php');
echo 'Shim library present in testing directory: '.($shim_exists ? 'Yes' : 'No')."\n";

# include the file to test
echo 'Including shim library: '.(!$includeskip ? 'Yes (use -i to skip)' : 'No')."\n";
if (!$includeskip) {
	require_once('mysql-shim.php');
}

# make sure any extension is loaded
echo 'MySQL extension loaded : '.(extension_loaded('mysql') ? 'Yes' : 'No')."\n";
echo 'MySQLi extension loaded: '.(extension_loaded('mysqli') ? 'Yes' : 'No')."\n";

if (!extension_loaded('mysql') && !extension_loaded('mysqli')) {
	echo 'Fatal error! Neither MySQL nor MySQLi (preferred) extensions are loaded.'."\n";
	echo 'Cannot continue without one of these loaded.'."\n";
	die(1);
}

# confirm user wants to continue, if not override is specified
if (!$confirmed && extension_loaded('mysql') && !$includeskip) {
	# make sure user wants to do this
	echo "\n".'WARNING! The (original?) MySQL extension seems to be loaded.'."\n";
	echo 'This will NOT test the library functions, but the native PHP MySQL extension functions.'."\n";
	echo 'Also consider using -i which does not include the library at all.'."\n";
	echo 'Are you sure you want to do this?  Type "y" to continue: ';
	$handle = fopen ("php://stdin","r");
	$line = fgets($handle);
	if(trim($line) != 'y'){
		echo 'Testing aborted'."\n";
		die(1);
	}
	fclose($handle);
	echo "\n";
}

# print host info
echo 'Host: '.$host."\n";
echo 'Username: '.$username."\n";
echo 'Password: '.str_repeat('*', strlen($password))."\n";
echo 'Database: '.$database."\n";

# confirm user wants to continue, if not override is specified
if (!$confirmed) {
	# make sure user wants to do this
	echo "\n".'WARNING! The database "'.$database.'" will be used for testing.'."\n";
	echo 'Data stored in the database and the database itself WILL be lost.'."\n\n";
	echo 'Are you sure you want to do this?  Type "y" to continue: ';
	$handle = fopen ("php://stdin","r");
	$line = fgets($handle);
	if(trim($line) != 'y'){
		echo 'Testing aborted'."\n";
		die(1);
	}
	fclose($handle);
	echo "\n";
}

$time_start = microtime(true);
echo 'Test started '.date('Y-m-d H:i:s')."\n";

# --- check constants
foreach (array(
	'MYSQL_ASSOC',
	'MYSQL_BOTH',
	'MYSQL_CLIENT_COMPRESS',
	'MYSQL_CLIENT_IGNORE_SPACE',
	'MYSQL_CLIENT_INTERACTIVE',
	'MYSQL_CLIENT_SSL',
	'MYSQL_NUM'
) as $const) {
	if (defined($const)) {
		echo 'Constant '.$const.' is defined = OK'."\n";
	} else {
		echo '! Constant '.$const.' is NOT defined'."\n";
	}
}

# --- mysql_connect
$function = 'mysql_connect';
$required = true;
if (function_exists($function)) {
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_connect($host, 'doesnotexist123', 'doesnotexist123');
	if ($r !== false) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

	# object or resource
	echo 'Testing '.$function.' return value...';
	$r = mysql_connect($host, $username, $password);
	if (!is_resource($r) && !is_object($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

	$link = $r;
} else {
	version_notice($function, $required);
}

# --- mysql_errno
$function = 'mysql_errno';
$required = false;
if (function_exists($function)) {
	# string
	echo 'Testing '.$function.' return value...';
	$r = mysql_errno($link);
	if (!is_int($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_errno('ABCDEFGH12345');
	if ($r !== NULL) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}


# --- mysql_error
$function = 'mysql_error';
$required = true;
if (function_exists('mysql_error')) {
	# string
	echo 'Testing '.$function.' return value...';
	$r = mysql_error($link);
	if (!is_string($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_error('ABCDEFGH12345');
	if ($r !== NULL) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_real_escape_string
$function = 'mysql_escape_string';
$required = false;
if (function_exists($function)) {
	# true
	echo 'Testing '.$function.' return value...';
	$r = mysql_real_escape_string('Teststring');
	if (!is_string($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_real_escape_string($link); # send in an object
	if ($r !== NULL) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_escape_string
$function = 'mysql_escape_string';
$required = false;
if (function_exists($function)) {
	# string
	echo 'Testing '.$function.' return value...';
	$r = mysql_escape_string('Testing');
	if (!is_string($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_query
$function = 'mysql_query';
$required = true;
if (function_exists($function)) {
	# a mysqli object
	echo 'Testing '.$function.' return value type: object...';
	# $sql = 'SELECT * FROM testtable';
	$sql = 'SHOW DATABASES';
	$sqllog[] = $sql;
	$r = mysql_query($sql);
	if (!is_object($r) && !is_resource($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# now we NEED mysql_num_rows!

# --- mysql_num_rows
$function = 'mysql_num_rows';
$required = true;
if (function_exists($function)) {
	$r = db_query($link, __LINE__, 'SHOW DATABASES LIKE "'.mysql_real_escape_string($database, $link).'"');
	# an integer
	echo 'Testing '.$function.' return value...';
	$rtmp = $r;
	$r = mysql_num_rows($r);
	if (!is_numeric($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	if (!$force && mysql_num_rows($rtmp)) {
		echo 'Fatal error! Database '.$database.' seems to exist.'."\n";
		echo 'If you want to force a deletion, use -f.'."\n";
		die(1);
	}
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_num_rows('faail');
	if ($r !== NULL) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --back to mysql_query: a boolean
$function = 'mysql_query';
$required = true;
if (function_exists($function)) {
	echo 'Testing '.$function.' return value type: boolean...';
	if ($force) {
		# drop db
		$sql = 'DROP DATABASE IF EXISTS '.mysql_real_escape_string($database, $link);
	} else {
		$sql = 'CREATE DATABASE '.mysql_real_escape_string($database, $link);
	}
	$sqllog[] = $sql;
	$r = mysql_query($sql);
	if ($r !== true) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

	# was db dropped or created in previous step?
	if ($force) {
		db_query($link, __LINE__, 'CREATE DATABASE '.mysql_real_escape_string($database, $link));
	}
} else {
	version_notice($function, $required);
}

# --- mysql_select_db
$function = 'mysql_select_db';
$required = true;
if (function_exists($function)) {
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_select_db('12345doesnotexist', $link);
	if ($r !== false) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

	# true
	echo 'Testing '.$function.' return value...';
	$r = mysql_select_db($database, $link);
	if ($r !== true) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# now we know mysql_query works partly

# preparations - do a table
db_query($link, __LINE__, 'DROP TABLE IF EXISTS testtable');
db_query($link, __LINE__, 'CREATE TABLE testtable (id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, testcolumn TINYTEXT NOT NULL)');
db_query($link, __LINE__, 'INSERT INTO testtable (testcolumn) VALUES("testing")');

# --- back to mysql_query again
$function = 'mysql_query';
$required = true;
if (function_exists($function)) {
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_query(false);
	if ($r !== false) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

} else {
	version_notice($function, $required);
}

# --- mysql_affected_rows
$function = 'mysql_affected_rows';
$required = false;
if (function_exists($function)) {
	db_query($link, __LINE__, 'INSERT INTO testtable (testcolumn) VALUES("testing")');
	# integer <> -1
	echo 'Testing '.$function.' return value...';
	$r = mysql_affected_rows($link);
	if (!is_numeric($r) || $r === -1) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# integer = -1
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_affected_rows('INVALID_LINK');
	if (/*!is_numeric($r) || $r !== -1*/ $r !== NULL) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_client_encoding
$function = 'mysql_client_encoding';
$required = false;
if (function_exists($function)) {
	# string
	echo 'Testing '.$function.' return value...';
	$r = mysql_client_encoding($link);
	if (!is_string($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_client_encoding('INVALID_LINK');
	if ($r !== NULL) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_create_db
$function = 'mysql_create_db';
$required = false;
if (function_exists($function)) {
	db_query($link, __LINE__, 'DROP DATABASE '.mysql_real_escape_string($database, $link));
	# true
	echo 'Testing '.$function.' return value...';
	$r = mysql_create_db($database, $link);
	if ($r !== true) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_create_db($database, $link);
	if ($r !== false) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_createdb
$function = 'mysql_createdb';
$required = false;
if (function_exists($function)) {
	db_query($link, __LINE__, 'DROP DATABASE '.mysql_real_escape_string($database, $link));
	# true
	echo 'Testing '.$function.' return value...';
	$r = mysql_createdb($database, $link);
	if ($r !== true) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_createdb($database, $link);
	if ($r !== false) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# recreate testing environment
	if (!mysql_select_db($database, $link)) {
		error(__LINE__, mysql_error($link));
	}
	# preparations - do a table
	db_query($link, __LINE__, 'DROP TABLE IF EXISTS testtable');
	db_query($link, __LINE__, 'CREATE TABLE testtable (id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, testcolumn TINYTEXT NOT NULL)');
	db_query($link, __LINE__, 'INSERT INTO testtable (testcolumn) VALUES("testing")');
} else {
	version_notice($function, $required);
}


# --- mysql_data_seek
$function = 'mysql_data_seek';
$required = false;
if (function_exists($function)) {
	$sql = 'SELECT * FROM testtable';
	$sqllog[] = $sql;
	if (!$r = mysql_query($sql, $link)) {
		echo 'Failed doing preparations.'.__LINE__.': '.mysql_error()."\n";
		die(1);
	}
	# true
	echo 'Testing '.$function.' return value...';
	$r = mysql_data_seek($r, 0);
	if ($r !== true) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_data_seek($r, 999999);
	if ($r !== NULL) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_dbname
$function = 'mysql_dbname';
$required = false;
if (function_exists($function)) {
	$r = db_query($link, __LINE__, 'SELECT * FROM testtable');
	# a string
	echo 'Testing '.$function.' return value...';
	$r = mysql_dbname($r, 0, 0);
	if (!is_string($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

	$r = db_query($link, __LINE__, 'SELECT * FROM testtable', $link);

	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_dbname($r, -1);
	if ($r !== false) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_db_name
$function = 'mysql_db_name';
$required = false;
if (function_exists($function)) {
	$r = db_query($link, __LINE__, 'SELECT * FROM testtable');
	# a string
	echo 'Testing '.$function.' return value...';
	$r = mysql_db_name($r, 0, 0);
	if (!is_string($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	$r = db_query($link, __LINE__, 'SELECT * FROM testtable', $link);
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_db_name($r, -1);
	if ($r !== false) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_db_query
$function = 'mysql_db_query';
$required = false;
if (function_exists($function)) {
	# true
	echo 'Testing '.$function.' return value...';
	$sql = 'SELECT * FROM testtable';
	$sqllog[] = $sql;
	$r = mysql_db_query($database, $sql, $link);
	if (!is_resource($r) && !is_object($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_db_query('12345doesnotexist', 'SHOW TABLES', $link);
	if ($r !== false) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_drop_db
$function = 'mysql_drop_db';
$required = false;
if (function_exists($function)) {
	# true
	echo 'Testing '.$function.' return value...';
	$r = mysql_drop_db($database, $link);
	if ($r !== true) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_drop_db($database, $link);
	if ($r !== false) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# recreate test environment preparations - do a db
	db_query($link, __LINE__, 'CREATE DATABASE '.mysql_real_escape_string($database, $link));
	if (!mysql_select_db($database, $link)) {
		error(__LINE__, mysql_error($link));
	}
	# preparations - do a table
	db_query($link, __LINE__, 'DROP TABLE IF EXISTS testtable');
	db_query($link, __LINE__, 'CREATE TABLE testtable (id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, testcolumn TINYTEXT NOT NULL)');
} else {
	version_notice($function, $required);
}

# --- mysql_fetch_array
$function = 'mysql_fetch_array';
$required = false;
if (function_exists($function)) {
	# run query
	$temp = db_query($link, __LINE__, 'SHOW DATABASES');
	# true
	echo 'Testing '.$function.' return value...';
	$r = mysql_fetch_array($temp);
	if (!is_array($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_fetch_array('INVALID_LINK');
	if ($r !== NULL) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_fetch_assoc
$function = 'mysql_fetch_assoc';
$required = true;
if (function_exists($function)) {
	# run query
	$temp = db_query($link, __LINE__, 'SHOW DATABASES');
	# true
	echo 'Testing '.$function.' return value...';
	$r = mysql_fetch_assoc($temp);
	if (!is_array($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_fetch_assoc('INVALID_LINK');
	if ($r !== NULL) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_fetch_field
$function = 'mysql_fetch_field';
$required = false;
if (function_exists($function)) {
	# run query
	$temp = db_query($link, __LINE__, 'SHOW DATABASES');
	# true
	echo 'Testing '.$function.' return value...';
	$r = mysql_fetch_field($temp);
	if (!is_object($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_fetch_field('INVALID_LINK');
	if ($r !== NULL) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_fetch_lengths
$function = 'mysql_fetch_lengths';
$required = false;
if (function_exists($function)) {
	if (function_exists('mysql_fetch_assoc')) {
		# run query
		$temp = db_query($link, __LINE__, 'SHOW DATABASES');
		mysql_fetch_assoc($temp); # must be done
		# true
		echo 'Testing '.$function.' return value...';
		$r = mysql_fetch_lengths($temp);
		if (!is_array($r)) {
			error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
		}
		echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
		# false
		echo 'Testing '.$function.' error return value...';
		$r = @mysql_fetch_lengths('INVALID_LINK');
		if ($r !== NULL) {
			error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
		}
		echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	} else {
		echo 'Not testing '.$function.' because mysql_fetch_assoc is missing'."\n";
	}
} else {
	version_notice($function, $required);
}

# --- mysql_fetch_object
$function = 'mysql_fetch_object';
$required = false;
if (function_exists($function)) {
	# run query
	$temp = db_query($link, __LINE__, 'SHOW DATABASES');
	# true
	echo 'Testing '.$function.' return value...';
	$r = mysql_fetch_object($temp);
	if (!is_object($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_fetch_object('INVALID_LINK');
	if ($r !== false) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_fetch_row
$function = 'mysql_fetch_row';
$required = false;
if (function_exists($function)) {
	# run query
	$temp = db_query($link, __LINE__, 'SHOW DATABASES');
	# true
	echo 'Testing '.$function.' return value...';
	$r = mysql_fetch_row($temp);
	if (!is_array($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_fetch_row('INVALID_LINK');
	if ($r !== NULL) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_field_flags
$function = 'mysql_field_flags';
$required = false;
if (function_exists($function)) {
	# preparations
	db_query($link, __LINE__, 'DROP TABLE IF EXISTS testtable');
	db_query($link, __LINE__, 'CREATE TABLE testtable(id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, testcolumn TINYTEXT NOT NULL)');
	db_query($link, __LINE__, 'INSERT INTO testtable (testcolumn) VALUES("testing")');
	$temp = db_query($link, __LINE__, 'SELECT * FROM testtable');
	# true
	echo 'Testing '.$function.' return value...';
	$r = mysql_field_flags($temp, 0);
	if (!is_numeric($r) && !is_string($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_field_flags('INVALID_LINK', 0);
	if ($r !== NULL) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_field_len
$function = 'mysql_field_len';
$required = false;
if (function_exists($function)) {
	# run query
	db_query($link, __LINE__, 'DROP TABLE IF EXISTS testtable');
	db_query($link, __LINE__, 'CREATE TABLE testtable(id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, testcolumn TINYTEXT NOT NULL)');
	$temp = db_query($link, __LINE__, 'SELECT * FROM testtable');
	# true
	echo 'Testing '.$function.' return value...';
	$r = mysql_field_len($temp, 0);
	if (!is_numeric($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
		die(1);
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_field_len('INVALID_LINK', 0);
	if ($r !== NULL) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
		die(1);
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_field_name
$function = 'mysql_field_name';
$required = false;
if (function_exists($function)) {
	# run query
	db_query($link, __LINE__, 'DROP TABLE IF EXISTS testtable');
	db_query($link, __LINE__, 'CREATE TABLE testtable(id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, testcolumn TINYTEXT NOT NULL)');
	$temp = db_query($link, __LINE__, 'SELECT * FROM testtable');
	# true
	echo 'Testing '.$function.' return value...';
	$r = mysql_field_name($temp, 0);
	if (!is_string($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_field_name('INVALID_LINK', 0);
	if ($r !== NULL) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_field_seek
$function = 'mysql_field_seek';
$required = false;
if (function_exists($function)) {
	# run query
	db_query($link, __LINE__, 'DROP TABLE IF EXISTS testtable');
	db_query($link, __LINE__, 'CREATE TABLE testtable(id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, testcolumn TINYTEXT NOT NULL)');
	$temp = db_query($link, __LINE__, 'SELECT * FROM testtable');
	# true
	echo 'Testing '.$function.' return value...';
	$r = mysql_field_seek($temp, 0);
	if ($r !== true) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_field_seek('INVALID_LINK', 0);
	if ($r !== NULL) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_field_table
$function = 'mysql_field_table';
$required = false;
if (function_exists($function)) {
	# run query
	db_query($link, __LINE__, 'DROP TABLE IF EXISTS testtable');
	db_query($link, __LINE__, 'CREATE TABLE testtable(id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, testcolumn TINYTEXT NOT NULL)');
	$temp = db_query($link, __LINE__, 'SELECT * FROM testtable');
	# true
	echo 'Testing '.$function.' return value...';
	$r = mysql_field_table($temp, 0);
	if (!is_string($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_field_table('INVALID_LINK', 0);
	if ($r !== NULL) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_field_type
$function = 'mysql_field_type';
$required = false;
if (function_exists($function)) {
	# run query
	db_query($link, __LINE__, 'DROP TABLE IF EXISTS testtable');
	db_query($link, __LINE__, 'CREATE TABLE testtable(id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, testcolumn TINYTEXT NOT NULL)');
	$temp = db_query($link, __LINE__, 'SELECT * FROM testtable');
	# true
	echo 'Testing '.$function.' return value...';
	$r = mysql_field_type($temp, 0);

	if (!is_string($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_field_type('INVALID_LINK', 0);
	if ($r !== NULL) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_free_result
$function = 'mysql_free_result';
$required = false;
if (function_exists($function)) {
	# run query
	db_query($link, __LINE__, 'DROP TABLE IF EXISTS testtable');
	db_query($link, __LINE__, 'CREATE TABLE testtable(id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, testcolumn TINYTEXT NOT NULL)');
	$temp = db_query($link, __LINE__, 'SELECT * FROM testtable');
	# true
	echo 'Testing '.$function.' return value...';
	$r = mysql_free_result($temp);
	if ($r !== true) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# not able to make this function fail
	# false
	# echo 'Testing '.$function.' error return value...';
	# $r = @mysql_free_result(999191);
	# if ($r !== false) {
	# 	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	#
	# }
	# echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_get_client_info
$function = 'mysql_get_client_info';
$required = false;
if (function_exists($function)) {
	# string
	echo 'Testing '.$function.' return value...';
	$r = mysql_get_client_info();

	if (!is_string($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_get_host_info
$function = 'mysql_get_host_info';
$required = false;
if (function_exists($function)) {
	# true
	echo 'Testing '.$function.' return value...';
	$r = mysql_get_host_info($link);
	if (!is_string($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_get_host_info('INVALID_LINK');
	if ($r !== NULL) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_get_proto_info
$function = 'mysql_get_proto_info';
$required = false;
if (function_exists($function)) {
	# true
	echo 'Testing '.$function.' return value...';
	$r = mysql_get_proto_info($link);
	if (!is_numeric($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_get_proto_info('INVALID_LINK');
	if ($r !== NULL) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_get_server_info
$function = 'mysql_get_server_info';
$required = false;
if (function_exists($function)) {
	# true
	echo 'Testing '.$function.' return value...';
	$r = mysql_get_server_info($link);
	if (!is_string($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_get_server_info('INVALID_LINK');
	if ($r !== NULL) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_info
$function = 'mysql_info';
$required = false;
if (function_exists($function)) {
	# run query
	db_query($link, __LINE__, 'DROP TABLE testtable');
	db_query($link, __LINE__, 'CREATE TABLE testtable(id INT NOT NULL PRIMARY KEY AUTO_INCREMENT)');
	$temp = db_query($link, __LINE__, 'ALTER TABLE testtable ADD COLUMN testcolumn INT NOT NULL');
	# true
	echo 'Testing '.$function.' return value...';
	$r = mysql_info($link);
	if (!is_string($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_info('INVALID_LINK');
	if ($r !== NULL) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# reset testing table
	db_query($link, __LINE__, 'DROP TABLE testtable');
	db_query($link, __LINE__, 'CREATE TABLE testtable(id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, testcolumn TINYTEXT NOT NULL)');
	db_query($link, __LINE__, 'INSERT INTO testtable (testcolumn) VALUES("testing")');
} else {
	version_notice($function, $required);
}

# --- mysql_insert_id
$function = 'mysql_info';
$required = false;
if (function_exists($function)) {
	# true
	echo 'Testing '.$function.' return value...';
	$r = mysql_insert_id($link);
	if (!is_numeric($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_insert_id('INVALID_LINK');
	if ($r !== NULL) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_list_dbs
$function = 'mysql_list_dbs';
$required = false;
if (function_exists($function)) {
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_list_dbs('INVALID_LINK');
	if ($r !== NULL) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# a resource
	echo 'Testing '.$function.' return value...';
	$r = mysql_list_dbs($link);
	if (!is_resource($r) && !is_object($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_list_fields
$function = 'mysql_list_fields';
$required = false;
if (function_exists($function)) {
	# a resource
	echo 'Testing '.$function.' return value...';
	$r = mysql_list_fields($database, 'testtable', $link);
	if (!is_resource($r) && !is_object($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	 #false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_list_fields($database, 'doesnotexist', $link);
	if ($r !== false) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_list_processes
$function = 'mysql_list_processes';
$required = false;
if (function_exists($function)) {
	# a resource
	echo 'Testing '.$function.' return value...';
	$r = mysql_list_processes($link);
	if (!is_resource($r) && !is_object($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_list_processes('INVALID_LINK');
	if ($r !== NULL) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_list_tables
$function = 'mysql_list_tables';
$required = false;
if (function_exists($function)) {
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_list_tables('doesnotexist12345', $link);
	if ($r !== false) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# a resource or an object
	echo 'Testing '.$function.' return value...';
	$r = mysql_list_tables($database, $link);
	if (!is_resource($r) && !is_object($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_num_fields
$function = 'mysql_num_fields';
$required = false;
if (function_exists($function)) {
	$r = db_query($link, __LINE__, 'SELECT * FROM testtable');
	# an integer
	echo 'Testing '.$function.' return value...';
	$r = mysql_num_fields($r);
	if (!is_numeric($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_num_fields(false);
	if ($r !== NULL) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_pconnect
$function = 'mysql_pconnect';
$required = false;
if (function_exists($function)) {
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_pconnect($host, 'doesnotexist123', 'doesnotexist123');
	if ($r !== false) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# object or resource
	echo 'Testing '.$function.' return value...';
	$r = mysql_pconnect($host, $username, $password);
	if (!is_resource($r) && !is_object($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	$link = $r;
	# reset db selection
	if (!@mysql_select_db($database, $link)) {
		echo 'Failed selecting database '.$database;
		die(1);
	}
} else {
	version_notice($function, $required);
}

# --- mysql_ping
$function = 'mysql_ping';
$required = false;
if (function_exists($function)) {
	# true
	echo 'Testing '.$function.' return value...';
	$r = mysql_ping($link);
	if ($r !== true) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_ping('ABCDEFGH12345');
	if ($r !== NULL) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_result
$function = 'mysql_result';
$required = false;
if (function_exists($function)) {
	$r = db_query($link, __LINE__, 'SELECT * FROM testtable');
	# an integer
	echo 'Testing '.$function.' return value...';
	$r = mysql_result($r, 0, 'testcolumn');
	if (!is_string($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_result($r, 1000, 'nonexistant');
	if ($r !== NULL) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_set_charset
$function = 'mysql_set_charset';
$required = false;
if (function_exists($function)) {
	# true
	echo 'Testing '.$function.' return value...';
	$r = mysql_set_charset('utf8', $link);
	if ($r !== true) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_set_charset('doesnotexist', $link);
	if ($r !== false) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_stat
$function = 'mysql_stat';
$required = false;
if (function_exists($function)) {
	# true
	echo 'Testing '.$function.' return value...';
	$r = mysql_stat($link);
	if (!is_string($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_stat('INVALID_LINK');
	if ($r !== null) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_tablename
$function = 'mysql_tablename';
$required = false;
if (function_exists($function)) {
	# string
	echo 'Testing '.$function.' return value...';
	$r = mysql_tablename(mysql_list_tables($database, $link), 0);
	if (!is_string($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_tablename(mysql_list_tables($database, $link), 99999);
	if ($r !== false) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_thread_id
$function = 'mysql_thread_id';
$required = false;
if (function_exists($function)) {
	# true
	echo 'Testing '.$function.' return value...';
	$r = mysql_thread_id($link);
	if (!is_numeric($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_thread_id('INVALID_LINK');
	if ($r !== NULL) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- mysql_unbuffered_query
$function = 'mysql_unbuffered_query';
$required = false;
if (function_exists($function)) {
	# a mysqli object
	echo 'Testing '.$function.' return value type I: object...';
	$sql = 'SELECT * FROM testtable';
	$sqllog[] = $sql;
	$r = mysql_unbuffered_query($sql);
	if (!is_object($r) && !is_resource($r)) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	mysql_free_result($r);
	# a boolean
	echo 'Testing '.$function.' return value type II: boolean...';
	$r = mysql_unbuffered_query('INSERT INTO testtable (testcolumn) VALUES("testing")');
	if ($r !== true) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_unbuffered_query(false);
	if ($r !== false) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	db_query($link, __LINE__, 'DROP DATABASE '.mysql_real_escape_string($database, $link));
} else {
	version_notice($function, $required);
}

# --- mysql_close
$function = 'mysql_close';
$required = false;
if (function_exists($function)) {
	# true
	echo 'Testing '.$function.' return value...';
	$r = mysql_close($link);
	if ($r !== true) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
	# false
	echo 'Testing '.$function.' error return value...';
	$r = @mysql_close($link);
	if ($r !== false) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	version_notice($function, $required);
}

# --- end of test
echo 'Test duration '.(microtime(true) - $time_start).' seconds'."\n";
echo 'Test completed '.date('Y-m-d H:i:s')."\n";
if ($skipped) {
	echo 'Skipped testing of '.$skipped.' function(s), see details above.'."\n";
	echo 'This is normal when testing native functions as a few may not be available.'."\n";
}

if ($missing_constants) {
	echo 'Missing '.$missing_constants.' constant(s), see details above.'."\n";
	echo 'This is normal when testing native constants as a few may not be available.'."\n";
}

if (!$skipped && !$missing_constants) {
	echo 'No errors found, all functions tested and all constants exist.'."\n";
}

#   function is_mysqli_or_resource($r)
#   function is_mysql_resource($r)
#   function is_generic_resource($r)
#   function is_mysql_resource_old($result)
#   function mysql_ensure_link($link_identifier)

# X function mysql_affected_rows($link_identifier = NULL)
# X function mysql_client_encoding($link_identifier = NULL)
# X function mysql_close($link = NULL)
# X function mysql_connect($server = MYSQL_DEFAULT_HOST, $username = MYSQL_DEFAULT_USER, $password = MYSQL_DEFAULT_PASSWORD, $new_link = false, $client_flags = 0)
# X function mysql_create_db($database_name, $link_identifier = NULL)
# X function mysql_data_seek($result , $row_number)
# X function mysql_db_name($result , $row, $field = NULL)
# X function mysql_db_query($database, $query, $link_identifier = NULL)
# X function mysql_drop_db($database_name, $link_identifier = NULL)
# X function mysql_errno($link_identifier = NULL)
# X function mysql_error($link_identifier = NULL)
# X function mysql_escape_string($unescaped_string)
# X function mysql_fetch_array($result, $result_type = MYSQL_BOTH)
# X function mysql_fetch_assoc ($result)
# X function mysql_fetch_field($result, $field_offset = NULL)
# X function mysql_fetch_lengths($result)
# X function mysql_fetch_object ($result, $class_name=NULL, $params=NULL)
# X function mysql_fetch_row ($result)
# X function mysql_field_flags($result, $field_offset)
# X function mysql_field_len($result, $field_offset)
# X function mysql_field_name($result, $field_offset)
# X function mysql_field_seek($result, $field_offset)
# X function mysql_field_table($result, $field_offset)
# X function mysql_field_type($result, $field_offset)
# X function mysql_free_result($result)
# X function mysql_get_client_info()
# X function mysql_get_host_info ($link_identifier = NULL)
# X function mysql_get_proto_info($link_identifier = NULL)
# X function mysql_get_server_info($link_identifier = NULL)
# X function mysql_info($link_identifier = NULL)
# X function mysql_insert_id($link_identifier = NULL)
# X function mysql_list_dbs ($link_identifier = NULL)
# X function mysql_list_fields ($database_name, $table_name, $link_identifier = NULL)
# X function mysql_list_processes($link_identifier = NULL)
# X function mysql_list_tables ($database_name, $table_name, $link_identifier = NULL)
# X function mysql_num_fields ($result)
# X function mysql_num_rows($result)
# X function mysql_pconnect($server = MYSQL_DEFAULT_HOST, $username = MYSQL_DEFAULT_USER, $password = MYSQL_DEFAULT_PASSWORD, $client_flags = 0)
# X function mysql_ping($link_identifier = NULL)
# X function mysql_query ($query, $link_identifier = NULL)
# X function mysql_real_escape_string($unescaped_string, $link_identifier = NULL)
# X function mysql_result($result , $row , $field = 0)
# X function mysql_select_db ($database_name, $link_identifier = NULL)
# X function mysql_set_charset($charset, $link_identifier = NULL)
# X function mysql_stat($link_identifier = NULL)
# X function mysql_tablename ($result, $i)
# X function mysql_thread_id($link_identifier = NULL)

?>
