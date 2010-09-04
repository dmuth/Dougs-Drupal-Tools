#!/usr/bin/env php
<?php
/**
*
* Drupal Backup script.
*
* Copyright 2010, Douglas Muth (http://www.dmuth.org/)
*
* This program is released under the terms of the the GNU Public License (GPL)
*
* This script performs a backup of Drupal's document_root directory and the
* database that it uses.  At this time, it is a standalone utility, but 
* will eventually be included with the Drupal 5 rewrite of my backup module.
* In the meantime, I have decided to make this standalong utility available.
*
* Pre-condition:  This script should be run from the document_root directory.
* Post-condition: A backup file will be written to document_root.
*	For those with security concerns, a random number is added to the 
*	filename to foil those attackers who would try to guess filenames.
*/


/**
* Get our current directory.
* This function was writtent to account for having a symlink as a parent
* directory.  Unfortunately, getcwd() will dereference the symlink, which
* I don't want when trying to find settings.php.
*
* @return string The full path to the current directory.
*/
function getcwd_local() {

	$retval = shell_exec("pwd");
	$retval = rtrim($retval);

	if ($retval == false) {
		$error = "system() call to 'pwd' failed!";
		throw new Exception($error);
	}

	return($retval);

} // End of getcwd_local()


/**
* Get our parent directory
* This makes use of getcwd_local(), and manually chops off the last 
* directory to again deal with symlink issues.
*
* @param string $dir A directory
*
* @return string The parent directory.
*/
function get_parent_dir($dir) {
	
	$retval = $dir;

	//
	// We're already at the top of the tree.
	//
	if ($retval == "/") {
		return($retval);
	}

	//
	// Remove a trailing slash
	//
	$retval = preg_replace("|/$|", "", $retval);

	//
	// Remove the last directory
	//
	$retval = preg_replace("|/[^/]+$|", "", $retval);

	if ($retval == "") {
		$retval = "/";
	}

	return($retval);

} // End of get_parent_dir()


/**
* Load our settings file so that we can get database credentials.
*
* @return string The URL to access the database.
*/
function load_settings() {

	$retval = "";

	//
	// Load the settings, parse the database URL, and remove the leading 
	// glash in the database name.
	//
	$file = "sites/default/settings.php";
	$target_file = "";

	//
	// Loop until we found a valid settings file, going into the parent
	// directory on each pass.
	//
	// A postitive side effect of this loop is that we'll be in Drupal's root 
	// directory, so the backup file will go there.
	//
	$dir = getcwd_local();

	while ($dir != "/") {

		$target_file = $dir . "/" . $file;
		//print "Target file: $target_file\n"; // Debugging

		if (is_file($target_file)) {
			break;
		}

		$dir = get_parent_dir($dir);
		if (!chdir($dir)) {
			$error = "chdir() failed. (current dir: $dir)";
			return($error);
		}

	} // End of while()

	//
	// If we can't find the target file, complain.
	//
	if (!is_file($target_file)) {
		$error = "Unable to find settings.php file. Are you under "
			. "the DOCUMENT_ROOT?";
		throw new Exception($error);
	}

	require_once($file);

	$retval = $db_url;

	return($retval);

} // End of load_settings()


/**
* Back up the database
*
* @param string $db_url The database URL.
*
* @return array An associative array of the database backup file and date string
*/
function backup_db($db_url) {

	$retval = array();

	$db_data = parse_url($db_url);
	$db_data["path"][0] = " ";

	//
	// Create our date string for filenames
	//
	$date_string = date("YmdHis");
	$retval["date_string"] = $date_string;

	//
	// Create our database temp file
	//
	$db_file_tmp = tempnam("/tmp", "backup-db-");

	//
	// The name of our final db file.
	// This includes a six digit random number to keep attackers from
	// guessing the filename.
	//
	$db_file = "backup-db-" . $date_string 
		. "-" . mt_rand(100000, 999999) . ".txt.gz";

	if (empty($db_file_tmp)) {
		$error = "call to tempnam() failed";
		throw new Exception($error);
	}

	//
	// Now dump our database to the temp file
	//
	$cmd = "mysqldump -u " . $db_data["user"]
		. " -h "  . $db_data["host"]
		. " -p"  . $db_data["pass"]
		. " "  . $db_data["path"]
		. " |gzip >$db_file_tmp "
		;

	//print "Debug: $cmd\n"; // Debugging
	print "Backing up database...\n";
	$fp = popen($cmd, "r");

	if (empty($fp)) {
		$error = "Unable to run command '$cmd'";
		throw new Exception($error);
	}

	$cmd_retval = pclose($fp);

	if ($cmd_retval != 0) {
		$error = "Command '$cmd' returned value: $cmd_retval";
		throw new Exception($error);
	}

	print "Done!\n";

	//
	// Move the temp file into the current directory (with a new name)
	// so that it can be included in the tarball.
	//
	if (!rename($db_file_tmp, $db_file)) {
		$error = "Renaming file '$db_file_tmp' to '$db_file' failed";
		throw new Exception($error);
	}

	//print "Debug: Renamed '$db_file_tmp' to '$db_file\n"; // Debugging

	$retval["db_file"] = $db_file;
	return($retval);

} // End of backup_db()


