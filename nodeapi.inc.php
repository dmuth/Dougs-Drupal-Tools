<?php
/**
* Hooks for the nodeapi event.
*
* @author Douglas Muth <http://www.dmuth.org/>
*
*/


/**
* This function is called whenever a node is created, changed, etc.
*/
function ddt_nodeapi($node, $op) {

	//print_r($node); // Debugging

	//
	// We only care about changes to the post
	//
	if ($op != "delete" && $op != "delete revision"
		&& $op != "insert" && $op != "update") {
		return(null);
	}

	$message = t("Op: %op on NID %nid ")
		. "(" 
			. t("Topic: %topic, ")
			. t("Published?: %published, ")
			. t("Promoted?: %promoted, ")
			. t("Sticky?: %sticky, ")
			. t("Comments allowed?: %comments") . 
		")";
	$var = array();
	$var["%nid"] = $node->nid;
	$var["%op"] = $op;
	$var["%topic"] = $node->title;
	$var["%published"] = $node->status;
	$var["%promoted"] = $node->promote;
	$var["%sticky"] = $node->sticky;
	$var["%comments"] = $node->comment;
	$link = l("view", "node/" . $node->nid);

	ddt_log($message, $var, "", $link);

} // End of ddt_nodeapi()





