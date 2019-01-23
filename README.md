PHP MySQL to MySQLi migration shim library

* Page contents

  * Purpose - The purpose of the library
  * Project goal - The goal of the project
  * Problem - The problem the library solves
  * Usage examples
  * How the library works
  * Good - benefits, features and things you get in return when using the library
  * Bad - pitfalls, problems and things to take in to consideration when using the library
  * Usage - How to use it
  * Minified version
  * Testing - How to test the library before you use it in critical situations
  * License - Information about the license for the library and the testing script
  * Sites and projects in the world using the library - Proof that it is in use
  * Contributors - People that have helped the development
  * Contact - Where to send mails with error reports, usage information and comments
  * Downloads - Links to download the library and the testing script

* Purpose

  Seamlessly redefines deprecated or missing mysql_ functions and MYSQL_ constants and calls the corresponding mysqli_ functions for PHP 5.5+ and PHP 7+.
  Converts PHP MySQL function calls to PHP MySQLi function calls.

* Project goal

  The goal is to have a library that emulates ALL the functions and that returns the SAME data as the original mysql_ versions.

  Please share and improve, mail me if you have improved it and like to share your work here.

* Problem

  As of PHP 5.5 the original mysql extension for PHP and its provided procedural functions for PHP are marked as deprecated and are removed in PHP 7.0.0.

  Websites and other PHP enabled projects that use these functions (like mysql_connect, mysql_query etc.) may not work properly after this change in PHP. This because the required functions now trigger an E_DEPRECATED error in PHP 5.5 and in PHP 7.0.0 even cause fatal errors as the functions do not exist anymore, breaking the bridge between your code and your database.

  * Solutions

    * Change ALL the mysql_ functions to their corresponding mysqli_ function. Not all mysql_ functions have mysqli_ versions and if they do, the syntax is often changed. Arguments are swapped, so mysql_example(argument1, argument2) may now look like mysqli_example(argument2, argument1) or even mysqli_example(argument2, argument1, argument3).
    * Run automatic conversion tool on the source that do the change. I have not tried this.
    * Disable the mysql extension, write a migration library that mimics the mysql_ functions and calls mysqli_ functions. This is what this page and the library is about.

* Usage examples

  * You have a web site that relies on the MySQL functions and want it to run on modern versions of PHP - this library should do it.

  * Your web host is missing the MySQL functions that you built your site with - this library should fill that gap, providing they have MySQLi support.

  * You don't want to learn the new MySQLi functions and want to continue using the traditional MySQL functions - include the library and you can continue like it was 2001.

  * You want to speed up a web site that uses the MySQL functions by tricking it to run MySQLi functions without knowing it - this library will boost performance by running your MySQL project on MySQLi functions.

  * You want to convert a MySQL function enabled site or project to use MySQLi functions instead - include this library and tear it down piece by piece while converting your project.

  * You want to switch storage solution in a project from using (PHP) MySQL (functions) to something else like SQlite - include and build upon this library, the original functions that your project expects to exist are defined.

