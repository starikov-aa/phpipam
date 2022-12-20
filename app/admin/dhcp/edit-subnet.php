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

$ipType = "IPv4";

# get subnets from ipam
$ipamSubnets = $Subnets->fetch_all_subnets();

# get subnets from dhcp
$dhcpSubnets = $dhcp->read_subnets($ipType);

# get subnet of ID
$curSubnet = $dhcpSubnets[$_POST['id']];

# вытаскиваем настройки переопределеные на уровне подсети
$routerAddr = $common->findInAssocArray($curSubnet['option-data'], 'name', 'routers')['data'] ?? '';
$domainName = $common->findInAssocArray($curSubnet['option-data'], 'name', 'domain-name')['data'] ?? '';
$domainNameServers = $common->findInAssocArray($curSubnet['option-data'], 'name', 'domain-name-servers')['data'] ?? '';

foreach ($ipamSubnets as $jis) {
    $jsData[$jis->id] = ['id' => $jis->id,
        'subnet' => $Subnets->transform_address($jis->subnet, 'dotted') . '/' . $jis->mask,
        'custom_Gateway' => @$jis->custom_Gateway];
}

?>

<script>
    if ($('#action').val() == 'edit') {
        //$(".noEdit").prop('readonly', true);
    }

    var subnets = '<?php print json_encode($jsData, JSON_UNESCAPED_SLASHES); ?>';
    subnets = $.parseJSON(subnets);
    //alert(subnets[4].subnet);

    $('#subnet-list').change(function () {
        sid = $(this).val();
        $('#id').val(sid);
        $('#subnet').val(subnets[sid].subnet);
        pool = calculateCidrRange(subnets[sid].subnet);
        $('#pools').val(pool[0] + '-' + pool[1]);
    })

    let validate = new Bouncer('#editSubnetDhcp', {
        fieldClass: 'valid-error',
        errorClass: 'valid-error-message',
        customValidations: {
            inSubNet: field => validateFuncInSubNet(field)
        },
        messages: {
            inSubNet: field => validateMsgInSubNet(field)
        }
    });

    $("#editSubnet").on("click", function (e) {
        let errCount = validate.validateAll($('#editSubnetDhcp')[0]);
        if (errCount.length > 0) {
            e.preventDefault();
            e.stopPropagation();
        }
    });

</script>

<!-- header -->
<div class="pHeader">Subnet <?php print $_POST['action']; ?></div>

<!-- content -->
<div class="pContent">
    <form id="editSubnetDhcp" name="editSubnetDhcp">
        <table class="table table-noborder table-condensed">
            <!-- ID -->
            <tr>
                <td
                "><?php print _('Subnet'); ?></td>
                <td>
                    <select name="subnet-list" id="subnet-list" class="form-control input-sm">
                        <?php foreach ($ipamSubnets as $isi) {
                            $on = ($isi->id == $_POST['id']) ? 'selected' : '';
                            print '<option value="' . $isi->id . '" ' . $on . '>' . $Subnets->transform_address($isi->subnet, 'dotted') . '/' . $isi->mask . ' (' . $isi->description . ')</option>';
                        } ?>
                    </select>
                </td>
            </tr>

            <!-- Pools -->
            <tr>
                <td style="white-space: nowrap;"><?php print _('Pools'); ?></td>
                <td>
                    <input type="text" id="pools" name="s[pools][][pool]" class="form-control input-sm"
                           value="<?php print $curSubnet['pools'][0]['pool']; ?>"
                           data-valid-inSubNet-type="pool" data-valid-inSubNet-network="#subnet-list" required>
                    <small id="passwordHelpBlock" class="form-text text-muted">
                        Format: StartIP-EndIP. e.g.: 192.168.0.0-192.168.0.254
                    </small>
                </td>
            </tr>

            <!-- Dns name -->
            <tr>
                <td style="white-space: nowrap;"><?php print _('Dns suffix'); ?></td>
                <td>
                    <input type="text" id="dns-name" name="s[option-data][domain-name]" class="form-control input-sm"
                           value="<?php print $domainName; ?>">
                </td>
            </tr>
            <!-- Dns servers -->
            <tr>
                <td style="white-space: nowrap;"><?php print _('Dns servers'); ?></td>
                <td>
                    <input type="text" id="dns-name-servers" name="s[option-data][domain-name-servers]"
                           class="form-control input-sm"
                           value="<?php print $domainNameServers; ?>">
                </td>
            </tr>
            <!-- Default dns servers -->
            <!--            <tr>-->
            <!--                <td style="white-space: nowrap;">-->
            <?php //print _('Use default dns servers'); ?><!--</td>-->
            <!--                <td>-->
            <!--                    <input type="checkbox" name="default-dns" id="default-dns" checked>-->
            <!--                </td>-->
            <!--            </tr>-->
            <!-- Router -->
            <tr>
                <td style="white-space: nowrap;"><?php print _('Gateway'); ?></td>
                <td>
                    <input type="text" id="routers" name="s[option-data][routers]" class="form-control input-sm"
                           value="<?php print $routerAddr; ?>" data-valid-inSubNet-type="ip"
                           data-valid-inSubNet-network="#subnet-list">
                </td>
            </tr>
            <!-- Relay -->
            <tr>
                <td style="white-space: nowrap;"><?php print _('Relay'); ?></td>
                <td>
                    <input type="text" id="relay" name="s[relay]" class="form-control input-sm"
                           value="<?php print $curSubnet['relay']['ip-addresses'][0]; ?>" pattern="(\d{1,3}.){3}\d{1,3}">
                </td>
            </tr>
            <!-- Valid Life Time -->
            <tr>
                <td style="white-space: nowrap;"><?php print _('Life Time (seconds)'); ?></td>
                <td>
                    <input type="number" id="valid-lifetime" name="s[valid-lifetime]" class="form-control input-sm"
                           value="<?php print $curSubnet['valid-lifetime']; ?>" min="3600" required>
                </td>
            </tr>
            <!-- Next server -->
            <tr>
                <td style="white-space: nowrap;"><?php print _('Next server'); ?></td>
                <td>
                    <input type="text" id="next-server" name="s[next-server]" class="form-control input-sm"
                           value="<?php print @$curSubnet['next-server']; ?>">
                </td>
            </tr>
        </table>
        <input type="hidden" id="action" name="action" value="<?php print $_POST['action']; ?>">
        <input type="hidden" id="id" name="s[id]" value="<?php print @$jsData[$_POST['id']]['id']; ?>">
        <input type="hidden" id="subnet" name="s[subnet]" value="<?php print @$jsData[$_POST['id']]['subnet']; ?>">
        <input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
    </form>

</div>

<!-- footer -->
<div class="pFooter">
    <div class="btn-group">
        <button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
        <button class="btn btn-sm btn-default submit_popup readonly-without-static <?php print $_POST['action'] == "delete" ? "btn-danger" : "btn-success"; ?>"
                id="editSubnet" data-script='app/admin/dhcp/edit-subnet-submit.php' data-form='editSubnetDhcp'
                data-result_div="editSubnetResult"><i
                    class="fa <?php print $_POST['action'] == "delete" ? "fa-trash-o" : "fa-check"; ?>"></i> <?php print ucwords(_($_POST['action'])); ?>
        </button>
    </div>
    <!-- Result -->
    <div class="editReservationResult" id="editSubnetResult"></div>
</div>
