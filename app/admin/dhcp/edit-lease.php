<?php

/**
 * Edit tag
 *************************************************/

/* functions */
require_once(dirname(__FILE__) . '/../../../functions/functions.php');

$Database = new Database_PDO;
$User = new User ($Database);
$Admin = new Admin ($Database);
$Subnets = new Subnets ($Database);
$Addresses = new Addresses ($Database);
$Result = new Result ();
$dhcp = new DHCP('kea');

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->Crypto->csrf_cookie("create", "tags");

# strip tags - XSS
$_POST = $User->strip_input_tags($_POST);

# validate action
$Admin->validate_action($_POST['action'], true);

//
$leaseInfo = $dhcp->read_leases()[$_POST['ip_addr']];

//
$reservationInfo = $dhcp->read_reservations("IPv4")[$_POST['ip_addr']];

//
$ipToDec = $Subnets->transform_address($_POST['ip_addr'], 'decimal');
$ipamIpInfo = (array)$Addresses->fetch_address('ip_addr', $ipToDec);

// IsStatic IP
$isStaticIP = is_array($reservationInfo);

# get subnets
$subnets4 = $dhcp->read_subnets("IPv4");
//$subnets6 = $dhcp->read_subnets ("IPv6");

$_ip = isset($leaseInfo['ip-address']) ? $leaseInfo['ip-address'] : $reservationInfo['ip-address'];
$_mac = isset($leaseInfo['hw-address']) ? $leaseInfo['hw-address'] : $reservationInfo['hw-address'];

# ID must be numeric
//if($_POST['action']="edit" && !empty($_POST['address']) && !empty($_POST['hwaddr'])) {
//$Result->show("danger", _("Invalid ID"), true, true);
//}

?>

<script>
    $("#althostname").click(function () {
        $("#hostname").val($(this).text());
    })

    if (!$('#static').prop('checked')) {
        $(".readonly-inp-without-static").prop('readonly', true);
        $(".staticonly").children().hide();
        if ($('[name="action"]').val() !== 'delete') {
            $("#editLease").prop('disabled', true);
        } else {
            $('#static').prop('disabled', true);
        }

    }

    $('#static').change(function () {
        if ($(this).prop('checked')) {
            $(".staticonly").children().show();
            $(".readonly-inp-without-static").prop('readonly', false);
            $("#editLease").prop('disabled', false);
        } else {
            $(".staticonly").children().hide();
            $(".readonly-inp-without-static").prop('readonly', true);
            if ($('[name="action"]').val() !== 'delete')
                $("#editLease").prop('disabled', true);
        }
    })

</script>

<!-- header -->
<div class="pHeader">Reservation edit</div>

<!-- content -->
<div class="pContent">

    <form id="editReservation" name="editReservation">
        <table class="table table-noborder table-condensed">

            <!-- IP -->
            <tr>
                <td style="width:120px;"><?php print _('IP'); ?></td>
                <td>
                    <input type="text" id="ip_addr" name="ip_addr"
                           class="form-control input-sm readonly-inp-without-static" value="<?php print $_ip; ?>">
                    <input type="hidden" name="action" value="<?php print $_POST['action']; ?>">
                    <input type="hidden" name="addressId" value="<?php print $ipamIpInfo['id']; ?>">
                    <input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
                </td>
            </tr>

            <!-- MAC -->
            <tr>
                <td style="white-space: nowrap;"><?php print _('Mac'); ?></td>
                <td>
                    <input type="text" id="hwaddr" name="hwaddr"
                           class="form-control input-sm readonly-inp-without-static" value="<?php print $_mac; ?>">
                </td>
            </tr>
            <!-- Make static -->
            <tr>
                <td><?php print _('Static'); ?></td>
                <td><input type="checkbox" name="static" id="static"
                           value="1" <?php $isStaticIP ? print 'checked' : '' ?>></td>
            </tr>
            <!-- Subnet -->
            <tr class="staticonly">
                <td style="white-space: nowrap;"><?php print _('Subnet'); ?></td>
                <td>
                    <select name="subnet_id" class="form-control input-sm input-w-auto">
                        <?php foreach ($subnets4 as $s) {
                            $on = ($reservationInfo['subnet-id'] == $s['id']) ? 'selected' : '';
                            print '<option value="' . $s['id'] . '" ' . $on . '>' . $s['subnet'] . '</option>';
                        } ?>
                    </select>
                </td>
            </tr>
            <!-- Hostname -->
            <tr class="staticonly">
                <td style="white-space: nowrap;"><?php print _('Hostname'); ?></td>
                <td>
                    <input type="text" id="hostname" name="hostname" class="form-control input-sm"
                           value="<?php print $_POST['hostname']; ?>">
                </td>
                <td><a id="althostname"><?php print $ipamIpInfo['hostname']; ?><a/></a></td>
            </tr>

            <!-- Hostname -->
            <tr class="staticonly">
                <td style="white-space: nowrap;"><?php print _('Next server'); ?></td>
                <td>
                    <input type="text" id="next-server" name="next-server" class="form-control input-sm"
                           value="<?php print @$reservationInfo['next-server']; ?>">
                </td>
            </tr>

            <!-- boot-file-name -->
            <tr class="staticonly">
                <td style="white-space: nowrap;"><?php print _('Boot file name'); ?></td>
                <td>
                    <input type="text" id="boot-file-name" name="boot-file-name" class="form-control input-sm"
                           value="<?php print @$reservationInfo['boot-file-name']; ?>">
                </td>
            </tr>

            <!-- Additional settings -->
            <tr class="staticonly">
                <td style="white-space: nowrap;"><?php print _('Additional settings (JSON format)'); ?></td>
                <td>
                    <textarea type="textarea" rows="5" id="additional_settings" name="additional_settings"
                              class="form-control input-sm"><?php print_r(json_encode($reservationInfo['options'])); ?></textarea>
                </td>
            </tr>
        </table>
    </form>

</div>

<!-- footer -->
<div class="pFooter">
    <div class="btn-group">
        <button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
        <button class="btn btn-sm btn-default submit_popup readonly-without-static <?php print $_POST['action'] == "delete" ? "btn-danger" : "btn-success"; ?>"
                id="editLease" data-script='app/admin/dhcp/edit-lease-submit.php' data-form='editReservation'
                data-result_div="editReservationResult"><i
                    class="fa <?php print $_POST['action'] == "delete" ? "fa-trash-o" : "fa-check"; ?>"></i> <?php print ucwords(_($_POST['action'])); ?>
        </button>
    </div>
    <!-- Result -->
    <div class="editReservationResult" id="editReservationResult"></div>
</div>
