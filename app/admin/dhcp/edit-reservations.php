<?php

/**
 * Edit tag
 *************************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Subnets	= new Subnets ($Database);
$Addresses	= new Addresses ($Database);
$Result 	= new Result ();
$dhcp = new DHCP('kea');

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "tags");

# strip tags - XSS
$_POST = $User->strip_input_tags ($_POST);

# validate action
$Admin->validate_action ($_POST['action'], true);

//$leases = $dhcp->read_leases()[$_POST['ip_addr']];
$reservation = $dhcp->read_reservations ("IPv4")[$_POST['ip_addr']];

# get subnets

$subnets4 = $dhcp->read_subnets ("IPv4");
//$subnets6 = $dhcp->read_subnets ("IPv6");

# ID must be numeric
//if($_POST['action']="edit" && !empty($_POST['address']) && !empty($_POST['hwaddr'])) {
//$Result->show("danger", _("Invalid ID"), true, true);
//}

$ipToDec = $Subnets->transform_address($_POST['ip_addr'], 'decimal');
$address = (array) $Addresses->fetch_address('ip_addr', $ipToDec);

?>

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
                    <input type="text" id="ip_addr" name="ip_addr" class="form-control input-sm" value="<?php print @$reservation['ip-address']; ?>">
                    <input type="hidden" name="action" value="<?php print $_POST['action']; ?>">
                    <input type="hidden" name="addressId" 	value="<?php print $address['id']; ?>">
                    <input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
                </td>
            </tr>

            <!-- MAC -->
            <tr>
                <td style="white-space: nowrap;"><?php print _('Mac'); ?></td>
                <td>
                    <input type="text" id="hwaddr" name="hwaddr" class="form-control input-sm" value="<?php print @$reservation['hw-address']; ?>">
                </td>
            </tr>

            <!-- Subnet -->
            <tr>
                <td style="white-space: nowrap;"><?php print _('Subnet'); ?></td>
                <td>
                    <select name="subnet_id" class="form-control input-sm input-w-auto">
                        <?php foreach ($subnets4 as $s){
                            $on = ($reservation['dhcp4_subnet_id'] == $s['id']) ? 'selected' : '';
                            print '<option value="'.$s['id'].'" '.$on.'>'.$s['subnet'].'</option>';
                        }?>
                    </select>
                </td>
            </tr>

            <!-- Hostname -->
            <tr>
                <td style="white-space: nowrap;"><?php print _('Hostname'); ?></td>
                <td>
                    <input type="text" id="hostname" name="hostname" class="form-control input-sm" value="<?php print @$reservation['hostname']; ?>">
                </td>
            </tr>

            <!-- Desc -->
            <tr>
                <td><?php print _('Description'); ?></td>
                <td><input type="text" id="description" name="description" value="<?php print($address['description']); ?>" class="form-control input-sm"></td>
            </tr>
            <!-- NextServer -->
            <tr>
                <td style="white-space: nowrap;"><?php print _('NextServer'); ?></td>
                <td>
                    <input type="text" id="hostname" name="nextserver" class="form-control input-sm" value="">
                </td>
            </tr>
            <!-- BootFileName -->
            <tr>
                <td style="white-space: nowrap;"><?php print _('BootFileName'); ?></td>
                <td>
                    <input type="text" id="hostname" name="boot-file-name" class="form-control input-sm" value="">
                </td>
            </tr>
        </table>
    </form>

</div>

<!-- footer -->
<div class="pFooter">
    <div class="btn-group">
        <button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
        <button class="btn btn-sm btn-default submit_popup <?php print $_POST['action']=="delete" ? "btn-danger" : "btn-success"; ?>" id="editReservationSubmit" data-script='app/admin/dhcp/edit-reservation-submit.php' data-form='editReservation' data-result_div="editReservationResult"><i class="fa <?php print $_POST['action']=="delete" ? "fa-trash-o" : "fa-check"; ?>"></i> <?php print ucwords(_($_POST['action'])); ?></button>
    </div>
    <!-- Result -->
    <div class="editReservationResult" id="editReservationResult"></div>
</div>
