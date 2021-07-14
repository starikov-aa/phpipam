<?php

/* functions */
require_once(dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database = new Database_PDO;
$User = new User ($Database);
$Admin = new Admin ($Database);
$Subnets = new Subnets ($Database);
$Addresses = new Addresses ($Database);
$Result = new Result ();

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode();

# strip input tags
$_POST = $Admin->strip_input_tags($_POST);

$dhcp = new DHCP('kea');
$reservation = $dhcp->read_reservations("IPv4");
$hwaddrs = array_search($_POST['hwaddr'], array_column($reservation, 'hw-address'));
$ipToDec = $Subnets->transform_address($_POST['ip_addr'], 'decimal');
$ipObj = $Addresses->fetch_address('ip_addr', $ipToDec);

if ($ipObj) {
    $address = (array)$ipObj;
}

$address['hostname'] = $_POST['hostname'];
$address['description'] = $_POST['description'];
$address['state'] = 5;
$address['ip_addr'] = $ipToDec;
$address['mac'] = $User->reformat_mac_address($_POST['hwaddr'], 1);

// формируем массив с доп оциями для резервирования.
$ap1 = ['boot-file-name' => $_POST['boot-file-name'],
    'next-server' => $_POST['next-server']
];
$ap2 = json_decode($_POST['additional_settings'], true);
$ap2 = is_array($ap2) ? $ap2 : [];
$reservationAdditionSettings = array_merge($ap1, $ap2);


if ($_POST['action'] == 'edit' || $_POST['action'] == 'add') {
//    if (!isset($reservation[$_POST['ip_addr']]) && $hwaddrs !== false) {
    try {
        $dhcp->write_reservation($_POST['ip_addr'], $_POST['hwaddr'], $_POST['subnet_id'], $reservationAdditionSettings);
    } catch (Throwable $e) {
        $Result->show("danger", _($e->getMessage()), true);
    }
//    }

    if ($ipObj) {
        $address['action'] = 'edit';
        $Addresses->modify_address($address);
    } else {
        $subNetId = $Subnets->find_subnet_by_ip($_POST['ip_addr'])->id;
        if ($subNetId) {
            $address['subnetId'] = $subNetId;
            $address['action'] = 'add';
            $Addresses->modify_address($address);
        } else {
            $Result->show("warning", _('No subnet found for this address'), false);
        }
    }
    $Result->show("success", _("Lease $_POST[action] success"), false);
} elseif ($_POST['action'] == 'delete') {
    if (isset($dhcp->read_leases()[$_POST['ip_addr']])) {
        try {
            $dhcp->delete_lease($_POST['ip_addr'], 'IPv4');
        } catch (Throwable $e) {
            $Result->show("danger", _($e->getMessage()), true);
        }
    }
    if (isset($_POST['static'])) {
        try {
            $dhcp->delete_reservation($_POST['ip_addr'], 'IPv4');
        } catch (Throwable $e) {
            $Result->show("danger", _($e->getMessage()), true);
        }
    }
}

