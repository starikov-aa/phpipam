<?php

/* functions */
require_once(dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database = new Database_PDO;
$User = new User ($Database);
$Admin = new Admin ($Database);
$Subnets = new Subnets ($Database);
$Result = new Result ();
$common = new Common_functions();

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode();

# strip input tags
$_POST = $Admin->strip_input_tags($_POST);

$dhcp = new DHCP('kea');

# get subnets
$subnets4 = $dhcp->read_subnets("IPv4");
$subnets6 = $dhcp->read_subnets("IPv6");

# get subnet of ID
$curSubnet4 = $subnets4[$_POST['id']];
$curSubnet6 = $subnets6[$_POST['id']];

// формируем массив с доп оциями для резервирования.
$ap1 = ['boot-file-name' => $_POST['boot-file-name'],
    'next-server' => $_POST['next-server']
];
$ap2 = json_decode($_POST['additional_settings'], true);
$ap2 = is_array($ap2) ? $ap2 : [];
$reservationAdditionSettings = array_merge($ap1, $ap2);

if ($_POST['action'] == 'edit' || $_POST['action'] == 'add') {
    try {
        $dhcp->write_subnet($_POST['s']);


    } catch (Throwable $e) {
        $Result->show("danger", _($e->getMessage()), true);
    }

    $Result->show("success", _("Lease $_POST[action] success"), false);
} elseif ($_POST['action'] == 'delete') {
    try {
        $dhcp->delete_reservation($_POST['ip_addr'], 'IPv4');
    } catch (Throwable $e) {
        $Result->show("danger", _($e->getMessage()), true);
    }
}

$Result->show("danger", print_r($_PO1ST), true);
