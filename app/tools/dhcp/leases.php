<?php

/**
 * Script to edit / add / delete groups
 *************************************************/

# verify that user is logged in
$User->check_user_session();
# perm check
$User->check_module_permissions("dhcp", User::ACCESS_R, true, false);

# get leases
$leases4 = $DHCP->read_leases("IPv4");
$leases6 = $DHCP->read_leases("IPv6");

# get reservation
$reservation4 = $DHCP->read_reservations("IPv4");
$reservation6 = $DHCP->read_reservations("IPv6");

# get subnets
$subnets4 = $DHCP->read_subnets("IPv4", "id");
$subnets6 = $DHCP->read_subnets("IPv6");

$common_func = new Common_functions();

// get information for all IP
$AllIP = [];
foreach ($Subnets->fetch_all_subnets() as $sub) {
    $ips = $Addresses->fetch_subnet_addresses($sub->id, null, null, ['ip_addr', 'description', 'hostname']);
    foreach ($ips as $ip) {
        $ip_addr = $common_func->transform_to_dotted($ip->ip_addr);
        $AllIP[$ip_addr] = (array)$ip;
        $AllIP[$ip_addr]['subnet']['description'] = $sub->description;
        $AllIP[$ip_addr]['subnet']['id'] = $sub->id;
        $AllIP[$ip_addr]['subnet']['sectionId'] = $sub->sectionId;
    }
}

