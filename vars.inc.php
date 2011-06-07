<?php
/**
* Code to interact with our system variables.
*
* @author Douglas Muth <http://www.dmuth.org/>
*
*/


/**
* Compare our old and current variable arrays and see what changed.
*
* @param array $old_vars Our array of old values.
*
* @param array $vars Our array of current values
*
* @return array An associative array which holds new, deleted,
*	and changed variables.
*/
function ddt_get_changed_vars($old_vars, $vars) {

	$retval = array();
	$retval["new"] = array();
	$retval["deleted"] = array();
	$retval["changed"] = array();

	//
	// Look for new and changed values.
	//
	foreach ($vars as $key => $value) {

		if (!array_key_exists($key, $old_vars)) {
			$retval["new"][] = $key;

		} else {

			if (serialize($value) != serialize($old_vars[$key])) {
				$old_value = serialize($old_vars[$key]);
				$new_value = serialize($value);

				//
				// Cut off our serialized strings after a certain length
				// to keep from getting giant multi-megabyte strings 
				// that kill the browser.
				//
				$max_len = 1024;

				if (strlen($old_value) > $max_len) {
					$old_value = substr($old_value, 0, $max_len) 
						. t(" (truncated at !max chars)",
							array("!max" => $max_len))
					;
				}

				if (strlen($new_value) > $max_len) {
					$new_value = substr($new_value, 0, $max_len) 
						. t(" (truncated at !max chars)",
							array("!max" => $max_len))
					;
				}

				$retval["changed"][] = 
					t("Old value for '$key': ") . $old_value . ", "
					. t("New value for '$key': ") . $new_value
					;
			}

		}

	}

	foreach ($old_vars as $key => $value) {
		if (!array_key_exists($key, $vars)) {
			$retval["deleted"][] = $key;
		}
	}

	return($retval);

} // End of ddt_get_changed_vars()


/**
* Log our changed variables.
*
* @param array $fields Array of new, deleted, and changed variables.
*/
function ddt_log_changed_vars($fields) {

	if (!empty($fields["new"])) {
		$message = t("New variables: %vars",
			array("%vars" => join(", ", $fields["new"]))
			);
		ddt_log($message);
	}

	if (!empty($fields["deleted"])) {
		$message = t("Deleted variables: %vars",
			array("%vars" => join(", ", $fields["deleted"]))
			);
		ddt_log($message);
	}

	if (!empty($fields["changed"])) {
		$message = t("Changed variables: %vars",
			array("%vars" => join(", ", $fields["changed"]))
			);
		ddt_log($message);
	}

} // End of ddt_log_changed_vars()

/**
* Fetch our stored variables.
* 
* @return array Our array of stored variables.
*/
function ddt_get_vars() {

	$retval = array();

	$query = "SELECT variables FROM {ddt_variables} LIMIT 1";
	$cursor = db_query($query);
	$row = db_fetch_array($cursor);

	//
	// If we got a result, unserialize the string.
	//
	if (!empty($row)) {
		$retval = unserialize($row["variables"]);
	}

	return($retval);

} // End of ddt_get_vars()


/**
* Store our current variables in the ddt_variables table.
*
* @param array $vars Our array of variables.
*/
function ddt_put_vars($vars) {

	//
	// First, wipe out what's there.
	//
	$query = "TRUNCATE TABLE {ddt_variables}";
	db_query($query);

	//
	// Now serialize and store our current variables.
	//
	$vars = serialize($vars);
	$query = "INSERT INTO {ddt_variables} (variables) VALUES ('%s')";
	db_query($query, $vars);

} // End of ddt_put_vars()