* How the library works

  If the mysql extension is missing but the mysqli extension is available, then it defines new mysql_ functions that executes the corresponding mysqli_ functions.

  If the original mysql extension is there, then not much is done, as all is fine and dandy (except for E_DEPRECATED warnings, which must be neglected manually).

  If none of the mysql or the mysqli extensions are loaded, then it dies, halting with an error.

  The library defines many mysql_ functions that do not have any corresponding mysqli_ alternative, by combining mysqli_ functionality or by fiddling in other ways.

  * Good - benefits, features and things you get in return

    You save time by not needing to change the code base just because PHP decided to change its functions and can continue with more important tasks.

    MySQLi extension is faster than MySQL extension, so your applications may be faster too.

    It is tested by others and a testing script is available to test it yourself. Sites and projects already using the library as a proof of that the library is working may be found at the bottom of this page.

    With this library you can continue to use the old mysql_ functions without caring about learning the new mysqli_ functions.

    The library can be edited to monitor and modify database data at a level you could not reach so easily before. For example, you could edit the mysql_query() shim function in the library to write all executed queries to a log file, or neglect all UPDATE, INSERT and DELETE queries or forward them to another server.

    It is open source and the license is MIT license.

  * Bad - pitfalls, problems and things to take in to consideration

    * mysql passes resources but mysqli passes objects, so comparisons done in your source with is_resource() may no longer work. Example:

    <?php
      # mysql
      if (is_resource($result)) {...}
      # mysqli
      if (is_object($result)) {...}
    ?>


    This is an fairly easy search-and-replace operation if you compare to fix up all mysql_ functions to mysqli_ functions.

    To ease this even more and make your code backward compatible I have bundled a is_resource_or_mysqli() function that checks if mysql is loaded, then looks for a (mysql) resource, or if mysqli is loaded then it looks for a mysqli object - or if none, then responds with false. You replace is_resource() with is_resource_or_mysqli().

    * Resources and objects are not the same, so if you do exotic stuff with your resources, then you may have more to edit.

    Please note. MySQL is not the only thing in PHP that work with resource type variables. File handles are also resources, therefore is_resource_or_mysqli() does not explicitly return true on MySQL resources but all kind of resources, like is_resource(). As this is a MySQL to MySQLi shim primarly is_resource_or_mysqli only returns true on MySQLi objects and not generic objects.

    On Unix/Linux platforms you may replace is_resource more effectively using find and sed in a shell (after taking a backup):

      find /home/user/public_html -type f -iname \*.php \
        -exec sed -i 's/is_resource/is_resource_or_mysqli/g' {} \;

    This library integrates at a critical level if you care about your database data. It jumps in between your application and the MySQL(i) extension and translates in both ways. I and the contributors have tried to make the library as correct and accurate as possible, but errors may exist. In a worst case scenario you may loose your data, although that is very unlikely.

    * More testing is encouraged, although the library has been used and therefore tested by the contributors and there is also a testing script to test the library before you use it in critical systems.

    * If you want to get rid of the E_DEPRECATED, you have some options:

    Make PHP ignore E_DEPRECATED warnings by suppressing them. May be done through php.ini or at runtime.

    Make PHP not to load the mysql extension, by commenting out the extension=mysql.so / extension=php_mysql.dll line in the php.ini file. Note for Ubuntu (~13.04) users: Even if you installed php5-mysql the extension is possible to comment out, check out the /etc/php5/mods-available/mysql.ini file).

    * Some functions may be missing, and some may (but should not) return data that is not equal to the original mysql_ versions.

* Usage

  The usage is targeted to be simple. In short: download the library to your source directory, disable the MySQL extension, make sure the MySQLi extension is there in it's place and then include the library in the top of your source code.

  Then continue with your PHP project as if PHP never dropped their MySQL functions.

  Disable the MySQL extension

  For PHP below version 7.0.0 you need to disable the MySQL extension in PHP - if it is enabled. To check if it is enabled, put this into a .php file and browse to it with a web browser, then check for the MySQL extension on the page:

  <?php echo phpinfo(); ?>

  To disable MySQL extension in Debian Jessie 8.3 (Linux) for example you can go to /etc/php5/mods-available with a root terminal and rename the file mysql.ini to mysql.ini.OFF

  On other systems you can edit the php.ini file and comment out the line:

  extension=mysql.so
  So it looks like this:

  ; extension=mysql.so
  Make sure that the MySQLi extension is enabled and working too - it should show up in phpinfo(); too.

* Usage of the library

  The library is a standard .php-file that you include into the top of the source:

  <?php require_once("mysql-shim/mysql-shim.php"); ?>
  You may place the file in a directory covered by the global include paths stated in the php.ini file:

    include_path = ".:/path/to/directory/where/mysql-shim.php/is/located:/usr/lib/php"

  This way you only need one copy of the library located in one of the directories in the include_path. You still need to include it though, using require_once() as stated above.

  Alternative usage - auto_prepend

  You don't need to explicitly include the library in each script (when you're working with a large codebase, and possibly one you've not written yourself this can be something of a problem). An alternative solution is use an auto_prepend file.

  In php.ini:

    auto_prepend_file=/path/to/mysql-shim.php

  or in apache's httpd.conf or .htaccess:

    php_value auto_prepend_file /path/to/mysql-shim.php

  Alternative usage - rename functions

  If do not have write access to the PHP configuration on the server or something else prevents you from disabling the MySQL extension then you do not have so many choices but to rename all the mysql_ functions in the library and in your code base - or rewrite the code base to use the new mysqli_ functions.

  If the code base is not too big you could replace "mysql_" with "mysql_shim_" for example, to avoid name collision between the functions. You do also need to rename the MYSQL_ constants.