// this function returns single item as table item for subnets
function print_leases($lease, $AllIP, $reservation, $isManagement)
{
    // get
    global $User, $Subnets;

    // Выводим описание подсети
    if (array_key_exists($lease['ip-address'], $AllIP)) {
        $ipamSubnet = $AllIP[$lease['ip-address']]['subnet'];
        $ipamIpDescription = $AllIP[$lease['ip-address']]['description'];
    } else {
        $ipamSubnet = (array)$Subnets->find_subnet_by_ip($lease['ip-address']);
        $ipamIpDescription = "";
    }

    // Проверяем резервирование
    if (array_key_exists($lease['ip-address'], $reservation)) {
        $isReserved = "";
        $isExpired = "Static";
    } else {
        $isReserved = "D";
        $isExpired = $lease['expire'] ?? "";
    }

    // Задаем имя из lease, ipam или оба сразу
    $ipamHN = @$AllIP[$lease['ip-address']]['hostname'];
    $leaseHN = preg_replace("/\.+$/", "", $lease['hostname']);
    if (!empty($leaseHN)){
        $Hostname = $leaseHN;
        if (!empty($ipamHN) && $ipamHN != $leaseHN){
            $Hostname .= ' (' . $ipamHN . ')';
        }
    } elseif (!empty($ipamHN)) {
        $Hostname = $ipamHN;
    } else {
        $Hostname = '---';
    }

    $printed_options = array();

    $leaseClientId = array_key_exists("client_id", $lease) ? $lease['client_id'] : "";
    $leaseState = array_key_exists("state", $lease) ? $lease['state'] : "-";
    $subnetLink = "<a href='index.php?page=subnets&section=" . $ipamSubnet['sectionId'] .
        "&subnetId=" . $ipamSubnet['id'] . "'>" . $ipamSubnet['description'] . "</a>";

    $html[] = "<tr>";
    $html[] = " <td>" . $isReserved . "</td>";
    $html[] = " <td>" . $subnetLink . "</td>";
    $html[] = " <td>" . $lease['ip-address'] . "</td>";
    $html[] = " <td>" . $User->reformat_mac_address($lease['hw-address'], 1) . "</td>";
    $html[] = " <td>" . $leaseClientId . "</td>";
    $html[] = " <td>" . $isExpired . "</td>";
    $html[] = " <td>" . $leaseState . "</td>";
    $html[] = " <td>" . $Hostname . "</td>";
    $html[] = " <td>" . $ipamIpDescription . "</td>";
    $html[] = "<td class='actions'>";
    if ($isManagement) {
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
<?php if ($isManagement) { ?>
    <a class='btn btn-sm btn-default btn-default btn-success dhcp-leases open_popup' data-action='add'
       data-script='app/admin/dhcp/edit-lease.php'><i class='fa fa-plus'></i> <?php print _('Add'); ?></a>
<?php } else { ?>
    <a class='btn btn-sm btn-default btn-default btn-success'
       href="<?php print create_link("administration", "dhcp", "leases"); ?>"><i
                class='fa fa-pencil'></i> <?php print _('Manage'); ?></a>
<?php } ?>

<br>

<script>
    function ipSorter(a, b) {
        const num1 = Number(a.split(".").map((num) => (`000${num}`).slice(-3) ).join(""));
        const num2 = Number(b.split(".").map((num) => (`000${num}`).slice(-3) ).join(""));
        return num1-num2;
    }
</script>

<!-- table -->
<table id="dhcp_leases" class="table sorted table-striped table-top table-td-top" data-cookie-id-table="dhcp_leases">

    <!-- Headers -->
    <thead>
    <tr>
        <th></th>
        <th data-field="subdesc" data-sortable="true">Sub desc</th>
        <th data-field="Address" data-sortable="true" data-sorter="ipSorter">Address</th>
        <th data-field="MAC" data-sortable="true">MAC</th>
        <th data-field="Client_id" data-sortable="true">Client ID</th>
        <th data-field="Expires" data-sortable="true">Expires</th>
        <th data-field="State" data-sortable="true">State</th>
        <th data-field="Hostname" data-sortable="true">Hostname (hn Ipam)</th>
        <th data-field="Description" data-sortable="true">Description</th>
        <th></th>
    </tr>
    </thead>

    <!-- subnets -->
    <?php

    $headCount = 10;

    // v4
    //$html[] = "<td class='th' colspan='" . $headCount . "'>" . _("IPv4 leases") . "</td>";
    $html[] = "</tr>";

    // IPv4 not configured
    if ($leases4 === false) {
        $html[] = "<tr>";
        $html[] = " <td colspan='" . $headCount . "'>" . $Result->show("info", _("IPv4 not configured on DHCP server"), false, false, true) . "</td>";
        $html[] = "</tr>";
    } // no subnets found
    elseif (!count($leases4) && !count($reservation4)) {
        $html[] = "<tr>";
        $html[] = " <td colspan='" . $headCount . "'>" . $Result->show("info", _("No IPv4 leases"), false, false, true) . "</td>";
        $html[] = "</tr>";
    } else {
        foreach ($leases4 as $lease) {
            $html = array_merge($html, print_leases($lease, $AllIP, $reservation4, $isManagement));
            if (isset($reservation4[$lease['ip-address']]) && is_array($reservation4[$lease['ip-address']])) {
                unset($reservation4[$lease['ip-address']]);
            }
        }
    }

    foreach ($reservation4 as $r) {
        $html = array_merge($html, print_leases($r, $AllIP, $reservation4, $isManagement));
    }

// TODO: переписать чтобы для IPv6 выводилось в отдельной таблице
    // v6
//    $html[] = "<tr>";
//    $html[] = "<td class='th' colspan='" . $headCount . "'>" . _("IPv6 leases") . "</td>";
//    $html[] = "</tr>";
//
//    // IPv4 not configured
//    if ($leases6 === false) {
//        $html[] = "<tr>";
//        $html[] = " <td colspan='" . $headCount . "'>" . $Result->show("info", _("IPv6 not configured on DHCP server"), false, false, true) . "</td>";
//        $html[] = "</tr>";
//    } // no subnets found
//    elseif (sizeof($leases6) == 0) {
//        $html[] = "<tr>";
//        $html[] = " <td colspan='" . $headCount . "'>" . $Result->show("info", _("No IPv6 leases"), false, false, true) . "</td>";
//        $html[] = "</tr>";
//    } else {
//        foreach ($leases6 as $s) {
//            $html = array_merge($html, print_leases($s, $AllIP));
//        }
//    }

    # print table
    print implode("\n", $html);
    ?>
    </tbody>
</table>
