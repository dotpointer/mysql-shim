<?php
	# test file for mysql shim library
	# by robert klebe, dotpointer

	# changelog
	# 2016-02-24 15:27:05 - first version
	# 2016-12-26 15:08:00 - renaming file from mysql.test.php to mysql-shim.test.php
	# 2017-02-20 18:06:00 - bugfix, renaming inclusion of library file

	$time_start = microtime(true);

# include the file to test
require_once('mysql-shim.php');

function print_help() {

	echo 'The following parameters can be specified: '."\n";
	echo '	-h <hostname> (optional, defaults to localhost)'."\n";
	echo '	-u <username> (optional, defaults to root)'."\n";
	echo '	-p <password> (optional, defaults to an empty string)'."\n";
	echo '	-d <database> (optional, defaults to testdatabase12345, to be created and deleted)'."\n";
	echo '	-? or --help show this help'."\n";
	die();

}

# get parameters
$opt = getopt('h:u:p:d:?', array('help'));

# get parameters
$database 	= isset($opt['d']) && strlen($opt['d']) ? $opt['d'] : 'testdatabase12345';;
$host 		= isset($opt['h']) && strlen($opt['h']) ? $opt['h'] : 'localhost';
$password 	= isset($opt['p']) ? $opt['p'] : '';
$username 	= isset($opt['u']) && strlen($opt['u']) ? $opt['u'] : 'root';

if (isset($opt['?']) || isset($opt['help'])) {
	print_help();
	die(0);
}

# print intro
echo 'Test of PHP MySQL to MySQLi migration shim library'."\n";
echo 'Test started '.date('Y-m-d H:i:s')."\n";
echo 'Using the following credentials: '."\n";
echo 'Host: '.$host."\n";
echo 'Username: '.$username."\n";
echo 'Password: '.str_repeat('*', strlen($password))."\n";
echo 'Database: '.$database."\n";

# preparations - connect to default host and make a default database
echo 'Connecting to database server and pre-selecting db...'."\n";
$link = @mysql_connect($host, $username, $password);
if (!$link) {
	echo 'Failed to connect to host: '.$host.'.'."\n";
	print_help();
	die(1);
}

# preps - do a db
@mysql_create_db($database, $link);
if (!@mysql_select_db($database, $link)) {
	die('Failed selecting database '.$database.', error: '.mysql_error());
}

# preps - do a table
@mysql_query('CREATE TABLE testtable (id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, testcolumn TINYTEXT NOT NULL)', $link);

# --- mysql_affected_rows ---

# insert a row to make rows affected
if (!mysql_query('INSERT INTO testtable (testcolumn) VALUES("testar")', $link)) die('Failed doing preps.'.__LINE__.': '.mysql_error());

# integer <> -1
echo 'Testing mysql_affected_rows return value...';
$r = mysql_affected_rows($link);
if (!is_numeric($r) || $r === -1) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# integer = -1
echo 'Testing mysql_affected_rows error return value...';
$r = @mysql_affected_rows('INVALID_LINK');
if (!is_numeric($r) || $r !== -1) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_client_encoding ----

# string
echo 'Testing mysql_client_encoding return value...';
$r = mysql_client_encoding($link);
if (!is_string($r)) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_client_encoding error return value...';
$r = @mysql_client_encoding('INVALID_LINK');
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_close ----

# true
echo 'Testing mysql_close return value...';
$r = mysql_close($link);
if ($r !== true) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_close error return value...';
$r = @mysql_close($link);
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_connect ----

# false
echo 'Testing mysql_connect error return value...';
$r = @mysql_connect($host, 'doesnotexist123', 'doesnotexist123');
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# object or resource
echo 'Testing mysql_connect return value...';
$r = mysql_connect($host, $username, $password);
if (!is_mysqli_or_resource($r)) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

$link = $r;

# reset db selection
if (!@mysql_select_db($database, $link)) die('Failed selecting database '.$database);

# --- mysql_create_db ----

# run query
@mysqli_query(mysql_ensure_link($link_identifier), 'DROP DATABASE '.mysql_real_escape_string($database));

# true
echo 'Testing mysql_create_db return value...';
$r = mysql_create_db($database, $link);
if ($r !== true) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_create_db error return value...';
$r = @mysql_create_db($database, $link);
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# recreate testing environment
if (!@mysql_select_db($database, $link)) die('Failed selecting database '.$database);
# preps - do a table
@mysql_query('CREATE TABLE testtable (id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, testcolumn TINYTEXT NOT NULL)', $link);
if (!mysql_query('INSERT INTO testtable (testcolumn) VALUES("testar")', $link)) die('Failed doing preps.'.__LINE__.': '.mysql_error());

