<?php

# verify that user is logged in
$User->check_user_session();
# perm check
$User->check_module_permissions ("dhcp", User::ACCESS_R, true, false);

//$dhcp = new DHCP('kea');
echo "<pre />";
echo $DHCP->get_servers_status();

?>