/**
* Get a list of all files in the current directory, filter out the current 
* and parent directories, and any existing backup files.  Trying to make 
* GNU tar's --exclude switch actually work for me was like trying to herd 
* housecats that were hopped up on crack.  It just wasn't going to happen. :-)
* Plus, this approach is more portable.
*
* @return string A list of files and directories in the Drupal root to 
*	back up.
*/
function get_file_list() {

	$retval = "";

	$fp = opendir(".");

	while ($file = readdir($fp)) {
		//
		// Skip the current and parent directory
		//
		if ($file == "." || $file == "..") {
			continue;
		}

		//
		// Skip any backup files
		//
		if (strstr($file, "backup-")) {
			print "Skipping file '$file'\n";
			continue;
		}

		if ($file == "filecache") {
			print "Skipping directory '$file'\n";
			continue;
		}

		//
		// Don't back up Git.
		//
		if ($file == ".git") {
			print "Skipping directory '$file'\n";
			continue;
		}

		//
		// Okay, add this file onto the list.
		//
		$retval .= $file . " ";

	}

	if (!$fp) {
		$error = "Unable to open current directory";
		throw new Exception($error);
	}

	closedir($fp);

	return($retval);

} // End of get_file_list()


/**
* Do the backup of our file system under the Drupal root.
*
* @param string $date_string The string of the current date.  This is passed
*	in, since we use the same string as we did for our database backup.
*	Yes you Drupal purists, I know I could have created a function called
*	get_date_string() or similar to create the string on the first call, store
*	it in a static variable, and return that static variable on subsequent
*	calls.  But I just didn't see the point since there's only 2 functions
*	involved.  And now I just wasted like 5 lines of comments. :-P
*
* @param string $file_list The list of files to back up.
*
* @param string $db_file The name of the database file.
*
* @return null
*/
function backup_files($date_string, $file_list, $db_file) {

	//
	// Now tar up the contents of this directory
	//
	$backup_tmp = tempnam("/tmp", "backup-htdocs-");

	//
	// This includes a six digit random number to keep attackers from
	// guessing the filename.
	//
	$backup_file = "backup-" . $date_string 
		. "-" . mt_rand(100000, 999999) . ".tar.gz";

	$cmd = "tar cfzh $backup_tmp $file_list 2>&1";

	print "Backing up filesystem...\n";
	$fp = popen($cmd, "r");

	if (empty($fp)) {
		$error = "Unable to run command '$cmd'";
		throw new Exception($error);
	}

	while ($line = fgets($fp)) {
		print "tar output: $line";
	}

	$retval = pclose($fp);

	//
	// Sometimes there might be issues with symlinks that point nowhere,
	// thanks to the way my deployment system for templates works.
	//
	if ($retval != 0) {
		$error = "Command '$cmd' returned value: $retval";
		print "WARNING: $error\n";
		//throw new Exception($error);
	}

	print "Done!\n";

	//
	// Now remove the database file, we don't need it anymore.
	//
	if (!@unlink($db_file)) {
		$error = "Unable to delete file '$db_file'";
		throw new Exception($error);
	}

	//
	// Finally, move the tarball into this directory so the user can grab it
	//
	if (!rename($backup_tmp, $backup_file)) {
		$error = "Renaming file '$backup_tmp' to '$backup_file' failed";
		throw new Exception($error);
	}

	//
	// Make our backup file world-readable.
	//
	if (!chmod($backup_file, 0644)) {
		$error = "chmod() failed";
		throw new Exception($error);
	}

	$message = "Backup written to '$backup_file' in Drupal root.\n";
	print $message;
 
	return(null);

} // End of backup_files()


/**
* This is the main function which does all of our work.
*
* @return NULL
*/
function main() {

	if (php_sapi_name() != "cli") {
		$error = "Don't run this from the web interface.";
		throw new Exception($error);
	}

	$db_url = load_settings();

	//
	// Backup our database
	//
	$db_data = backup_db($db_url);
	$db_file = $db_data["db_file"];
	$date_string = $db_data["date_string"];

	//
	// Get our list of files to back up.
	//
	$file_list = get_file_list();

	//
	// Finally, add in our database backup.  It's excluded since it's
	// a backup file just in case there's multiple database dumps lying 
	// around from previous backups that were aborted.  But we want to
	// explicitly add in the dump from *this* run of backup.
	//
	$file_list .= $db_file;

	//
	// Now backup all of our files
	//
	backup_files($date_string, $file_list, $db_file);

} // End of main()

main(); 

