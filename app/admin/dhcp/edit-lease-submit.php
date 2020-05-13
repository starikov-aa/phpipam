<?php

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Subnets	= new Subnets ($Database);
$Addresses	= new Addresses ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();

# strip input tags
$_POST = $Admin->strip_input_tags($_POST);

$dhcp = new DHCP('kea');
$reservation = $dhcp->read_reservations ("IPv4");
$hwaddrs = array_search($_POST['hwaddr'], array_column($reservation, 'hw-address'));
$ipObj = $Addresses->fetch_address(null, $_POST['addressId']);

if ($ipObj) {
    $address = (array) $ipObj;
}

$address['description'] = $_POST['description'];
$address['state'] = '6';
$address['ip_addr'] = $Subnets->transform_address($_POST['ip_addr'], 'decimal');
$address['mac'] = $User->reformat_mac_address ($_POST['hwaddr'], 1);

if ($_POST['action'] == 'edit') {
    if (isset($_POST['static'])) {
        if (!isset($reservation[$_POST['ip_addr']]) && !$hwaddrs) {
            try {
                $dhcp->write_reservation($_POST['ip_addr'], $_POST['hwaddr'], $_POST['subnet_id']);
            } catch (Throwable $e) {
                $Result->show("danger", _($e->getMessage()), true);
            }
        }

        if ($ipObj) {
            $address['action'] = 'edit';
            $Addresses->modify_address($address);
        } else {
            $subNetId = $Subnets->find_subnet_by_ip($_POST['ip_addr']);
            if ($subNetId) {
                $address['subnetId'] = $subNetId;
                $address['action'] = 'add';
                $Addresses->modify_address($address);
            } else {
                $Result->show("warning", _('No subnet found for this address'), false);
            }
        }

        $Result->show("success", _("Lease $_POST[action] success"), false);
    }
} elseif ($_POST['action'] == 'delete') {
    try {
        $dhcp->delete_lease($_POST['ip_addr'], 'IPv4');
    } catch (Throwable $e) {
        $Result->show("danger", _($e->getMessage()), true);
    }
}
