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
            <!-- Text input-->
            <div class="form-group">
                <label class="col-md-4 control-label" for="Api server address">Api server address</label>
                <div class="col-md-4">
                    <input id="Api server address" name="Api server address" type="text" placeholder="placeholder" class="form-control input-md">
                    <span class="help-block">Addresses of servers with a port through ":". Multiple servers are separated by commas. For example: 10.0.0.1:8080,10.0.0.2:8080</span>
                </div>
            </div>

            <!-- Multiple Checkboxes -->
            <div class="form-group">
                <label class="col-md-4 control-label" for="services">Use services</label>
                <div class="col-md-4">
                    <div class="checkbox">
                        <label for="services-0">
                            <input type="checkbox" name="services" id="services-0" value="dhcp4">
                            Dhcp4
                        </label>
                    </div>
                    <div class="checkbox">
                        <label for="services-1">
                            <input type="checkbox" name="services" id="services-1" value="dhcp6">
                            Dhcp6
                        </label>
                    </div>
                </div>
            </div>

            <!-- Button -->
            <div class="form-group">
                <label class="col-md-4 control-label" for="save"></label>
                <div class="col-md-4">
                    <button id="save" name="save" class="btn btn-success">Save</button>
                </div>
            </div>

        </fieldset>
    </form>
</div>

<?php
foreach ($dhcp_db as $k=>$s) {
    if(is_array($s)) {
        print $k."<br>";
        foreach ($s as $k2=>$s2) {
        print "&nbsp;&nbsp; $k2: $s2<br>";
        }
    }
    else {
        print "$k: $s<br>";
    }
}