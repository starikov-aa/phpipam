<?php

/**
 * Edit tag
 *************************************************/

/* functions */
require_once(dirname(__FILE__) . '/../../../functions/functions.php');

$Database = new Database_PDO;
$User = new User ($Database);
$Admin = new Admin ($Database);
$Result = new Result ();
$dhcp = new DHCP('kea');
$common = new Common_functions();

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->Crypto->csrf_cookie("create", "tags");

# strip tags - XSS
$_POST = $User->strip_input_tags($_POST);

# validate action
$Admin->validate_action($_POST['action'], true);

# get subnets
$subnets4 = $dhcp->read_subnets("IPv4");
$subnets6 = $dhcp->read_subnets("IPv6");

# get subnet of ID
$curSubnet4 = $subnets4[$_POST['id']];
$curSubnet6 = $subnets6[$_POST['id']];

# вытаскиваем настройки переопределеные на уровне подсети
$routerAddr = $common->findInAssocArray($curSubnet4['option-data'], 'name', 'routers')['data'] ?? '';
$domainName = $common->findInAssocArray($curSubnet4['option-data'], 'name', 'domain-name')['data'] ?? '';
$domainNameServers = $common->findInAssocArray($curSubnet4['option-data'], 'name', 'domain-name-servers')['data'] ?? '';

//print_r($curSubnet4);

# ID must be numeric
//if($_POST['action']="edit" && !empty($_POST['address']) && !empty($_POST['hwaddr'])) {
//$Result->show("danger", _("Invalid ID"), true, true);
//}

?>

<script>
    if ($('#action').val() == 'edit'){
        $(".noEdit").prop('readonly', true);
    }
</script>

<!-- header -->
<div class="pHeader">Subnet <?php print $_POST['action']; ?></div>

<!-- content -->
<div class="pContent">
    <form id="editSubnet" name="editSubnet">
        <table class="table table-noborder table-condensed">
            <!-- ID -->
            <tr>
                <td style="white-space: nowrap;"><?php print _('ID'); ?></td>
                <td>
                    <input type="text" id="ids" name="s[id]" class="form-control input-sm noEdit"
                           value="<?php print $curSubnet4['id']; ?>">
                </td>
            </tr>
            <!-- Subnet -->
            <tr>
                <td style="width:120px;"><?php print _('Subnet'); ?></td>
                <td>
                    <input type="text" id="subnet" name="s[subnet]"
                           class="form-control input-sm readonly-inp-without-static"
                           value="<?php print $curSubnet4['subnet']; ?>">
                </td>
            </tr>

            <!-- Pools -->
            <tr>
                <td style="white-space: nowrap;"><?php print _('Pools'); ?></td>
                <td>
                    <input type="text" id="pool" name="s[pools][][pool]" class="form-control input-sm"
                           value="<?php print $curSubnet4['pools'][0]['pool']; ?>">
                    <small id="passwordHelpBlock" class="form-text text-muted">
                        Format: StartIP-EndIP. e.g.: 192.168.0.0-192.168.0.254
                    </small>
                </td>
            </tr>

            <!-- Dns name -->
            <tr>
                <td style="white-space: nowrap;"><?php print _('Dns name'); ?></td>
                <td>
                    <input type="text" id="dns-name" name="s[option-data][domain-name]" class="form-control input-sm"
                           value="<?php print $domainName; ?>">
                </td>
            </tr>
            <!-- Dns servers -->
            <tr>
                <td style="white-space: nowrap;"><?php print _('Dns servers'); ?></td>
                <td>
                    <input type="text" id="dns-name-servers" name="s[option-data][domain-name-servers]" class="form-control input-sm"
                           value="<?php print $domainNameServers; ?>"></div><div class="col">
                </td>
            </tr>
            <!-- Default dns servers -->
            <tr>
                <td style="white-space: nowrap;"><?php print _('Use default dns servers'); ?></td>
                <td>
                    <input type="checkbox" name="default-dns" id="default-dns" checked>
                </td>
            </tr>
            <!-- Router -->
            <tr>
                <td style="white-space: nowrap;"><?php print _('Router'); ?></td>
                <td>
                    <input type="text" id="routers" name="s[option-data][routers]" class="form-control input-sm"
                           value="<?php print $routerAddr; ?>">
                </td>
            </tr>
            <!-- Relay -->
            <tr>
                <td style="white-space: nowrap;"><?php print _('Relay'); ?></td>
                <td>
                    <input type="text" id="relay" name="s[relay][ip-addresses][]" class="form-control input-sm"
                           value="<?php print $curSubnet4['relay']['ip-addresses'][0]; ?>">
                </td>
            </tr>
            <!-- Valid Life Time -->
            <tr>
                <td style="white-space: nowrap;"><?php print _('Life Time'); ?></td>
                <td>
                    <input type="text" id="valid-lifetime" name="s[valid-lifetime]" class="form-control input-sm"
                           value="<?php print $curSubnet4['valid-lifetime']; ?>">
                </td>
            </tr>
            <!-- Next server -->
            <tr>
                <td style="white-space: nowrap;"><?php print _('Next server'); ?></td>
                <td>
                    <input type="text" id="next-server" name="s[next-server]" class="form-control input-sm"
                           value="<?php print @$curSubnet4['next-server']; ?>">
                </td>
            </tr>
        </table>
        <input type="hidden" id="action" name="action" value="<?php print $_POST['action']; ?>">
        <input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
    </form>

</div>

<!-- footer -->
<div class="pFooter">
    <div class="btn-group">
        <button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
        <button class="btn btn-sm btn-default submit_popup readonly-without-static <?php print $_POST['action'] == "delete" ? "btn-danger" : "btn-success"; ?>"
                id="editSubnet" data-script='app/admin/dhcp/edit-subnet-submit.php' data-form='editSubnet'
                data-result_div="editSubnetResult"><i
                    class="fa <?php print $_POST['action'] == "delete" ? "fa-trash-o" : "fa-check"; ?>"></i> <?php print ucwords(_($_POST['action'])); ?>
        </button>
    </div>
    <!-- Result -->
    <div class="editReservationResult" id="editSubnetResult"></div>
</div>