* Minified version

  Tony Russo has contributed with a minifier script that generates a minified version of the shim library.

  The reason for this is the difference in footprint, the minified version is about 11 kB while the uncompressed is about 37 kB due to the large amount of comments.

  The minified version may benefit from faster loading because of all the comments and line breaks. The minifier script is also included to generate new minified versions.

  Run the minifier script to generate a minified version of the shim library based on the uncompressed version.

* Testing

  As of february 2016 I have written a testing script to test the library. It walks through the functions in the library and runs them to check if the return values and the return values upon errors are correct according to PHP.net manual.

  Note that this script does live tests - it creates databases, tables, table rows, deletes databases and so on. The same words for the library goes for the testing script - it has been developed to be safe to use, but errors may exist. So if you want to be really safe then run the test file on a database server containing data you don't mind losing.

  You are of course welcome to test the library yourself and also to modify both the library and the testing script. Please send me an e-mail if you find anything interesting - like errors. Below are usage instructions for the testing script.

* Usage of the testing script

  Place the testing script in the same directory as the library (but not in an outside world-accessible directory just to be safe)

  Make sure that the MySQL extension is disabled and that the MySQLi extension is enabled

  Go to the directory when you placed the testing script and the library with a terminal/console

  Run it with PHP, replace credential placeholders with root username and password, or an MySQL user that has the same MySQL privileges:
  /usr/bin/php mysql-shim.test.php -h localhost -u mysql-root-username -p mysql-root-password

  If it works it runs through all the functions and does live tests.

* Parameters for the testing script

  You may pass the following parameters to the testing script:

  -d "database" (optional, defaults to testdatabase12345, to be created and deleted)

  -f to drop database if it exists

  -H "hostname" (optional, defaults to localhost)

  -h or --help show this help

  -m to test the minified version

  -p "password" (optional, defaults to an empty string)

  -u "username" (optional, defaults to root)

  -y to continue without confirmation

  -i to skip shim library even if present in directory

* License

  See the LICENSE file for the current license.

  The current license is MIT license since 2018-06-06.

  Previous versions before 2018-06-06 was public domain.

  Contain comments from PHP.net that may rule under different licenses. I do not take any responsibility and I am not liable for any damage caused through use of the code.

* Sites and projects in the world using the library

  * dotpointer - My home page, Sweden
  * blumen maarsen - A web shop, Switzerland
  * u5CMS Content Management System - A CMS, Switzerland

  Are you using the library? Drop me a mail with the address to your project and I may include it in the list. You may also supply a short comment to publish if you like to.

* Contributors

  * Robert Klebe, dotpointer - Author of initial version and maintainer
  * marc17, GitHub - Improved mysql_result()
  * Colin McKinnon - Find and sed replacements, improved is_resource_or_mysqli() and auto_prepend suggestions
  * Yaff Are - Improved error return values
  * Tony Russo - Noted mysql_unbuffered_query() missing and reported bug in testing script
  * zacware, GitHub - Improved mysql_result() and added mysql_selectdb()
  * Checkout contributors at GitLab, please send me mail if you would like to contribute

* Contact

  You may send me a letter using: dotpointer (put an at sign here) gmail (put a dot here) com.

* Download

  The project can be found on GitLab, download and install git, then clone the project, for example in Debian:

    apt-get install git

    git clone https://gitlab.com/dotpointer/mysql-shim.git

  Or visit the web page and download the project in a ZIP file:

    https://gitlab.com/dotpointer/mysql-shim

