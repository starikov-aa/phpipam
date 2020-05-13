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
$leases = $dhcp->read_leases()[$_POST['ip_addr']];
$reservation = $dhcp->read_reservations ("IPv4");

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "tags");

# strip tags - XSS
$_POST = $User->strip_input_tags ($_POST);

# validate action
$Admin->validate_action ($_POST['action'], true);

# ID must be numeric
//if($_POST['action']="edit" && !empty($_POST['address']) && !empty($_POST['hwaddr'])) {
    //$Result->show("danger", _("Invalid ID"), true, true);
//}

$ipToDec = $Subnets->transform_address($_POST['ip_addr'], 'decimal');
$address = (array) $Addresses->fetch_address('ip_addr', $ipToDec);

?>

<!-- header -->
<div class="pHeader">Lease edit</div>

<!-- content -->
<div class="pContent">

    <form id="editLease" name="editLease">
        <table class="table table-noborder table-condensed">

            <!-- IP -->
            <tr>
                <td style="width:120px;"><?php print _('IP'); ?></td>
                <td>
                    <input type="text" id="ip_addr" name="ip_addr" class="form-control input-sm" value="<?php print @$leases['address']; ?>"  readonly>
                    <input type="hidden" name="action" value="<?php print $_POST['action']; ?>">
                    <input type="hidden" name="subnet_id" value="<?php print $_POST['subnet_id']; ?>">
                    <input type="hidden" name="addressId" 	value="<?php print $address['id']; ?>">
                    <input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
                </td>
            </tr>

            <!-- MAC -->
            <tr>
                <td style="white-space: nowrap;"><?php print _('Mac'); ?></td>
                <td>
                    <input type="text" id=""hwaddr" name="hwaddr" class="form-control input-sm" value="<?php print @$leases['hwaddr']; ?>"  readonly>
                </td>
            </tr>

            <!-- Desc -->
            <tr>
                <td><?php print _('Description'); ?></td>
                <td><input type="text" id="description" name="description" value="<?php print($address['description']); ?>" class="form-control input-sm"></td>
            </tr>

            <!-- Make static -->
            <tr>
                <td><?php print _('Static'); ?></td>
                <td><input type="checkbox" name="static" value="" <?php isset($reservation[$leases['address']])? print 'checked' : ''?>></td>
            </tr>
        </table>
    </form>

</div>

<!-- footer -->
<div class="pFooter">
    <div class="btn-group">
        <button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
        <button class="btn btn-sm btn-default submit_popup <?php print $_POST['action']=="delete" ? "btn-danger" : "btn-success"; ?>" id="editLeaseSubmit" data-script='app/admin/dhcp/edit-lease-submit.php' data-form='editLease' data-result_div="editLeaseResult"><i class="fa <?php print $_POST['action']=="delete" ? "fa-trash-o" : "fa-check"; ?>"></i> <?php print ucwords(_($_POST['action'])); ?></button>
    </div>
    <!-- Result -->
    <div class="editLeaseResult" id="editLeaseResult"></div>
</div>