# --- mysql_data_seek ----

if (!$r = mysql_query('SELECT * FROM testtable', $link)) die('Failed doing preps.'.__LINE__.': '.mysql_error());

# true
echo 'Testing mysql_data_seek return value...';
$r = mysql_data_seek($r, 0);
if ($r !== true) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_data_seek error return value...';
$r = @mysql_data_seek($r, 999999);
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_db_name ----

if (!$r = mysql_query('SELECT * FROM testtable', $link)) die('Failed doing preps.'.__LINE__.': '.mysql_error());

# a string
echo 'Testing mysql_db_name return value...';
$r = mysql_db_name($r, 0, 0);
if (!is_string($r)) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

if (!$r = mysql_query('SELECT * FROM testtable', $link)) die('Failed doing preps.'.__LINE__.': '.mysql_error());

# false
echo 'Testing mysql_db_name error return value...';
$r = mysql_db_name($r, -1);
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_db_query ----

# true
echo 'Testing mysql_db_query return value...';
$r = mysql_db_query($database, 'SELECT * FROM testtable', $link);
if (!is_resource($r) && !is_object($r)) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_db_query error return value...';
$r = @mysql_db_query('12345doesnotexist', 'SHOW TABLES', $link);
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_drop_db ----

# true
echo 'Testing mysql_drop_db return value...';
$r = mysql_drop_db($database, $link);
if ($r !== true) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_drop_db error return value...';
$r = @mysql_drop_db($database, $link);
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";


# recreate test environment preps - do a db
@mysql_create_db($database, $link);
if (!@mysql_select_db($database, $link)) die('Failed selecting database '.$database);
# preps - do a table
@mysql_query('CREATE TABLE testtable (id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, testcolumn TINYTEXT NOT NULL)', $link);

# --- mysql_errno ----

# string
echo 'Testing mysql_errno return value...';
$r = mysql_errno($link);
if (!is_int($r)) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_errno error return value...';
$r = @mysql_errno('ABCDEFGH12345');
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_error ----

# string
echo 'Testing mysql_error return value...';
$r = mysql_error($link);
if (!is_string($r)) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_error error return value...';
$r = @mysql_error('ABCDEFGH12345');
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_escape_string ----

# string
echo 'Testing mysql_escape_string return value...';
$r = mysql_escape_string('Testing');
if (!is_string($r)) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_fetch_array ----

# run query
$temp = mysqli_query(mysql_ensure_link($link), 'SHOW DATABASES');

# true
echo 'Testing mysql_fetch_array return value...';
$r = mysql_fetch_array($temp);
if (!is_array($r)) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_fetch_array error return value...';
$r = @mysql_fetch_array('INVALID_LINK');
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_fetch_assoc ----

# run query
$temp = mysqli_query(mysql_ensure_link($link), 'SHOW DATABASES');

# true
echo 'Testing mysql_fetch_assoc return value...';
$r = mysql_fetch_assoc($temp);
if (!is_array($r)) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_fetch_assoc error return value...';
$r = @mysql_fetch_assoc('INVALID_LINK');
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_fetch_field ----

# run query
$temp = mysqli_query(mysql_ensure_link($link), 'SHOW DATABASES');

# true
echo 'Testing mysql_fetch_field return value...';
$r = mysql_fetch_field($temp);
if (!is_object($r)) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_fetch_field error return value...';
$r = @mysql_fetch_field('INVALID_LINK');
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_fetch_lengths ----

# run query
$temp = mysqli_query(mysql_ensure_link($link), 'SHOW DATABASES');
mysql_fetch_assoc($temp); # must be done

# true
echo 'Testing mysql_fetch_lengths return value...';
$r = mysql_fetch_lengths($temp);
if (!is_array($r)) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_fetch_lengths error return value...';
$r = @mysql_fetch_lengths('INVALID_LINK');
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_fetch_object ---

# run query
$temp = mysqli_query(mysql_ensure_link($link), 'SHOW DATABASES');

# true
echo 'Testing mysql_fetch_object return value...';
$r = mysql_fetch_object($temp);
if (!is_object($r)) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_fetch_object error return value...';
$r = @mysql_fetch_object('INVALID_LINK');
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_fetch_row ---

# run query
$temp = mysqli_query(mysql_ensure_link($link), 'SHOW DATABASES');

# true
echo 'Testing mysql_fetch_row return value...';
$r = mysql_fetch_row($temp);
if (!is_array($r)) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_fetch_row error return value...';
$r = @mysql_fetch_row('INVALID_LINK');
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";


