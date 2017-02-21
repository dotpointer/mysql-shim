<?php
	# test file for mysql shim library
	# by robert klebe, dotpointer

	# changelog
	# 2016-02-24 15:27:05 - first version
	# 2016-12-26 15:08:00 - renaming file from mysql.test.php to mysql-shim.test.php
	# 2017-02-20 18:06:00 - bugfix, renaming inclusion of library file
	# 2017-02-20 20:10:00 - bugfix, invalid connection credentials resulted in error, printing help on connect error, thanks to Tony Russo for finding it
	# 2017-02-20 22:19:21 - adding tests for mysql_query, mysql_unbuffered_query, mysql_num_rows, mysql_pconnect etc.
	# 2017-02-21 18:56:20 - rewriting error handling

$time_start = microtime(true);
$sqllog = array();
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
	echo '	-i to include shim library even if not present in directory'."\n";
	echo '	-I to skip shim library even if present in directory'."\n";
}

# get parameters
$opt = getopt('fyh:u:p:d:HiI', array('help'));

# get parameters
$confirmed	= isset($opt['y']);
$force		= isset($opt['f']);
$database 	= isset($opt['d']) && strlen($opt['d']) ? $opt['d'] : 'testdatabase12345';;
$help		= isset($opt['H']) || isset($opt['help']);
$host 		= isset($opt['h']) && strlen($opt['h']) ? $opt['h'] : 'localhost';
$include	= isset($opt['i']);
$includeskip= isset($opt['I']);
$password	= isset($opt['p']) ? $opt['p'] : '';
$username	= isset($opt['u']) && strlen($opt['u']) ? $opt['u'] : 'root';

# is help requested?
if ($help) {
	# print help
	print_help();
	die(0);
}

$extension = false;

# print intro
echo 'Test of PHP MySQL to MySQLi migration shim library'."\n";
echo 'Test started '.date('Y-m-d H:i:s')."\n";

echo 'Uname: '.php_uname()."\n";
echo 'PHP version: '.phpversion()."\n";

# check if the shim exists
$shim_exists = file_exists('mysql-shim.php');

echo 'Shim library present in testing directory: '.($shim_exists ? 'yes' : 'no')."\n";

# confirm user wants to continue, if not override is specified
if (!$include && !$includeskip) {
	if (!$shim_exists) {
		# make sure user wants to do this
		echo 'The shim library file (mysql-shim.php) is not present in the directory.'."\n";
		echo 'Do you want to include it anyway? (It may be in global includes).'."\n";
		echo 'Type "y" to try to include it: ';
		$handle = fopen ("php://stdin","r");
		$line = fgets($handle);
		if(trim($line) === 'y'){
			$include = true;
		}
		fclose($handle);
		echo "\n";
	} else {
		$include = true;
	}
}

echo 'Including shim library: '.($include && !$includeskip ? 'yes' : 'no')."\n";
# include the file to test
if ($include && !$includeskip) {
	require_once('mysql-shim.php');
}


echo 'MySQL extension loaded: '.(extension_loaded('mysql') ? 'yes' : 'no')."\n";
echo 'MySQLi extension loaded: '.(extension_loaded('mysqli') ? 'yes' : 'no')."\n";

if (!extension_loaded('mysql') && !extension_loaded('mysqli')) {
	echo 'Fatal error! Neither MySQL nor MySQLi (preferred) extensions are loaded.'."\n";
	echo 'Cannot continue without one of these.'."\n";
	die(1);
}

