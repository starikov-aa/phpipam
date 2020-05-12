<?php
?>

<br>
<div id="content">
    <form class="form-horizontal">
        <fieldset>
            <!-- Textarea -->
            <div class="form-group">
                <div class="col-md-5">
                    <?php
                    foreach ($DHCP->get_servers_config() as $s => $c) {
                        echo "<h4>$s</h4>";
                        echo '<textarea rows=20 class="form-control" id="servers" name="servers">' . json_encode($c, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES) . '</textarea>';
                    }
                    ?>
                </div>
            </div>
        </fieldset>
    </form>
</div>