# --- mysql_field_flags ---

# preps
$temp = @mysql_query('CREATE TABLE testtable(id INT NOT NULL PRIMARY KEY AUTO_INCREMENT)', $link);
$temp = mysql_query('INSERT INTO testtable (testcolumn) VALUES("testing")', $link);
$temp = mysql_query('SELECT * FROM testtable', $link);


# true
echo 'Testing mysql_field_flags return value...';
$r = mysql_field_flags($temp, 0);

if (!is_numeric($r) && !is_string($r)) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_field_flags error return value...';
$r = @mysql_field_flags('INVALID_LINK', 0);
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_field_len ---

# run query
$temp = @mysqli_query(mysql_ensure_link($link), 'CREATE TABLE testtable(id INT NOT NULL PRIMARY KEY AUTO_INCREMENT)');
$temp = mysqli_query(mysql_ensure_link($link), 'SELECT * FROM testtable');
# true
echo 'Testing mysql_field_len return value...';
$r = mysql_field_len($temp, 0);

if (!is_numeric($r)) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_field_len error return value...';
$r = @mysql_field_len('INVALID_LINK', 0);
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_field_name ---

# run query
$temp = @mysqli_query(mysql_ensure_link($link), 'CREATE TABLE testtable(id INT NOT NULL PRIMARY KEY AUTO_INCREMENT)');
$temp = mysqli_query(mysql_ensure_link($link), 'SELECT * FROM testtable');
# true
echo 'Testing mysql_field_name return value...';
$r = mysql_field_name($temp, 0);

if (!is_string($r)) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_field_name error return value...';
$r = @mysql_field_name('INVALID_LINK', 0);
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_field_seek ---

# run query
$temp = @mysqli_query(mysql_ensure_link($link), 'CREATE TABLE testtable(id INT NOT NULL PRIMARY KEY AUTO_INCREMENT)');
$temp = mysqli_query(mysql_ensure_link($link), 'SELECT * FROM testtable');
# true
echo 'Testing mysql_field_seek return value...';
$r = mysql_field_seek($temp, 0);

if ($r !== true) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_field_seek error return value...';
$r = @mysql_field_seek('INVALID_LINK', 0);
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_field_table ---

# run query
$temp = @mysqli_query(mysql_ensure_link($link), 'CREATE TABLE testtable(id INT NOT NULL PRIMARY KEY AUTO_INCREMENT)');
$temp = mysqli_query(mysql_ensure_link($link), 'SELECT * FROM testtable');
# true
echo 'Testing mysql_field_table return value...';
$r = mysql_field_table($temp, 0);

if (!is_string($r)) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_field_table error return value...';
$r = @mysql_field_table('INVALID_LINK', 0);
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# run query
$temp = @mysqli_query(mysql_ensure_link($link), 'CREATE TABLE testtable(id INT NOT NULL PRIMARY KEY AUTO_INCREMENT)');
$temp = mysqli_query(mysql_ensure_link($link), 'SELECT * FROM testtable');
# true
echo 'Testing mysql_field_type return value...';
$r = mysql_field_type($temp, 0);

if (!is_string($r)) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_field_type error return value...';
$r = @mysql_field_type('INVALID_LINK', 0);
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_free_result ---

# run query
$temp = @mysqli_query(mysql_ensure_link($link), 'CREATE TABLE testtable(id INT NOT NULL PRIMARY KEY AUTO_INCREMENT)');
$temp = mysqli_query(mysql_ensure_link($link), 'SELECT * FROM testtable');
# true
echo 'Testing mysql_free_result return value...';
$r = mysql_free_result($temp);

if ($r !== true) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# is not able to make this function fail
# false
# echo 'Testing mysql_free_result error return value...';
# $r = @mysql_free_result(999191);
# if ($r !== false) {
# 	die('FAIL, invalid return value: '.var_export($r, true)."\n");
# }
# echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_get_client_info ---

# string
echo 'Testing mysql_get_client_info return value...';
$r = mysql_get_client_info();

if (!is_string($r)) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_get_host_info ----

# true
echo 'Testing mysql_get_host_info return value...';
$r = mysql_get_host_info($link);
if (!is_string($r)) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_get_host_info error return value...';
$r = @mysql_get_host_info('INVALID_LINK');
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_get_proto_info ----

# true
echo 'Testing mysql_get_proto_info return value...';
$r = mysql_get_proto_info($link);
if (!is_numeric($r)) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_get_proto_info error return value...';
$r = @mysql_get_proto_info('INVALID_LINK');
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_get_server_info ----