# confirm user wants to continue, if not override is specified
if (!$confirmed && extension_loaded('mysql')) {
	# make sure user wants to do this
	echo "\n".'WARNING! The (original?) MySQL extension seems to be loaded.'."\n";
	echo 'This will NOT test the library, but the native PHP MySQL extension functions.'."\n";
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

# --- test function existence
foreach (array(
	'mysql_affected_rows',
	'mysql_client_encoding',
	'mysql_close',
	'mysql_connect',
	'mysql_data_seek',
	'mysql_db_name',
	'mysql_db_query',
	'mysql_errno',
	'mysql_error',
	'mysql_escape_string',
	'mysql_fetch_array',
	'mysql_fetch_assoc',
	'mysql_fetch_field',
	'mysql_fetch_lengths',
	'mysql_fetch_object',
	'mysql_fetch_row',
	'mysql_field_flags',
	'mysql_field_len',
	'mysql_field_name',
	'mysql_field_seek',
	'mysql_field_table',
	'mysql_field_type',
	'mysql_free_result',
	'mysql_get_client_info',
	'mysql_get_host_info',
	'mysql_get_proto_info',
	'mysql_get_server_info',
	'mysql_info',
	'mysql_insert_id',
	'mysql_list_dbs',
	'mysql_list_fields',
	'mysql_list_processes',
	'mysql_list_tables',
	'mysql_num_fields',
	'mysql_num_rows',
	'mysql_pconnect',
	'mysql_ping',
	'mysql_query',
	'mysql_real_escape_string',
	'mysql_result',
	'mysql_select_db',
	'mysql_set_charset',
	'mysql_stat',
	'mysql_tablename',
	'mysql_thread_id'
) as $v) {
	echo 'Checking existence of '.$v.': ';
	if (function_exists($v)) {
		echo 'OK'."\n";
	} else {
		echo 'Not found'."\n";

		echo 'Fatal error! Could not find function '.$v."\n";
		echo 'Make sure that the library in included and/or that MySQL(i) extension is loaded'."\n";
		die(1);
	}
}

# --- test function existence
foreach ($functions = array(
	'mysql_createdb',
	'mysql_create_db',
	'mysql_drop_db'
) as $v) {
	echo 'Checking existence of PHP 4.3 deprecated '.$v.': ';
	if (function_exists($v)) {
		echo 'OK'."\n";
	} else {
		echo 'Not found (normal for native PHP5 extension)'."\n";
	}
}

# --- mysql_connect

# false
echo 'Testing mysql_connect error return value...';
$r = @mysql_connect($host, 'doesnotexist123', 'doesnotexist123');
if ($r !== false) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# object or resource
echo 'Testing mysql_connect return value...';
$r = mysql_connect($host, $username, $password);
if (!is_resource($r) && !is_object($r)) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

$link = $r;

# --- mysql_errno

# string
echo 'Testing mysql_errno return value...';
$r = mysql_errno($link);
if (!is_int($r)) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_errno error return value...';
$r = @mysql_errno('ABCDEFGH12345');
if ($r !== NULL) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_error

# string
echo 'Testing mysql_error return value...';
$r = mysql_error($link);
if (!is_string($r)) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_error error return value...';
$r = @mysql_error('ABCDEFGH12345');
if ($r !== NULL) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_escape_string

# string
echo 'Testing mysql_escape_string return value...';
$r = mysql_escape_string('Testing');
if (!is_string($r)) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";


# --- mysql_query

# a mysqli object
echo 'Testing mysql_query return value type: object...';
# $sql = 'SELECT * FROM testtable';
$sql = 'SHOW DATABASES';
$sqllog[] = $sql;
$r = mysql_query($sql);
if (!is_object($r) && !is_resource($r)) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

$r = db_query($link, __LINE__, 'SHOW DATABASES LIKE "'.mysql_real_escape_string($database, $link).'"');

# now we NEED mysql_num_rows!

# --- mysql_num_rows

# an integer
echo 'Testing mysql_num_rows return value...';
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
echo 'Testing mysql_num_rows error return value...';
$r = @mysql_num_rows('faail');
if ($r !== NULL) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --back to mysql_query: a boolean
echo 'Testing mysql_query return value type: boolean...';
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

# --- mysql_select_db

# false
echo 'Testing mysql_select_db error return value...';
$r = @mysql_select_db('12345doesnotexist', $link);
if ($r !== false) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# true
echo 'Testing mysql_select_db return value...';
$r = mysql_select_db($database, $link);
if ($r !== true) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";



# do_query($link, __LINE__, 'INSERT INTO testtable (testcolumn) VALUES("testing")');

# now we know mysql_query works

# preparations - should db be dropped?

# preparations - do a db
# db_query($link, __LINE__, 'CREATE DATABASE '.mysql_real_escape_string($database, $link));

# preparations - select db
# if (!mysql_select_db($database, $link)) {
# 	error(__LINE__, mysql_error($link));
# }

# preparations - do a table
db_query($link, __LINE__, 'DROP TABLE IF EXISTS testtable');
db_query($link, __LINE__, 'CREATE TABLE testtable (id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, testcolumn TINYTEXT NOT NULL)');
db_query($link, __LINE__, 'INSERT INTO testtable (testcolumn) VALUES("testing")');

# false
echo 'Testing mysql_query error return value...';
$r = @mysql_query(false);
if ($r !== false) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_affected_rows

db_query($link, __LINE__, 'INSERT INTO testtable (testcolumn) VALUES("testing")');

# integer <> -1
echo 'Testing mysql_affected_rows return value...';
$r = mysql_affected_rows($link);
if (!is_numeric($r) || $r === -1) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# integer = -1
echo 'Testing mysql_affected_rows error return value...';
$r = @mysql_affected_rows('INVALID_LINK');
if (/*!is_numeric($r) || $r !== -1*/ $r !== NULL) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_client_encoding

# string
echo 'Testing mysql_client_encoding return value...';
$r = mysql_client_encoding($link);
if (!is_string($r)) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_client_encoding error return value...';
$r = @mysql_client_encoding('INVALID_LINK');
if ($r !== NULL) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

if (function_exists('mysql_create_db')) {

	# --- mysql_create_db
	db_query($link, __LINE__, 'DROP DATABASE '.mysql_real_escape_string($database, $link));

	# true
	echo 'Testing mysql_create_db return value...';
	$r = mysql_create_db($database, $link);
	if ($r !== true) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

	# false
	echo 'Testing mysql_create_db error return value...';
	$r = @mysql_create_db($database, $link);
	if ($r !== false) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	echo 'Not testing mysql_create_db - Nonexistant (Normal for native PHP5 extension)'."\n";
}

if (function_exists('mysql_createdb')) {

	# --- mysql_createdb
	db_query($link, __LINE__, 'DROP DATABASE '.mysql_real_escape_string($database, $link));

	# true
	echo 'Testing mysql_createdb return value...';
	$r = mysql_createdb($database, $link);
	if ($r !== true) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

	# false
	echo 'Testing mysql_createdb error return value...';
	$r = @mysql_createdb($database, $link);
	if ($r !== false) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
} else {
	echo 'Not testing mysql_createdb - Nonexistant (Normal for native PHP5 extension)'."\n";
}


# recreate testing environment
if (!mysql_select_db($database, $link)) {
	error(__LINE__, mysql_error($link));
}
# preparations - do a table
db_query($link, __LINE__, 'DROP TABLE IF EXISTS testtable');
db_query($link, __LINE__, 'CREATE TABLE testtable (id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, testcolumn TINYTEXT NOT NULL)');
db_query($link, __LINE__, 'INSERT INTO testtable (testcolumn) VALUES("testing")');

# --- mysql_data_seek

$sql = 'SELECT * FROM testtable';
$sqllog[] = $sql;
if (!$r = mysql_query($sql, $link)) {
	echo 'Failed doing preparations.'.__LINE__.': '.mysql_error()."\n";
	die(1);
}

# true
echo 'Testing mysql_data_seek return value...';
$r = mysql_data_seek($r, 0);
if ($r !== true) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_data_seek error return value...';
$r = @mysql_data_seek($r, 999999);
if ($r !== NULL) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_db_name

$r = db_query($link, __LINE__, 'SELECT * FROM testtable');

# a string
echo 'Testing mysql_db_name return value...';
$r = mysql_db_name($r, 0, 0);
if (!is_string($r)) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

$r = db_query($link, __LINE__, 'SELECT * FROM testtable', $link);

# false
echo 'Testing mysql_db_name error return value...';
$r = mysql_db_name($r, -1);
if ($r !== false) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_db_query

# true
echo 'Testing mysql_db_query return value...';
$sql = 'SELECT * FROM testtable';
$sqllog[] = $sql;
$r = mysql_db_query($database, $sql, $link);
if (!is_resource($r) && !is_object($r)) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_db_query error return value...';
$r = @mysql_db_query('12345doesnotexist', 'SHOW TABLES', $link);
if ($r !== false) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

if (function_exists('mysql_drop_db')) {
	# --- mysql_drop_db

	# true
	echo 'Testing mysql_drop_db return value...';
	$r = mysql_drop_db($database, $link);
	if ($r !== true) {
		error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	}
	echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

	# false
	echo 'Testing mysql_drop_db error return value...';
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
	echo 'Not testing mysql_drop_db - Nonexistant (Normal for native PHP5 extension)'."\n";
}

# --- mysql_fetch_array

# run query
$temp = db_query($link, __LINE__, 'SHOW DATABASES');

# true
echo 'Testing mysql_fetch_array return value...';
$r = mysql_fetch_array($temp);
if (!is_array($r)) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_fetch_array error return value...';
$r = @mysql_fetch_array('INVALID_LINK');
if ($r !== NULL) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_fetch_assoc

# run query
$temp = db_query($link, __LINE__, 'SHOW DATABASES');

# true
echo 'Testing mysql_fetch_assoc return value...';
$r = mysql_fetch_assoc($temp);
if (!is_array($r)) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_fetch_assoc error return value...';
$r = @mysql_fetch_assoc('INVALID_LINK');
if ($r !== NULL) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_fetch_field

# run query
$temp = db_query($link, __LINE__, 'SHOW DATABASES');

# true
echo 'Testing mysql_fetch_field return value...';
$r = mysql_fetch_field($temp);
if (!is_object($r)) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_fetch_field error return value...';
$r = @mysql_fetch_field('INVALID_LINK');
if ($r !== NULL) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_fetch_lengths

# run query
$temp = db_query($link, __LINE__, 'SHOW DATABASES');

mysql_fetch_assoc($temp); # must be done

# true
echo 'Testing mysql_fetch_lengths return value...';
$r = mysql_fetch_lengths($temp);
if (!is_array($r)) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_fetch_lengths error return value...';
$r = @mysql_fetch_lengths('INVALID_LINK');
if ($r !== NULL) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_fetch_object

# run query
$temp = db_query($link, __LINE__, 'SHOW DATABASES');

# true
echo 'Testing mysql_fetch_object return value...';
$r = mysql_fetch_object($temp);
if (!is_object($r)) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_fetch_object error return value...';
$r = @mysql_fetch_object('INVALID_LINK');
if ($r !== false) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_fetch_row

# run query
$temp = db_query($link, __LINE__, 'SHOW DATABASES');

# true
echo 'Testing mysql_fetch_row return value...';
$r = mysql_fetch_row($temp);
if (!is_array($r)) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_fetch_row error return value...';
$r = @mysql_fetch_row('INVALID_LINK');
if ($r !== NULL) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_field_flags

# preparations
db_query($link, __LINE__, 'DROP TABLE IF EXISTS testtable');
db_query($link, __LINE__, 'CREATE TABLE testtable(id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, testcolumn TINYTEXT NOT NULL)');
db_query($link, __LINE__, 'INSERT INTO testtable (testcolumn) VALUES("testing")');
$temp = db_query($link, __LINE__, 'SELECT * FROM testtable');

# true
echo 'Testing mysql_field_flags return value...';
$r = mysql_field_flags($temp, 0);

if (!is_numeric($r) && !is_string($r)) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_field_flags error return value...';
$r = @mysql_field_flags('INVALID_LINK', 0);
if ($r !== NULL) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_field_len

# run query
db_query($link, __LINE__, 'DROP TABLE IF EXISTS testtable');
db_query($link, __LINE__, 'CREATE TABLE testtable(id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, testcolumn TINYTEXT NOT NULL)');
$temp = db_query($link, __LINE__, 'SELECT * FROM testtable');
# true
echo 'Testing mysql_field_len return value...';
$r = mysql_field_len($temp, 0);

if (!is_numeric($r)) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	die(1);
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_field_len error return value...';
$r = @mysql_field_len('INVALID_LINK', 0);
if ($r !== NULL) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
	die(1);
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_field_name

# run query
db_query($link, __LINE__, 'DROP TABLE IF EXISTS testtable');
db_query($link, __LINE__, 'CREATE TABLE testtable(id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, testcolumn TINYTEXT NOT NULL)');
$temp = db_query($link, __LINE__, 'SELECT * FROM testtable');
# true
echo 'Testing mysql_field_name return value...';
$r = mysql_field_name($temp, 0);

if (!is_string($r)) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_field_name error return value...';
$r = @mysql_field_name('INVALID_LINK', 0);
if ($r !== NULL) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_field_seek

# run query
db_query($link, __LINE__, 'DROP TABLE IF EXISTS testtable');
db_query($link, __LINE__, 'CREATE TABLE testtable(id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, testcolumn TINYTEXT NOT NULL)');
$temp = db_query($link, __LINE__, 'SELECT * FROM testtable');
# true
echo 'Testing mysql_field_seek return value...';
$r = mysql_field_seek($temp, 0);

if ($r !== true) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_field_seek error return value...';
$r = @mysql_field_seek('INVALID_LINK', 0);
if ($r !== NULL) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_field_table

# run query
db_query($link, __LINE__, 'DROP TABLE IF EXISTS testtable');
db_query($link, __LINE__, 'CREATE TABLE testtable(id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, testcolumn TINYTEXT NOT NULL)');
$temp = db_query($link, __LINE__, 'SELECT * FROM testtable');
# true
echo 'Testing mysql_field_table return value...';
$r = mysql_field_table($temp, 0);

if (!is_string($r)) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_field_table error return value...';
$r = @mysql_field_table('INVALID_LINK', 0);
if ($r !== NULL) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# run query
db_query($link, __LINE__, 'DROP TABLE IF EXISTS testtable');
db_query($link, __LINE__, 'CREATE TABLE testtable(id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, testcolumn TINYTEXT NOT NULL)');
$temp = db_query($link, __LINE__, 'SELECT * FROM testtable');
# true
echo 'Testing mysql_field_type return value...';
$r = mysql_field_type($temp, 0);

if (!is_string($r)) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_field_type error return value...';
$r = @mysql_field_type('INVALID_LINK', 0);
if ($r !== NULL) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_free_result

# run query
db_query($link, __LINE__, 'DROP TABLE IF EXISTS testtable');
db_query($link, __LINE__, 'CREATE TABLE testtable(id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, testcolumn TINYTEXT NOT NULL)');
$temp = db_query($link, __LINE__, 'SELECT * FROM testtable');
# true
echo 'Testing mysql_free_result return value...';
$r = mysql_free_result($temp);

if ($r !== true) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# is not able to make this function fail
# false
# echo 'Testing mysql_free_result error return value...';
# $r = @mysql_free_result(999191);
# if ($r !== false) {
# 	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
#
# }
# echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_get_client_info

# string
echo 'Testing mysql_get_client_info return value...';
$r = mysql_get_client_info();

if (!is_string($r)) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_get_host_info

# true
echo 'Testing mysql_get_host_info return value...';
$r = mysql_get_host_info($link);
if (!is_string($r)) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_get_host_info error return value...';
$r = @mysql_get_host_info('INVALID_LINK');
if ($r !== NULL) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_get_proto_info

# true
echo 'Testing mysql_get_proto_info return value...';
$r = mysql_get_proto_info($link);
if (!is_numeric($r)) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_get_proto_info error return value...';
$r = @mysql_get_proto_info('INVALID_LINK');
if ($r !== NULL) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_get_server_info

# true
echo 'Testing mysql_get_server_info return value...';
$r = mysql_get_server_info($link);
if (!is_string($r)) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_get_server_info error return value...';
$r = @mysql_get_server_info('INVALID_LINK');
if ($r !== NULL) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_info

# run query
db_query($link, __LINE__, 'DROP TABLE testtable');
db_query($link, __LINE__, 'CREATE TABLE testtable(id INT NOT NULL PRIMARY KEY AUTO_INCREMENT)');
$temp = db_query($link, __LINE__, 'ALTER TABLE testtable ADD COLUMN testcolumn INT NOT NULL');

# true
echo 'Testing mysql_info return value...';
$r = mysql_info($link);
if (!is_string($r)) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_info error return value...';
$r = @mysql_info('INVALID_LINK');
if ($r !== NULL) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# reset testing table
db_query($link, __LINE__, 'DROP TABLE testtable');
db_query($link, __LINE__, 'CREATE TABLE testtable(id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, testcolumn TINYTEXT NOT NULL)');
db_query($link, __LINE__, 'INSERT INTO testtable (testcolumn) VALUES("testing")');

# --- mysql_insert_id

# true
echo 'Testing mysql_insert_id return value...';
$r = mysql_insert_id($link);
if (!is_numeric($r)) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_insert_id error return value...';
$r = @mysql_insert_id('INVALID_LINK');
if ($r !== NULL) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_list_dbs

# false
echo 'Testing mysql_list_dbs error return value...';
$r = @mysql_list_dbs('INVALID_LINK');
if ($r !== NULL) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# a resource
echo 'Testing mysql_list_dbs return value...';
$r = mysql_list_dbs($link);
if (!is_resource($r) && !is_object($r)) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_list_fields

# a resource
echo 'Testing mysql_list_fields return value...';
$r = mysql_list_fields($database, 'testtable', $link);
if (!is_resource($r) && !is_object($r)) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

 #false
echo 'Testing mysql_list_fields error return value...';
$r = @mysql_list_fields($database, 'doesnotexist', $link);
if ($r !== false) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_list_processes

# a resource
echo 'Testing mysql_list_processes return value...';
$r = mysql_list_processes($link);
if (!is_resource($r) && !is_object($r)) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

 #false
echo 'Testing mysql_list_processes error return value...';
$r = @mysql_list_processes('INVALID_LINK');
if ($r !== NULL) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";


# --- mysql_list_tables

# false
echo 'Testing mysql_list_tables error return value...';
$r = @mysql_list_tables('doesnotexist12345', $link);
if ($r !== false) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# a resource or an object
echo 'Testing mysql_list_tables return value...';
$r = mysql_list_tables($database, $link);
if (!is_resource($r) && !is_object($r)) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_num_fields

$r = db_query($link, __LINE__, 'SELECT * FROM testtable');

# an integer
echo 'Testing mysql_num_fields return value...';
$r = mysql_num_fields($r);
if (!is_numeric($r)) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_num_fields error return value...';
$r = @mysql_num_fields(false);
if ($r !== NULL) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_pconnect

# false
echo 'Testing mysql_pconnect error return value...';
$r = @mysql_pconnect($host, 'doesnotexist123', 'doesnotexist123');
if ($r !== false) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# object or resource
echo 'Testing mysql_pconnect return value...';
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

# --- mysql_ping

# true
echo 'Testing mysql_ping return value...';
$r = mysql_ping($link);
if ($r !== true) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_ping error return value...';
$r = @mysql_ping('ABCDEFGH12345');
if ($r !== NULL) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_real_escape_string

# true
echo 'Testing mysql_real_escape_string return value...';
$r = mysql_real_escape_string('Teststring');
if (!is_string($r)) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_real_escape_string error return value...';
$r = @mysql_real_escape_string($link); # send in an object
if ($r !== NULL) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_result

$r = db_query($link, __LINE__, 'SELECT * FROM testtable');

# an integer
echo 'Testing mysql_result return value...';
$r = mysql_result($r, 0, 'testcolumn');
if (!is_string($r)) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_result error return value...';
$r = @mysql_result($r, 1000, 'nonexistant');
if ($r !== NULL) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_set_charset
# true
echo 'Testing mysql_set_charset return value...';
$r = mysql_set_charset('utf8', $link);
if ($r !== true) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_set_charset error return value...';
$r = @mysql_set_charset('doesnotexist', $link);
if ($r !== false) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_stat

# true
echo 'Testing mysql_stat return value...';
$r = mysql_stat($link);
if (!is_string($r)) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_stat error return value...';
$r = @mysql_stat('INVALID_LINK');
if ($r !== null) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_tablename

# string
echo 'Testing mysql_tablename return value...';
$r = mysql_tablename(mysql_list_tables($database, $link), 0);
if (!is_string($r)) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_tablename error return value...';
$r = @mysql_tablename(mysql_list_tables($database, $link), 99999);
if ($r !== false) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_thread_id

# true
echo 'Testing mysql_thread_id return value...';
$r = mysql_thread_id($link);
if (!is_numeric($r)) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_thread_id error return value...';
$r = @mysql_thread_id('INVALID_LINK');
if ($r !== NULL) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_unbuffered_query

# a mysqli object
echo 'Testing mysql_unbuffered_query return value type I: object...';
$sql = 'SELECT * FROM testtable';
$sqllog[] = $sql;
$r = mysql_unbuffered_query($sql);
if (!is_object($r) && !is_resource($r)) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";
mysql_free_result($r);

# a boolean
echo 'Testing mysql_unbuffered_query return value type II: boolean...';
$r = mysql_unbuffered_query('INSERT INTO testtable (testcolumn) VALUES("testing")');
if ($r !== true) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_unbuffered_query error return value...';
$r = @mysql_unbuffered_query(false);
if ($r !== false) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

db_query($link, __LINE__, 'DROP DATABASE '.mysql_real_escape_string($database, $link));

# --- mysql_close

# true
echo 'Testing mysql_close return value...';
$r = mysql_close($link);
if ($r !== true) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_close error return value...';
$r = @mysql_close($link);
if ($r !== false) {
	error(__LINE__, 'FAIL, invalid return value: '.var_export($r, true));
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- end of test

echo 'Test duration '.(microtime(true) - $time_start).' seconds'."\n";
echo 'Test completed without errors '.date('Y-m-d H:i:s')."\n";

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
