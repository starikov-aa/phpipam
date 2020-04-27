<?php

/**
 * @param $ip
 * @param $IpRange
 * @return bool
 */
function isIpInRange($ip, $IpRange) {
    $IpRange = explode('/', $IpRange);
    $range_start  = ip2long($IpRange[0]);
    $range_end  = $range_start + pow(2, 32-intval($IpRange[1])) - 1;
    $ip = ip2long($ip);
    return ($ip >=$range_start && $ip <= $range_end) ? true : false;
}