# true
echo 'Testing mysql_get_server_info return value...';
$r = mysql_get_server_info($link);
if (!is_string($r)) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_get_server_info error return value...';
$r = @mysql_get_server_info('INVALID_LINK');
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_info ----

# run query
$temp = @mysqli_query(mysql_ensure_link($link), 'DROP TABLE testtable');
$temp = @mysqli_query(mysql_ensure_link($link), 'CREATE TABLE testtable(id INT NOT NULL PRIMARY KEY AUTO_INCREMENT)');
$temp = mysqli_query(mysql_ensure_link($link), 'ALTER TABLE testtable ADD COLUMN testcolumn INT NOT NULL');

# true
echo 'Testing mysql_info return value...';
$r = mysql_info($link);
if (!is_string($r)) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_info error return value...';
$r = @mysql_info('INVALID_LINK');
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_insert_id ---

# true
echo 'Testing mysql_insert_id return value...';
$r = mysql_insert_id($link);
if (!is_numeric($r)) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_insert_id error return value...';
$r = @mysql_insert_id('INVALID_LINK');
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_list_dbs ----

# false
echo 'Testing mysql_list_dbs error return value...';
$r = @mysql_list_dbs('INVALID_LINK');
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# a resource
echo 'Testing mysql_list_dbs return value...';
$r = mysql_list_dbs($link);
if (!is_resource($r) && !is_object($r)) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_list_fields ---

# a resource
echo 'Testing mysql_list_fields return value...';
$r = mysql_list_fields($database, 'testtable', $link);
if (!is_resource($r) && !is_object($r)) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

 #false
echo 'Testing mysql_list_fields error return value...';
$r = @mysql_list_fields($database, 'doesnotexist', $link);
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_list_processes ---

# a resource
echo 'Testing mysql_list_processes return value...';
$r = mysql_list_processes($link);
if (!is_resource($r) && !is_object($r)) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

 #false
echo 'Testing mysql_list_processes error return value...';
$r = @mysql_list_processes('INVALID_LINK');
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";


# --- mysql_list_tables ----

# false
echo 'Testing mysql_list_tables error return value...';
$r = @mysql_list_tables('doesnotexist12345', $link);
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# a resource or an object
echo 'Testing mysql_list_tables return value...';
$r = mysql_list_tables($database, $link);
if (!is_resource($r) && !is_object($r)) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_num_fields ---

$r = mysqli_query(mysql_ensure_link($link), 'SELECT * FROM testtable');

# an integer
echo 'Testing mysql_num_fields return value...';
$r = mysql_num_fields($r);
if (!is_numeric($r)) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_num_fields error return value...';
$r = @mysql_num_fields(false);
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";



# --- mysql_num_rows ---

echo 'Testing mysql_num_rows return value...';
echo 'not implemented in test'."\n";
echo 'Testing mysql_num_rows error return value...';
echo 'not implemented in test'."\n";

# --- mysql_pconnect ---

echo 'Testing mysql_pconnect return value...';
echo 'not implemented in test'."\n";
echo 'Testing mysql_pconnect error return value...';
echo 'not implemented in test'."\n";

# --- mysql_ping ----

# true
echo 'Testing mysql_ping return value...';
$r = mysql_ping($link);
if ($r !== true) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_ping error return value...';
$r = @mysql_ping('ABCDEFGH12345');
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_query ---

echo 'Testing mysql_query return value...';
echo 'not implemented in test'."\n";
echo 'Testing mysql_query error return value...';
echo 'not implemented in test'."\n";

# --- mysql_real_escape_string ---

echo 'Testing mysql_real_escape_string return value...';
echo 'not implemented in test'."\n";
echo 'Testing mysql_real_escape_string error return value...';
echo 'not implemented in test'."\n";

# --- mysql_result ---

echo 'Testing mysql_result return value...';
echo 'not implemented in test'."\n";
echo 'Testing mysql_result error return value...';
echo 'not implemented in test'."\n";

# --- mysql_select_db ----

# false
echo 'Testing mysql_select_db error return value...';
$r = @mysql_select_db('12345doesnotexist', $link);
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# true
echo 'Testing mysql_select_db return value...';
$r = mysql_select_db($database, $link);
if ($r !== true) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";


# --- mysql_set_charset ---
# true
echo 'Testing mysql_set_charset return value...';
$r = mysql_set_charset('utf8', $link);
if ($r !== true) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_set_charset error return value...';
$r = @mysql_set_charset('doesnotexist', $link);
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_stat ---

