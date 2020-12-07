function ip4ToInt(ip) {
    return ip.split('.').reduce((int, oct) => (int << 8) + parseInt(oct, 10), 0) >>> 0;
}

function intToIp4(int) {
    return [(int >>> 24) & 0xFF, (int >>> 16) & 0xFF,
        (int >>> 8) & 0xFF, int & 0xFF].join('.');
}

function calculateCidrRange(cidr) {
    const [range, bits = 32] = cidr.split('/');
    const mask = ~(2 ** (32 - bits) - 1);
    return [intToIp4(ip4ToInt(range) & mask), intToIp4(ip4ToInt(range) | ~mask)];
}

function inSubNet(ip, subnet) {
    var mask, base_ip, long_ip = ip2long(ip);
    if ((mask = subnet.match(/^(.*?)\/(\d{1,2})$/)) && ((base_ip = intToIp4(mask[1])) >= 0)) {
        var freedom = Math.pow(2, 32 - parseInt(mask[2]));
        return (long_ip > base_ip || long_ip === base_ip) && ((long_ip < base_ip + freedom - 1) || (long_ip === base_ip + freedom - 1));
    } else return false;
}

function compareSubnet(subnetA, subnetB) {
    [addressA, maskA] = subnetA.split('/');
    [addressB, maskB] = subnetB.split('/');
    ipCmp = ip4ToInt(addressB) - ip4ToInt(addressA);
    if (ipCmp > 0) {
        return -1;
    } else if (ipCmp < 0) {
        return 1;
    } else {
        if (maskA < maskB) {
            return 1;
        } else if (maskA > maskB) {
            return -1;
        } else {
            return 0;
        }
    }
}