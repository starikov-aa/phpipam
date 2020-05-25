<?php

/**
 * Script to edit / add / delete groups
 *************************************************/

# verify that user is logged in
$User->check_user_session();
# perm check
$User->check_module_permissions ("dhcp", User::ACCESS_R, true, false);
?>

<br>
<div id="content">
    <form class="form-horizontal">
        <fieldset>
            <!-- Textarea -->
            <div class="form-group">
                <label class="col-md-4 control-label" for="servers">Servers</label>
                <div class="col-md-4">
                    <textarea class="form-control" id="servers" name="servers">config in json format</textarea>
                </div>
            </div>
            <div class="form-group">
                <label class="col-md-4 control-label" for="read_server">С какого сервера читать</label>
                <div class="col-md-4">
                    <select class="form-control" id="read_server" name="read_server"></select>
                </div>
            </div>
            <div class="form-group">
                <label class="col-md-4 control-label" for="read_server">На какой сервер писать</label>
                <div class="col-md-4">
                    <select class="form-control" id="write_server" name="read_server"></select>
                </div>
            </div>

            <!-- Multiple Checkboxes -->
            <div class="form-group">
                <label class="col-md-4 control-label" for="dhcpuse">DHCP server usage</label>
                <div class="col-md-4">
                    <div class="checkbox">
                        <label for="dhcpuse-0">
                            <input type="checkbox" name="dhcpuse" id="dhcpuse-0" value="v4">
                            DHCPv4
                        </label>
                    </div>
                    <div class="checkbox">
                        <label for="dhcpuse-1">
                            <input type="checkbox" name="dhcpuse" id="dhcpuse-1" value="v6">
                            DHCPv6
                        </label>
                    </div>
                </div>
            </div>

            <!-- Button -->
            <div class="form-group">
                <label class="col-md-4 control-label" for="save"></label>
                <div class="col-md-4">
                    <button id="save" name="save" class="btn btn-primary">Save</button>
                </div>
            </div>

        </fieldset>
    </form>
</div>

<?php

