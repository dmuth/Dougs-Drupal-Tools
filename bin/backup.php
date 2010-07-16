#!/usr/bin/env php
<?php
/**
*
* Drupal Backup 1.0.
*
* Copyright 2007, Douglas Muth (http://www.claws-and-paws.com/)
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
* This is the main function which does all of our work.
*
* @return mixed NULL is returned on success, otherwise an error is returned.
* 	This allows us to "catch" errors and let them bubble up the call stack,
*	not unlike exceptions in PHP 5.
*/
function main() {

	if (php_sapi_name() != "cli") {
		$error = "Don't run this from the web interface.";
		return($error);
	}

	//
	// Load the settings, parse the database URL, and remove the leading 
	// glash in the database name.
	//
	$file = "sites/default/settings.php";
	//chdir("..");
	if (!is_file($file)) {
		$error = "Unable to load file '$file'.  Did you run this from "
			. "DOCUMENT_ROOT?";
		return($error);
	}
	require_once($file);

	$db_data = parse_url($db_url);
	$db_data["path"][0] = " ";

	//
	// Create our date string for filenames
	//
	$date_string = date("YmdHis");

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
		return($error);
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

	print "Backing up database...\n";
	$fp = popen($cmd, "r");

	if (empty($fp)) {
		$error = "Unable to run command '$cmd'";
		return($error);
	}

	$retval = pclose($fp);

	if ($retval != 0) {
		$error = "Command '$cmd' returned value: $retval";
		return($error);
	}

	print "Done!\n";

	//
	// Move the temp file into the current directory (with a new name)
	// so that it can be included in the tarball.
	//
	if (!rename($db_file_tmp, $db_file)) {
		$error = "Renaming file '$db_file_tmp' to '$db_file' failed";
		return($error);
	}

	//
	// Get a list of all files in the current directory, filter out the 
	// current and parent directories, and any existing backup files.
	// Trying to make GNU tar's --exclude switch actually work for me
	// was like trying to herd housecats that were hopped up on crack.
	// It just wasn't going to happen. :-)  Plus, this approach is
	// more portable.
	//
	$file_list = "";

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

		$file_list .= $file . " ";

	}

	if (!$fp) {
		$error = "Unable to open current directory";
		return($error);
	}

	closedir($fp);

	//
	// Finally, add in our database backup.  It's excluded since it's
	// a backup file just in case there's multiple database dumps lying 
	// around from previous backups that were aborted.  But we want to
	// explicitly add in the dump from *this* run of backup.
	//
	$file_list .= $db_file;

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

	$cmd = "tar cfz $backup_tmp $file_list 2>&1";

	print "Backing up filesystem...\n";
	$fp = popen($cmd, "r");

	if (empty($fp)) {
		$error = "Unable to run command '$cmd'";
		return($error);
	}

	while ($line = fgets($fp)) {
		print "tar output: $line";
	}

	$retval = pclose($fp);

	if ($retval != 0) {
		$error = "Command '$cmd' returned value: $retval";
		return($error);
	}

	print "Done!\n";

	//
	// Now remove the database file, we don't need it anymore.
	//
	if (!@unlink($db_file)) {
		$error = "Unable to delete file '$db_file'";
		return($error);
	}

	//
	// Finally, move the tarball into this directory so the user can grab it
	//
	if (!rename($backup_tmp, $backup_file)) {
		$error = "Renaming file '$backup_tmp' to '$backup_file' failed";
		return($error);
	}

	//
	// Make our backup file world-readable.
	//
	if (!chmod($backup_file, 0644)) {
		$error = "chmod() failed";
		return($error);
	}
  
	// Assume success
	return(null);

} // End of main()


if ($error = main()) {
	print $argv[0] . ": Error: $error\n";
	exit (1);
}


?>
