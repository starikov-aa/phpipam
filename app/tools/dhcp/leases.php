<?php

/**
 * Script to edit / add / delete groups
 *************************************************/

# verify that user is logged in
$User->check_user_session();
# perm check
$User->check_module_permissions("dhcp", User::ACCESS_R, true, false);

// Check admin page
if ($User->is_admin(false)) {
    if ($_GET['page'] == "administration") {
        $IsManagement = true;
    } else {
        $IsManagement = false;
    }
}

# get leases
$leases4 = $DHCP->read_leases("IPv4");
$leases6 = $DHCP->read_leases("IPv6");

# get reservation
$reservation4 = $DHCP->read_reservations("IPv4");
$reservation6 = $DHCP->read_reservations("IPv6");

# get subnets
$subnets4 = $DHCP->read_subnets("IPv4", "id");
$subnets6 = $DHCP->read_subnets("IPv6");

// get information for all IP
$AllIP = [];
foreach ($Subnets->fetch_all_subnets() as $sub) {
    $ips = $Addresses->fetch_subnet_addresses($sub->id, null, null, ['ip_addr', 'description', 'hostname']);
    foreach ($ips as $ip) {
        $AllIP[$ip->ip] = (array)$ip;
        $AllIP[$ip->ip]['sub_desc'] = $sub->description;
    }
}

$table_headers = [
    '',
    'Sub desc',
    'Address',
    'MAC',
    'Client_id',
    'Expires',
    'State',
    'Hostname (hn Ipam)',
    'Description',
    ''
];


// this function returns single item as table item for subnets
function print_leases($lease, $AllIP, $reservation, $IsManagement)
{
    // get
    global $User;

    $IsReserved = is_array($reservation[$lease['ip-address']]) ? 'S' : 'D';
    $HostnameIpam = empty($AllIP[$lease['ip-address']]['hostname']) ? '' : ' (' . $AllIP[$lease['ip-address']]['hostname'] . ')';
    $HostnameDhcp = empty($lease['hostname']) ? $reservation[$lease['ip-address']]['hostname'] : $lease['hostname'];
    $Hostname = ($HostnameDhcp === $AllIP[$lease['ip-address']]['hostname']) ? $HostnameDhcp : $HostnameDhcp . $HostnameIpam;

    // printed option to add defaults
    $printed_options = array();

    $html[] = "<tr>";
    $html[] = " <td>" . $IsReserved . "</td>";
    $html[] = " <td>" . $AllIP[$lease['ip-address']]['sub_desc'] . "</td>";
    $html[] = " <td>" . $lease['ip-address'] . "</td>";
    $html[] = " <td>" . $User->reformat_mac_address($lease['hw-address'], 1) . "</td>";
    $html[] = " <td>" . $lease['client_id'] . "</td>";
    $html[] = " <td>" . $lease['expire'] . "</td>";
    $html[] = " <td>" . $lease['state'] . "</td>";
    $html[] = " <td>" . $Hostname . "</td>";
    $html[] = " <td>" . $AllIP[$lease['ip-address']]['description'] . "</td>";
    $html[] = "<td class='actions'>";
    if ($IsManagement) {
        $html[] = "    <div class='btn-group'>";
        $html[] =  "	<button class='btn btn-xs btn-default open_popup' data-class='500' data-ip_addr='".$lease['ip-address']."' data-hostname='".$Hostname."' data-script='app/admin/dhcp/edit-lease.php' data-action='edit'><i class='fa fa-pencil'></i></button>";
        $html[] =  "	<button class='btn btn-xs btn-default open_popup' data-class='500' data-ip_addr='".$lease['ip-address']."' data-hostname='".$Hostname."' data-script='app/admin/dhcp/edit-lease.php' data-action='delete'><i class='fa fa-times'></i></button>";
        $html[] = "	</div>";
    }
    $html[] = "	</td>";
    $html[] = " </td>";
    $html[] = "</tr>";
    // return
    return $html;
}

?>

<br>
<h4><?php print _("Active leases"); ?></h4>
<hr>

<!-- Manage -->
<?php if ($IsManagement) { ?>
    <a class='btn btn-sm btn-default btn-default btn-success dhcp-leases open_popup' data-action='add'
       data-script='app/admin/dhcp/edit-lease.php'><i class='fa fa-plus'></i> <?php print _('Add'); ?></a>
<?php } else { ?>
    <a class='btn btn-sm btn-default btn-default btn-success'
       href="<?php print create_link("administration", "dhcp", "leases"); ?>"><i
                class='fa fa-pencil'></i> <?php print _('Manage'); ?></a>
<?php } ?>

<br>

<!-- table -->
<table id="zonesPrint" class="table sorted table-striped table-top table-td-top" data-cookie-id-table="dhcp_leases">

    <!-- Headers -->
    <thead>
    <tr>
        <?php foreach ($table_headers as $hn) { ?>
            <th><?php print _($hn); ?></th>
        <?php } ?>
    </tr>
    </thead>

    <!-- subnets -->
    <?php
    // v4
    $html[] = "<tr>";
    $html[] = "<td class='th' colspan='" . count($table_headers) . "'>" . _("IPv4 leases") . "</td>";
    $html[] = "</tr>";

    // IPv4 not configured
    if ($leases4 === false) {
        $html[] = "<tr>";
        $html[] = " <td colspan='" . count($table_headers) . "'>" . $Result->show("info", _("IPv4 not configured on DHCP server"), false, false, true) . "</td>";
        $html[] = "</tr>";
    } // no subnets found
    elseif (sizeof($leases4) == 0) {
        $html[] = "<tr>";
        $html[] = " <td colspan='" . count($table_headers) . "'>" . $Result->show("info", _("No IPv4 leases"), false, false, true) . "</td>";
        $html[] = "</tr>";
    } else {
        foreach ($leases4 as $lease) {
            $html = array_merge($html, print_leases($lease, $AllIP, $reservation4, $IsManagement));
            if (is_array($reservation4[$lease['ip-address']])) {
                unset($reservation4[$lease['ip-address']]);
            }
        }
        foreach ($reservation4 as $r) {
            $html = array_merge($html, print_leases($r, $AllIP, $reservation4, $IsManagement));
        }
    }


    // v6
    $html[] = "<tr>";
    $html[] = "<td class='th' colspan='" . count($table_headers) . "'>" . _("IPv6 leases") . "</td>";
    $html[] = "</tr>";

    // IPv4 not configured
    if ($leases6 === false) {
        $html[] = "<tr>";
        $html[] = " <td colspan='" . count($table_headers) . "'>" . $Result->show("info", _("IPv6 not configured on DHCP server"), false, false, true) . "</td>";
        $html[] = "</tr>";
    } // no subnets found
    elseif (sizeof($leases6) == 0) {
        $html[] = "<tr>";
        $html[] = " <td colspan='" . count($table_headers) . "'>" . $Result->show("info", _("No IPv6 leases"), false, false, true) . "</td>";
        $html[] = "</tr>";
    } else {
        foreach ($leases6 as $s) {
            $html = array_merge($html, print_leases($s, $AllIP));
        }
    }

    # print table
    print implode("\n", $html);
    ?>
    </tbody>
</table>
