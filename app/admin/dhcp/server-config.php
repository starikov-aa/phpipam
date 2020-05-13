<?php
?>

<br>
<div id="content">
    <form class="form-inline">

        <!-- Textarea -->
        <div class="custom-control custom-checkbox my-1 mr-sm-2">
        <?php
        foreach ($DHCP->get_servers_config() as $s => $c) {
            echo $s;
            echo '<textarea rows=30 cols="80" class="form-control" id="servers" name="servers">' . json_encode($c, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES) . '</textarea>';
        }
        ?>
        </div>

    </form>
</div>