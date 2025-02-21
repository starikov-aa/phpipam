<?php

global $Subnets;

# verify that user is logged in
$User->check_user_session();
# perm check
$User->check_module_permissions("dhcp", User::ACCESS_R, true, false);

# get subnets
$subnets4 = $DHCP->read_subnets("IPv4");
$subnets6 = $DHCP->read_subnets("IPv6");

function secondsToTime($seconds) {
    $dtF = new DateTime('@0');
    $dtT = new DateTime("@$seconds");
    return $dtF->diff($dtT)->format('%ad %H:%I:%S');
}

// Получаем массив подсетей IPAM.
// В качестве ключа сеть в формате CIDR - 1.2.3.4/24
$allIpamSubnets = [];
foreach ($Subnets->fetch_all_subnets() as $subnet) {
    $allIpamSubnets[long2ip($subnet->subnet) . "/" . $subnet->mask] = [
            "id" => $subnet->id,
            "sectionId" => $subnet->sectionId,
            "description" => $subnet->description
    ];
}

/**
 * Формирует строку для таблицы.
 * @param array $s Массив с информацией о подсети из KEA
 * @param bool $isManagement Права пользователя
 * @param array $allIpamSubnets Список подсетей из IPAM
 * @return array Массив HTML строк для вставки в таблицу
 */
function print_subnets(array $s, bool $isManagement, array $allIpamSubnets) : array
{
    $printed_options = array();
    // get config
    global $config, $DHCP;

    if (array_key_exists($s['subnet'], $allIpamSubnets)) {
        $ipamSubnet = $allIpamSubnets[$s['subnet']];
        $subnetLink = "<a href='index.php?page=subnets&section=" . $ipamSubnet['sectionId'] .
            "&subnetId=" . $ipamSubnet['id'] . "'>" . $ipamSubnet['description'] . "</a>";
    } else {
        $subnetLink = "Подсеть отсутствует в IPAM,<br>есть только в конфиге KEA";
    }

    $html[] = "<tr>";
    $html[] = "<td>$subnetLink</td>";
    // subnet
    $html[] = " <td>" . $s['subnet'] . "</td>";
    // pools
    $html[] = " <td>";
    if (sizeof($s['pools']) > 0) {
        foreach ($s['pools'] as $p) {
            $html[] = $p['pool'] . "<br>";
        }
    } else {
        $html[] = "No pools configured";
    }
    $html[] = " </td>";
    // options
    $html[] = " <td>";
    if (sizeof($s['option-data']) > 0) {
        foreach ($s['option-data'] as $p) {
            $html[] = $p['name'] . ": " . $p['data'] . "<br>";
            // save to printed options vas
            $printed_options[] = $p['name'];
        }
    } else {
        //$html[] = "/";
    }

    $html[] = "Lease life time: " . secondsToTime($s['valid-lifetime']) . "<br>";

    // add defaults
    $m = 0;
    if (isset($config['Dhcp4']['option-data'])) {
        foreach ($config['Dhcp4']['option-data'] as $d) {
            // if more specific parameter is already set for subnet ignore, otherwise show
            $d['data'] = $d['name'] == 'domain-search' ? $DHCP->DomainSearch2Text($d['data']) : $d['data'];
            if (!in_array($d['name'], $printed_options)) {
                $hr = $m == 0 ? "<hr><span class='text-muted'>Defaults:</span><br>" : "<br>";
                $html[] = $hr . $d['name'] . ": " . $d['data'];
                // next index
                $m++;
            }
        }
    }

    $html[] = " </td>";
    $html[] = "<td class='actions'>";
    if ($isManagement) {
        $html[] = "    <div class='btn-group'>";
        $html[] = "	<button class='btn btn-xs btn-default open_popup' data-class='500' data-id='" . $s['id'] . "' data-script='app/admin/dhcp/edit-subnet.php' data-action='edit'><i class='fa fa-pencil'></i></button>";
        $html[] = "	<button class='btn btn-xs btn-default open_popup' data-class='500' data-id='" . $s['id'] . "' data-script='app/admin/dhcp/edit-subnet.php' data-action='delete'><i class='fa fa-times'></i></button>";
        $html[] = "	</div>";
    }
    $html[] = "	</td>";
    $html[] = "</tr>";
    // return
    return $html;
}

?>

<br>
<h4><?php print _("Subnets and pools"); ?></h4>
<hr>

<!-- Manage -->
<?php if ($isManagement) { ?>
    <a class='btn btn-sm btn-default btn-default btn-success dhcp-leases open_popup' data-class='500' data-action='add'
       data-script='app/admin/dhcp/edit-subnet.php'><i class='fa fa-plus'></i> <?php print _('Add'); ?></a>
<?php } else { ?>
    <a class='btn btn-sm btn-default btn-default btn-success'
       href="<?php print create_link("administration", "dhcp", "subnets"); ?>"><i
                class='fa fa-pencil'></i> <?php print _('Manage'); ?></a>
<?php } ?>
<br>

<!-- table -->
<table id="zonesPrint" class="table sorted table-striped table-top table-td-top" data-cookie-id-table="dhcp_subnets">

    <!-- Headers -->
    <thead>
    <tr>
        <th data-field="desc" data-sortable="false">Desc</th>
        <th data-field="subnet" data-sortable="true" data-sorter="compareSubnet">Subnet</th>
        <th data-field="pools" data-sortable="false">Pools</th>
        <th data-field="options" data-sortable="false">Options</th>
        <th></th>
    </tr>
    </thead>

    <!-- subnets -->
    <?php

    $headCount = 4;

    $html = [];

    // IPv4 not configured
    if ($subnets4 === false) {
        $html[] = "<tr>";
        $html[] = " <td colspan='" . $headCount . "'>" . $Result->show("info", _("IPv4 not configured on DHCP server"), false, false, true) . "</td>";
        $html[] = "</tr>";
    } // no subnets found
    elseif (sizeof($subnets4) == 0) {
        $html[] = "<tr>";
        $html[] = " <td colspan='" . $headCount . "'>" . $Result->show("info", _("No IPv4 subnets"), false, false, true) . "</td>";
        $html[] = "</tr>";
    } else {
        foreach ($subnets4 as $s) {
            $html = array_merge($html, print_subnets($s, $isManagement, $allIpamSubnets));
        }
    }

    // TODO: переписать чтобы для IPv6 выводилось в отдельной таблице
    //    // v6
    //    $html[] = "<tr>";
    //    $html[] = "<td class='th' colspan='" . $headCount . "'>" . _("IPv6 subnets") . "</td>";
    //    $html[] = "</tr>";
    //
    //    // IPv6 not configured
    //    if ($subnets6 === false) {
    //        $html[] = "<tr>";
    //        $html[] = " <td colspan='" . $headCount . "'>" . $Result->show("info", _("IPv6 not configured on DHCP server"), false, false, true) . "</td>";
    //        $html[] = "</tr>";
    //    } // no subnets found
    //    elseif (sizeof($subnets6) == 0) {
    //        $html[] = "<tr>";
    //        $html[] = " <td colspan='" . $headCount . "'>" . $Result->show("info", _("No IPv6 subnets"), false, false, true) . "</td>";
    //        $html[] = "</tr>";
    //    } else {
    //        foreach ($subnets6 as $s) {
    //            $html = array_merge($html, print_subnets($s, $IsManagement));
    //        }
    //    }

    # print table
    print implode("\n", $html);
    ?>
    </tbody>
</table>