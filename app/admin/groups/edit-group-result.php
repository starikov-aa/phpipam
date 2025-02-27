<?php

/**
 * Script to display usermod result
 *************************************/


/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# check if site is demo
$User->is_demo();
# check maintaneance mode
$User->check_maintaneance_mode ();

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "group", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";


# remove users from this group if delete and remove group from sections
if($POST->action == "delete") {
	$Admin->remove_group_from_users($POST->g_id);
	$Admin->remove_group_from_sections($POST->g_id);
}
else {
	if(strlen($POST->g_name) < 2)										{ $Result->show("danger", _('Name must be at least 2 characters long')."!", true); }
}

# unique name
if($POST->action=="add") {
if($Admin->fetch_object("userGroups", "g_name", $POST->g_name)!==false)	{ $Result->show("danger", _('Group already exists')."!", true); }
}

# create array of values for modification
$values = array("g_id"=>$POST->g_id,
				"g_name"=>$POST->g_name,
				"g_desc"=>$POST->g_desc);

# fetch custom fields
$update = $Tools->update_POST_custom_fields('userGroups', $POST->action, $POST);
$values = array_merge($values, $update);

/* try to execute */
if(!$Admin->object_modify("userGroups", $POST->action, "g_id", $values)) { $Result->show("danger",  _("Group")." ".$User->get_post_action()." "._("error")."!", false); }
else 					 													{ $Result->show("success", _("Group")." ".$User->get_post_action()." "._("success")."!", false); }

# from list of usernames provided from AD result if some user matches add him to group
if (!is_blank($POST->gmembers)) {
	// save id
	$gid = $Admin->lastId;
	// to array
	$gmembers = pf_explode(";", $POST->gmembers);
	// check
	foreach ($gmembers as $gm) {
		// check if user exists
		$user=$Admin->fetch_object("users","username",$gm);
		if ($user!==false) {
			// add to group
			$Admin->add_group_to_user ($gid, $user->id);
		}
	}
}
