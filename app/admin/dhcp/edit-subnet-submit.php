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

$ipType = "IPv4";

$dhcp = new DHCP('kea');

# get subnets
$dhcpSubnets = $dhcp->read_subnets($ipType);

# get subnet of ID
$curSubnet = $dhcpSubnets[$_POST['s']['id']] ?? false;

// формируем массив с доп оциями для резервирования.
$ap1 = ['boot-file-name' => $_POST['boot-file-name'],
    'next-server' => $_POST['next-server']
];
$ap2 = json_decode($_POST['additional_settings'], true);
$ap2 = is_array($ap2) ? $ap2 : [];
$reservationAdditionSettings = array_merge($ap1, $ap2);

if ($_POST['action'] == 'edit' || $_POST['action'] == 'add') {
    $pd = $_POST['s'];
    $curSubnet['id'] = (int)$pd['id'];
    $curSubnet['subnet'] = $pd['subnet'];
    $curSubnet['pools'] = $pd['pools'];
    $curSubnet['valid-lifetime'] = (int)$pd['valid-lifetime'] ?: 4000;
    $curSubnet['next-server'] = $pd['next-server'];

    if (!empty($pd['relay']) &&
        !in_array($pd['relay'], $curSubnet['relay']['ip-addresses'])) {
        $curSubnet['relay']['ip-addresses'][] = $pd['relay'];
    }

    // обновляем/добавляем опции
    foreach ($pd['option-data'] as $od_k => $od_v) {
        if (empty($od_v)) continue;
        $odItemKey = $common->findInAssocArray($curSubnet['option-data'], 'name', $od_k, true);
        if ($odItemKey !== false) {
            $opPath = &$curSubnet['option-data'][$odItemKey];
        } else {
            $opPath = &$curSubnet['option-data'][];
        }

        $opPath['name'] = $od_k;
        $opPath['data'] = $od_v;
    }

    //print_r($curSubnet);

    try {
        $dhcp->write_subnet($curSubnet, $ipType);
    } catch (Throwable $e) {
        $Result->show("danger", _($e->getMessage()), true);
    }
} elseif ($_POST['action'] == 'delete') {
    try {
        //$dhcp->delete_reservation($_POST['ip_addr'], 'IPv4');
    } catch (Throwable $e) {
        $Result->show("danger", _($e->getMessage()), true);
    }
}

$Result->show("success", _("Subnet $_POST[action] success"), false);
