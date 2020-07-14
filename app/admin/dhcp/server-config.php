<?php

if (isset($_POST['syncbut'])){
    //$Database = new Database_PDO;
    //$User = new User ($Database);
    //$kea_dhcp = new DHCP_kea(json_decode($User->settings->DHCP, true)['kea']);
}

?>

<br>
<div id="content">
    <form name="syncconfig" class="form-inline" method="post" action="app/admin/dhcp/server-config.php">

        <!-- Textarea -->
        <div class="custom-control custom-checkbox my-1 mr-sm-2">
        <?php
        foreach ($DHCP->get_servers_config() as $s => $c) {
            echo $s;
            echo '<textarea rows=30 cols="80" class="form-control" id="servers" name="servers">' . json_encode($c, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES) . '</textarea>';
        }
        ?>
            <input type="submit" name="syncbut" id="syncbut" value="Sync server config">
        </div>


    </form>
</div>