# true
echo 'Testing mysql_stat return value...';
$r = mysql_stat($link);
if (!is_string($r)) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_stat error return value...';
$r = @mysql_stat('INVALID_LINK');
if ($r !== null) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_tablename ---

# string
echo 'Testing mysql_tablename return value...';
$r = mysql_tablename(mysql_list_tables($database, $link), 0);
if (!is_string($r)) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_tablename error return value...';
$r = @mysql_tablename(mysql_list_tables($database, $link), 99999);
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# --- mysql_thread_id ---

# true
echo 'Testing mysql_thread_id return value...';
$r = mysql_thread_id($link);
if (!is_numeric($r)) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";

# false
echo 'Testing mysql_thread_id error return value...';
$r = @mysql_thread_id('INVALID_LINK');
if ($r !== false) {
	die('FAIL, invalid return value: '.var_export($r, true)."\n");
}
echo '['.gettype($r).'] '.(!is_object($r) ? (!is_array($r) ? var_export($r, true) : 'array') : 'object').' = OK'."\n";


# --- end of test

@mysql_drop_db($database, $link);
@mysql_close($link);

echo 'Test duration '.(microtime(true) - $time_start).' seconds'."\n";
echo 'Test completed without errors '.date('Y-m-d H:i:s')."\n";
/*

function is_mysqli_or_resource($r) {
function is_mysql_resource($r) {
function is_generic_resource($r) {
function is_mysql_resource_old($result) {
function mysql_ensure_link($link_identifier) {


function mysql_affected_rows($link_identifier = NULL) {
--- function mysql_client_encoding($link_identifier = NULL) {
--- function mysql_close($link = NULL) {
--- function mysql_connect($server = MYSQL_DEFAULT_HOST, $username = MYSQL_DEFAULT_USER, $password = MYSQL_DEFAULT_PASSWORD, $new_link = false, $client_flags = 0) {
--- function mysql_create_db($database_name, $link_identifier = NULL) {
--- function mysql_data_seek($result , $row_number) {
--- function mysql_db_name($result , $row, $field = NULL) {
--- function mysql_db_query($database, $query, $link_identifier = NULL) {
--- function mysql_drop_db($database_name, $link_identifier = NULL) {
--- function mysql_errno($link_identifier = NULL) {
--- function mysql_error($link_identifier = NULL) {
--- function mysql_escape_string($unescaped_string) {
--- function mysql_fetch_array($result, $result_type = MYSQL_BOTH) {
--- function mysql_fetch_assoc ($result) {
--- function mysql_fetch_field($result, $field_offset = NULL) {
--- function mysql_fetch_lengths($result) {
--- function mysql_fetch_object ($result, $class_name=NULL, $params=NULL) {
--- function mysql_fetch_row ($result) {
--- function mysql_field_flags($result, $field_offset) {
--- function mysql_field_len($result, $field_offset) {
--- function mysql_field_name($result, $field_offset) {
--- function mysql_field_seek($result, $field_offset) {
--- function mysql_field_table($result, $field_offset) {
--- function mysql_field_type($result, $field_offset) {
--- function mysql_free_result($result) {
--- function mysql_get_client_info() {
--- function mysql_get_host_info ($link_identifier = NULL) {
--- function mysql_get_proto_info($link_identifier = NULL) {
--- function mysql_get_server_info($link_identifier = NULL) {
--- function mysql_info($link_identifier = NULL) {
function mysql_insert_id($link_identifier = NULL) {
--- function mysql_list_dbs ($link_identifier = NULL) {
function mysql_list_fields ($database_name, $table_name, $link_identifier = NULL) {
function mysql_list_processes($link_identifier = NULL) {
--- function mysql_list_tables ($database_name, $table_name, $link_identifier = NULL) {
function mysql_num_fields ($result) {
function mysql_num_rows($result) {
function mysql_pconnect($server = MYSQL_DEFAULT_HOST, $username = MYSQL_DEFAULT_USER, $password = MYSQL_DEFAULT_PASSWORD, $client_flags = 0) {
--- function mysql_ping($link_identifier = NULL) {
function mysql_query ($query, $link_identifier = NULL) {
function mysql_real_escape_string($unescaped_string, $link_identifier = NULL) {
function mysql_result($result , $row , $field = 0) {
--- function mysql_select_db ($database_name, $link_identifier = NULL) {
function mysql_set_charset($charset, $link_identifier = NULL) {
function mysql_stat($link_identifier = NULL) {
function mysql_tablename ($result, $i) {
function mysql_thread_id($link_identifier = NULL) {*/





?>
