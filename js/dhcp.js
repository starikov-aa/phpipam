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
    let mask, base_ip, long_ip = ip4ToInt(ip);
    if ((mask = subnet.match(/^(.*?)\/(\d{1,2})$/)) && ((base_ip = ip4ToInt(mask[1])) >= 0)) {
        let freedom = Math.pow(2, 32 - parseInt(mask[2]));
        return (long_ip > base_ip || long_ip === base_ip) && ((long_ip < base_ip + freedom - 1) || (long_ip === base_ip + freedom - 1));
        // return (long_ip > base_ip) && (long_ip < base_ip + freedom - 1);
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

/**
 * MAC address validation
 *
 * @param address MAC
 * @returns {boolean} True is valid otherwise false
 */
function validateMacAddress(address) {
    let regex = /^([0-9A-F]{2}[:-]){5}([0-9A-F]{2})$/i;
    return regex.test(address);
}

/**
 * Bouncer custom validate
 * Checks an IP or pool (start ip - end ip) to the selected subnet (CIDR).
 * For the field in which the IP or pool is indicated, two attributes must be set:
 * data-valid-inSubNet-type, values:
 * - pool - if a pool of addresses is specified
 * - ip - if the field contains one address
 * data-valid-inSubNet-network - a field selector with a subnet must be specified as a value, for example ID - #subnet
 *
 * @param field Check field
 * @returns {boolean} False is valid otherwise true
 */
function validateFuncInSubNet(field) {
    let type = field.getAttribute('data-valid-inSubNet-type');
    let selector = field.getAttribute('data-valid-inSubNet-network');
    if (!selector || !type) return false;

    let network = document.querySelector(selector);
    if (!network) return false;

    network = network.selectedOptions[0].text.match(/^(.*\/\d{1,2})/)[1]

    if (field.value === '' && !field.required) {
        return false;
    } else if (type === 'ip' && /^(\d{1,3}.){3}\d{1,3}$/.test(field.value)) {
        return !inSubNet(field.value, network);
    } else if (type === 'pool' && /^(\d{1,3}.){3}\d{1,3}-(\d{1,3}.){3}\d{1,3}$/.test(field.value)) {
        let pool = field.value.split('-');
        return !(inSubNet(pool[0], network) && inSubNet(pool[1], network));
    }

    return true;
}

/**
 * Bouncer custom message for validateFuncInSubNet()
 *
 * @param field Check field
 * @returns {string} The message text
 */
function validateMsgInSubNet(field) {
    return 'The specified IP or pool is not included in the selected subnet';
}

/**
 * Checks which theme is used
 *
 * @returns {boolean} True - dark, false - white
 */
function isCurrentThemeDark(){
    return /bg-light/.test($('body').css('background'));
}

/**
 * Returns the name of the class for the Bouncer depending on the current theme of the site
 *
 * @returns {string}
 */
function getBouncerErrorClass() {
    return isCurrentThemeDark() ? 'valid-error-message_dark' : 'valid-error-message_white';
}
