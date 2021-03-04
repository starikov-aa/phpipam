<?php

/**
 * DHCP_kea class to work with isc-dhcp server
 *
 *  It will be called form class.DHCP.php wrapper it kea is selected as DHCP type
 *
 *  http://kea.isc.org/wiki
 *
 *
 */
class DHCP_kea extends Common_functions
{

    /**
     * Location of kea config file
     *
     * (default value: false)
     *
     * @var bool
     * @access private
     */
    private $kea_config_file = "/etc/kea/kea.conf";

    /**
     * Settings to be provided to process kea files
     *
     * (default value: array())
     *
     * @var array
     * @access private
     */
    private $kea_settings = array();

    /**
     * Raw config file
     *
     * (default value: "")
     *
     * @var string
     * @access public
     */
    public $config_raw = "";

    /**
     * Parsed config file
     *
     * (default value: false)
     *
     * @var array|bool
     * @access public
     */
    public $config = false;

    /**
     * Falg if ipv4 is used
     *
     * (default value: false)
     *
     * @var bool
     * @access public
     */
    public $ipv4_used = false;

    /**
     * Flag if ipv6 is used
     *
     * (default value: false)
     *
     * @var bool
     * @access public
     */
    public $ipv6_used = false;

    /**
     * Array to store DHCP subnets, parsed from config file
     *
     *  Format:
     *      $subnets[] = array (pools=>array());
     *
     * (default value: array())
     *
     * @var array
     * @access public
     */
    public $subnets4 = array();

    /**
     * Array to store DHCP subnets, parsed from config file
     *
     *  Format:
     *      $subnets[] = array (pools=>array());
     *
     * (default value: array())
     *
     * @var array
     * @access public
     */
    public $subnets6 = array();

    /**
     * set available lease database types
     *
     * (default value: array("memfile", "mysql", "postgresql"))
     *
     * @var string
     * @access public
     */
    public $lease_types = array("memfile", "mysql", "postgresql");

    /**
     * List of active leases
     *
     * (default value: array())
     *
     * @var array
     * @access public
     */
    public $leases4 = array();

    /**
     * List of active leases
     *
     * (default value: array())
     *
     * @var array
     * @access public
     */
    public $leases6 = array();

    /**
     * Available reservation methods
     *
     * (default value: array("mysql"))
     *
     * @var string
     * @access public
     */
    public $reservation_types = array("file", "mysql");

    /**
     * Definition of hosts reservations
     *
     * (default value: array())
     *
     * @var array
     * @access public
     */
    public $reservations4 = array();
    /**
     * @var array
     */
    public $reservations6 = array();

    /**
     * Database object for leases and hosts
     *
     * (default value: false)
     *
     * @var bool
     * @access protected
     */
    protected $Database_kea = false;


    /**
     * The address of the server from which data will be taken (liza, reservation).
     * By default, this is a server with the "primary" role.
     *
     * @var string
     */
    private $ApiReadServer = "";

    private $LogFile = '';

    /**
     * __construct function.
     *
     * @access public
     * @param array $kea_settings (default: array())
     * @return void
     */
    public function __construct($kea_settings = array())
    {

        $this->LogFile = $_SERVER['DOCUMENT_ROOT'] . "/kea_dhcp.log";

        // save settings
        if (is_array($kea_settings)) {
            $this->kea_settings = $kea_settings;
        } else {
            throw new exception ("Invalid kea settings");
        }

        // set file
        if (isset($this->kea_settings['file'])) {
            $this->kea_config_file = $this->kea_settings['file'];
        }
        $rs = $this->get_server('all', $addr_only = true);
        if ($rs) {
            $this->ApiReadServer = $rs;
        }

        // parse config file on startup
        $this->parse_config();
        // parse and save subnets
        $this->parse_subnets();
    }

    /**
     * Opens database connection if needed for leases and hosts
     *
     * @access private
     * @param mixed $username
     * @param mixed $password
     * @param mixed $host
     * @param mixed $port
     * @param mixed $dbname
     * @param mixed $charset
     * @return void
     */
    private function init_database_conection($username, $password, $host, $port, $dbname)
    {
        // open
        $this->Database_kea = new Database_PDO ($username, $password, $host, $port, $dbname);
    }


    /**
     * This function parses config file and returns it as array.
     *
     * @access private
     * @return void
     */
    private function parse_config_file()
    {
        // get file to array
        if (file_exists($this->kea_config_file)) {
            $config = file($this->kea_config_file);
            // save
            $this->config_raw = implode("\n", array_filter($config));
        } else {
            throw new exception ("Cannot access config file " . $this->kea_config_file);
        }

        // loop and remove comments (contains #) and replace multilpe spaces
        $out = array();
        foreach ($config as $k => $f) {
            if (strpos($f, "#") !== false || strlen($f) == 0) {
            } else {
                if (strlen($f) > 0) {
                    $out[] = $f;
                }
            }
        }

        // join to line
        $config = implode("", $out);

        // validate json
        if ($this->validate_json_string($config) === false) {
            throw new exception ("JSON config file error: $this->json_error");
        }

        // save config
        $this->config = json_decode($config, true);
        // save IPv4 / IPv6 flags
        if (isset($this->config['Dhcp4'])) {
            $this->ipv4_used = true;
        }
        if (isset($this->config['Dhcp6'])) {
            $this->ipv6_used = true;
        }
    }

    /**
     * Saves subnets definition to $subnets object
     *
     * @access private
     * @return void
     */
    private function parse_subnets()
    {
        // save to subnets4 object
        $this->subnets4 = @$this->config['Dhcp4']['subnet4'];
        // save to subnets6 object
        $this->subnets6 = @$this->config['Dhcp6']['subnet6'];
    }









    /* @leases --------------- */

    /**
     * Saves leases to $leases object as array.
     *
     * @access public
     * @param string $type (default: "IPv4")
     * @return void
     */
    public function get_leases2($type = "IPv4")
    {
        // first check where they are stored - mysql, postgres or file
        if ($type == "IPv4") {
            $lease_database = $this->config['Dhcp4']['lease-database'];
        } else {
            $lease_database = $this->config['Dhcp6']['lease-database'];
        }

        // set lease type
        $lease_database_type = $lease_database['type'];

        // validate database type
        if (!in_array($lease_database_type, $this->lease_types)) {
            throw new exception ("Invalid lease database type");
        }

        // get leases
        $lease_type = "get_leases_" . $lease_database_type;
        $this->{$lease_type} ($lease_database, $type);
    }

    /**
     * Fetches leases from memfile.
     *
     *  First line is structure
     *      address,hwaddr,client_id,valid_lifetime,expire,subnet_id,fqdn_fwd,fqdn_rev,hostname,state
     *
     * @access private
     * @param mixed $lease_database
     * @param string $type (default: "IPv4")
     * @return void
     */
    private function get_leases_memfile($lease_database, $type)
    {
        // read file to array
        $leases_from_file = @file($lease_database['name']);
        // first item are titles
        unset($leases_from_file[0]);
        // if leases are present format to array
        if (sizeof($leases_from_file) > 0 && $leases_from_file !== false) {
            // init array
            $leases_parsed = array();
            // loop and save leases
            foreach ($leases_from_file as $l) {
                if (strlen($l) > 1) {
                    // to array
                    $l = explode(",", $l);

                    // set state
                    switch ($l[9]) {
                        case 0:
                            $l[9] = "default";
                            break;
                        case 1:
                            $l[9] = "declined";
                            break;
                        case 2:
                            $l[9] = "expired-reclaimed";
                            break;
                    }
                    // save only active
                    if ($l[4] > time()) {
                        $leases_parsed[$l[0]] = array(
                            "address" => $l[0],
                            "hwaddr" => $l[1],
                            "client_id" => $l[2],
                            "valid_lifetime" => $l[3],
                            "expire" => date("Y-m-d H:i:s", $l[4]),
                            "subnet_id" => $l[5],
                            "fqdn_fwd" => $l[6],
                            "fqdn_rev" => $l[7],
                            "hostname" => $l[8],
                            "state" => $l[9]
                        );
                    }
                }
            }
        } else {
            throw new exception("Cannot read leases file " . $lease_database['name']);
        }

        // save result
        if ($type == "IPv4") {
            $this->leases4 = $leases_parsed;
        } else {
            $this->leases6 = $leases_parsed;
        }
    }

    /**
     * Fetches leases from mysql database.
     *
     * @access private
     * @param mixed $lease_database
     * @param string $type (default: "IPv4")
     * @return void
     */
    private function get_leases_mysql($lease_database, $type)
    {
        // if host not specified assume localhost
        if (strlen($lease_database['host']) == 0) {
            $lease_database['host'] = "localhost";
        }
        // open DB connection
        $this->init_database_conection($lease_database['user'], $lease_database['password'], $lease_database['host'], 3306, $lease_database['name']);
        // set query
        if ($type == "IPv4") {
            $query = "select ";
            $query .= "INET_NTOA(address) as `address`, hex(hwaddr) as hwaddr, hex(`client_id`) as client_id,`subnet_id`,`valid_lifetime`,`expire`,`name` as `state`,`fqdn_fwd`,`fqdn_rev`,`hostname` from `lease4` as a, ";
            $query .= "`lease_state` as s where a.`state` = s.`state`;";
        } else {
            throw new Exception("IPv6 leases not yet!");
        }
        // fetch leases
        try {
            $leases = $this->Database_kea->getObjectsQuery($query);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        // save leases
        if (sizeof($leases) > 0) {
            // we need array
            $result = array();
            // loop
            foreach ($leases as $k => $l) {
                $result[$k] = (array)$l;
            }

            // save
            if ($type == "IPv4") {
                $this->leases4 = $result;
            } else {
                $this->leases6 = $result;
            }
        }
    }

    /**
     * Fetches leases from postgres SQL.
     *
     * @access private
     * @param mixed $lease_database
     * @return void
     */
    private function get_leases_postgresql($lease_database)
    {
        throw new exception ("PostgresSQL not supported");
    }









    /* @reservations --------------- */

    /**
     * Saves reservations to $reservations object as array.
     *
     *  Note:
     *      For IPv4 reservations KEA by default uses `reservations` item under subnet4 > reservations array.
     *      It can also use hosts-database in MySQL, if hosts-database is set
     *
     *
     *  For KEA v 1.0 only MySQL is supported. If needed later item can be added to $reservation_types and new method created
     *
     * @access public
     * @param string $type (default: "IPv4")
     * @return void
     * @throws exception
     */
    public function get_reservations($type = "IPv4")
    {
        // first check where they are stored - mysql, postgres or file
        if ($type == "IPv4") {
            if (isset($this->config['Dhcp4']['hosts-database'])) {
                $reservations_database = $this->config['Dhcp4']['hosts-database'];
            } else {
                $reservations_database = false;
            }
        } else {
            if (isset($this->config['Dhcp4']['hosts-database'])) {
                $reservations_database = $this->config['Dhcp6']['hosts-database'];
            } else {
                $reservations_database = false;
            }
        }


        // first check reservations under subnet > reservations, can be both
        $this->get_reservations_config_file($type);

        // if set in config check also database
        if ($reservations_database !== false) {
            // set lease type
            $reservations_database_type = $reservations_database['type'];

            // id database type is set and valid check it also
            if (!in_array($reservations_database_type, $this->reservation_types)) {
                throw new exception ("Invalid reservations database type");
            } else {
                // get leases
                $type_l = "get_reservations_" . $reservations_database_type;
                $this->{$type_l} ($reservations_database, $type);
            }
        }
    }

    /**
     * Fetches leases from memfile.
     *
     *  https://kea.isc.org/wiki/HostReservationDesign
     *
     * @access private
     * @param mixed $type
     * @return void
     */
    private function get_reservations_config_file($type)
    {
        // read file
        if ($type == "IPv4") {
            // check if set
            if (isset($this->config['Dhcp4']['subnet4'])) {
                foreach ($this->config['Dhcp4']['subnet4'] as $s) {
                    // set
                    if (isset($s['reservations'])) {
                        // save id
                        unset($s_id);
                        $s_id = isset($s['id']) ? $s['id'] : "";
                        // loop
                        foreach ($s['reservations'] as $r) {
                            $this->reservations4[$r['ip-address']] = array(
                                "location" => "Config file",
                                "hw-address" => $r['hw-address'],
                                "ip-address" => $r['ip-address'],
                                "hostname" => $r['hostname'],
                                "subnet-id" => $s_id,
                                "next-server" => $r['next-server'],
                                "boot-file-name" => $r['boot-file-name'],
                                "subnet" => $r['subnet']
                            );
                            // options
                            if (isset($r['option-data'])) {
                                $this->reservations4[$r['ip-address']]['options']['option-data'] = $r['option-data'];
                            }
                            // classes
                            if (isset($r['client-classes'])) {
                                $this->reservations4[$r['ip-address']]['options']['client-classes'] = $r['client-classes'];
                            }

                            // reformat
                            //$this->reservations4[$r['ip-address']] = $this->reformat_empty_array_fields($this->reservations4[$r['ip-address']], "/-");
                        }
                    }
                }
            }
        } else {
            //$this->reservations6 = $reservations_database['name'];
        }
    }

    /**
     * Fetches leases from mysql database.
     *
     * @access private
     * @param mixed $reservations_database //database details
     * @param mixed $type //ipv4 / ipv6
     * @return void
     */
    private function get_reservations_mysql($reservations_database, $type)
    {
        // if host not specified assume localhost
        if (strlen($reservations_database['host']) == 0) {
            $reservations_database['host'] = "localhost";
        }
        // open DB connection
        $this->init_database_conection($reservations_database['user'], $reservations_database['password'], $reservations_database['host'], 3306, $reservations_database['name']);
        // set query
        if ($type == "IPv4") {
            $query = "select 'MySQL' as 'location', `dhcp4_subnet_id`, `ipv4_address` as `ip-address`, HEX(`dhcp_identifier`) as `hw-address`, `hostname` from `hosts`;";
        } else {
            $query = "select * from `hosts`;";
        }
        // fetch leases
        try {
            $reservations = $this->Database_kea->getObjectsQuery($query);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        // save leases
        if (sizeof($reservations) > 0) {
            // we need array
            $result = array();
            // loop
            foreach ($reservations as $k => $l) {
                // check for subnet
                if ($l->dhcp4_subnet_id !== 0 && strlen($l->dhcp4_subnet_id) > 0) {
                    if ($type == "IPv4") {
                        foreach ($this->subnets4 as $s) {
                            if ($s['id'] == $l->dhcp4_subnet_id) {
                                $l->subnet = $s['subnet'];
                            }
                        }
                    } else {
                        foreach ($this->subnets6 as $s) {
                            if ($s['id'] == $l->dhcp6_subnet_id) {
                                $l->subnet = $s['subnet'];
                            }
                        }
                    }
                }

                // save
                if ($type == "IPv4") {
                    $this->reservations4[] = (array)$l;
                } else {
                    $this->reservations6[] = (array)$l;
                }
            }
        }
    }


    /**
     *
     */
    public function read_statistics()
    {
        //$this->
    }

    public function gen_error_msg($array)
    {
        $result = [];
        $array = isset($array['server']) ? [$array] : $array;
        foreach ($array as $item) {
            $result[] = "<br> Server: " . $item['server'] .
                "<br> Status: " . $item['status'] .
                "<br> Text: " . $item['msg'];
        }
        $text = join($result, '');
        $this->_log_($text);
        return $text;
    }

    /**
     * @param $name
     * @param string $type
     * @return string
     */
    function get_service_name($name, $type = 'IPv4')
    {
        if ($name == 'dhcp') {
            $service = $type == 'IPv4' ? 'Dhcp4' : 'Dhcp6';
        }
        return $service;
    }

    /**
     * Отправляет запросы к АПИ
     *
     * @param $command
     * @param string $service
     * @param string $arguments
     * @param string $server
     * @return bool|string|array
     * @throws exception
     */
    public function api_request($command, $service = '', $arguments = null, $server = '', $exec_all_server = false)
    {
        $result = false;
        $srv = [];

        $cmd['command'] = $command;

        if (empty($command)) {
            throw new exception ("api_request: command is empty");
        }

        if (!empty($service)) {
            $cmd['service'][] = strtolower($service);
        }

        if (is_array($arguments)) {
            $cmd['arguments'] = $arguments;
        }

        $cmd = json_encode($cmd, JSON_UNESCAPED_SLASHES);

        if (empty($server)) {
            $srv = $this->ApiReadServer;
        } elseif (!empty($server) && !is_array($server)) {
            $srv[] = $server;
        } elseif (is_array($server)) {
            $srv = $server;
        } else {
            throw new exception ('api_request: Servers parse error. Current value: ' . print_r($server, true));
        }

        foreach ($srv as $s) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://' . $s);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $cmd);
            $raw = json_decode(curl_exec($ch), true);
            curl_close($ch);

            if (isset($raw[0]['result'])) {
                $result[] = [
                    'server' => $s,
                    'data' => $raw[0]['arguments'],
                    'msg' => $raw[0]['text'],
                    'status' => $raw[0]['result']
                ];
            } else {
                throw new exception ("api_request: Error execute command: " . $cmd . "<pre>" . print_r($raw . " / server: " . $s, true));
            }

            if (!$exec_all_server) {
                break;
            }

            //print_r($this->kea_settings). "\n\n\n";
            //print_r($result);
        }
        return $exec_all_server ? $result : $result[0];
    }

    /**
     * @throws exception
     */
    public function parse_config($type = 'IPv4')
    {
        $raw_v4 = $this->api_request('config-get', 'dhcp4');
        if ($raw_v4) {
            $this->config = $raw_v4['data'];
            if (isset($raw_v4['data']['Dhcp4'])) {
                $this->ipv4_used = true;
            }
            //if (isset($raw_v4['data']['Dhcp4'])) {
            //$this->ipv6_used = true;
            //}
        }
    }

    /**
     * Apply config and write to file
     *
     * @param $service
     * @param $arguments
     * @throws exception
     */
    public function write_config($service, $arguments)
    {
        $servers = $this->get_server('primary');
        if ($servers) {
            foreach ($servers as $s) {

                foreach ($arguments['Dhcp4']['hooks-libraries'] as $key => $arg) {
                    if (isset($arg['parameters']['high-availability'])) {
                        $arguments['Dhcp4']['hooks-libraries'][$key]['parameters']['high-availability'][0]['this-server-name'] = $s['name'];
                    }
                }

                $cs = $this->api_request('config-set', $service, $arguments, $s['api_addr']);

                if ($cs['status'] != 0) {
                    throw new exception ("Set new config fail" . $this->gen_error_msg($cs));
                } else {
                    $cw = $this->api_request('config-write', $service, $s['api_addr']);
                    if ($cw['status'] != 0) {
                        throw new exception ("Write new config to file fail" . $this->gen_error_msg($cw));
                    }
                }
            }
        }
    }

    public function write_reservation($ip, $mac, $subnet_id = null, $additional_settings = [], $backend = 'config', $type = 'IPv4')
    {
        if ($backend == 'config') {
            //throw new exception ($ip .' - '. $mac .' - '. $subnet_id .' - '. $type);
            $this->write_reservation_to_config($ip, $mac, $subnet_id, $additional_settings, $type);
        }
    }

    /**
     * @param $ip IP
     * @param $mac MAC
     * @param $subnet_id
     * @param string $type IPv4 or IPv6
     * @throws exception
     */
    private function write_reservation_to_config($ip, $mac, $subnet_id, $additional_settings = [], $type = 'IPv4')
    {
        $ipv = $type == 'IPv4' ? '4' : '6';
        $service = $this->get_service_name('dhcp', $type);
        $result = $this->config;

        // ищем номер подсети в массиве сетей
        $subnet_id_found = array_search($subnet_id, array_column($result[$service]['subnet' . $ipv], 'id'));

        // нашли подсеть
        if ($subnet_id_found !== false) {
            $subnet = &$result[$service]['subnet' . $ipv][$subnet_id_found];

            // получаем список резервирований в подсети
            $r_list = $subnet['reservations'];

            // ищем IP среди зарезервированых
            $ip_num = array_search($ip, array_column($r_list, 'ip-address'));

            // ищем MAC среди зарезервированых
            $mac_num = array_search($mac, array_column($r_list, 'hw-address'));

            // ip & mac совпадают, значит редактируем какие то опции
            if ($r_list[$ip_num]['ip-address'] == $ip && $r_list[$ip_num]['hw-address'] == $mac) {
                // обновляем какие то доп. опции
                $tmp = $r_list[$ip_num];
                $subnet['reservations'][$ip_num] = array_merge($tmp, $additional_settings);
                $this->_log_('write_reserv: MAC & ip found');

            } // найдено резервирование с заданыи IP, но мак другой
            elseif ($ip_num !== false && $mac_num === false) {
                // тогда у записи обновляем MAC
                $subnet['reservations'][$ip_num]['hw-address'] = $mac;
                $this->_log_('write_reserv: MAC found, ip not found');

            } // найдено резервирование с заданыи Mac, но IP другой
            elseif ($mac_num !== false && $ip_num === false) {
                // тогда у записи обновляем IP
                $subnet['reservations'][$mac_num]['ip-address'] = $ip;
                $this->_log_('write_reserv: IP found, MAC not found');

            } // мак и IP не найдены, созадем новое резервирование
            elseif ($ip_num === false && $mac_num === false) {
                if ($this->isIpInRange($ip, $subnet['subnet'])) {
                    $subnet['reservations'][] = [
                        'ip-address' => $ip,
                        'hw-address' => $mac
                    ];
                    $this->_log_('write_reserv: MAC & ip not found');
                } else {
                    throw new exception ("Ip " . $ip . " is not on subnet " . $subnet_id);
                }
            }

            // удаляем старые лизы чтобы небыло путаницы
            try {
                $this->delete_lease($ip, $type);
            } catch (Exception $e) {
                $this->_log_('delete_lease: ' . $e->getMessage());
            }

            $this->write_config($service, $result);

        }
    }


    /**
     * Gets the leases of the specified type
     *
     * @param string $type IPv4 or IPv6
     * @throws exception
     */
    public function get_leases($type = 'IPv4')
    {
        $result = [];
        $ipv = $type == 'IPv4' ? '4' : '6';
        $service = $this->get_service_name('dhcp', $type);

        if (!$this->ipv4_used && $type == 'IPv4') return;
        if (!$this->ipv6_used && $type == 'IPv6') return;

        $raw = $this->api_request('lease' . $ipv . '-get-all', $service);

        if ($raw['status'] === 0) {
            $leases = $raw['data']['leases'];
            foreach ($leases as $item) {
                $result[$item['ip-address']] = [
                    "ip-address" => $item['ip-address'],
                    "hw-address" => $item['hw-address'],
                    "client_id" => $item['client-id'],
                    "valid_lifetime" => $item['valid-lft'],
                    "expire" => date("Y-m-d H:i:s", ($item['cltt'] + $item['valid-lft'])),
                    "subnet_id" => $item['subnet-id'],
                    "fqdn_fwd" => $item['fqdn-fwd'],
                    "fqdn_rev" => $item['fqdn-rev'],
                    "hostname" => $item['hostname'],
                    "state" => $item['state']
                ];
            }

            if ($type == 'IPv4') {
                $this->leases4 = $result;
            } else {
                $this->leases6 = $result;
            }

        } else {
            throw new exception ("can't get leases" . $this->gen_error_msg($raw));
        }
    }

    /**
     * Removes lease with the given type
     *
     * @param $ip
     * @param string $type IPv4 or IPv6
     * @throws exception
     */
    public function delete_lease($ip, $type = 'IPv4')
    {
        $ipv = $type == 'IPv4' ? '4' : '6';
        $raw = $this->api_request('lease' . $ipv . '-del', $this->get_service_name('dhcp', $type), ['ip-address' => $ip], '', true);

        foreach ($raw as $item) {
            if ($item['status'] !== 0) {
                throw new exception ("can't delete leases " . $ip . $this->gen_error_msg($item));
            }
        }

        $raw2 = $this->api_request('leases-reclaim', $this->get_service_name('dhcp', $type), ['remove' => false], '', true);

        foreach ($raw2 as $item2) {
            if ($item2['status'] !== 0) {
                throw new exception ("can't reclaim leases " . $ip . $this->gen_error_msg($item2));
            }
        }

    }

    /**
     * @param $ip
     * @param string $type
     * @throws exception
     */
    public function delete_reservation($ip, $type = 'IPv4')
    {
        $ipv = $type == 'IPv4' ? '4' : '6';
        $service = $this->get_service_name('dhcp', $type);
        $subnetList = $this->config[$service]['subnet' . $ipv];
        $result = $this->config;

        foreach ($subnetList as $subnet_num => $subnet) {
            if ($this->isIpInRange($ip, $subnet['subnet'])) {
                echo 'found in: ' . $subnet['subnet'];
                $key_to_delete = array_search($ip, array_column($subnet['reservations'], 'ip-address'));
                array_splice($result[$service]['subnet' . $ipv][$subnet_num]['reservations'], $key_to_delete, 1);
            }
        }

        $this->write_config($service, $result);
    }

    public function get_server($role = 'all', $addr_only = false)
    {
        $servers = $this->kea_settings['servers'];
        $result = false;
        foreach ($servers as $srv) {
            if ($srv['role'] == $role || $role == 'all') {
                if (!$addr_only) {
                    $result[] = [
                        "addr" => $srv['addr'],
                        "api_addr" => $srv['addr'] . ":" . $srv['port'],
                        "role" => $srv['role'],
                        "name" => $srv['name']
                    ];
                } else {
                    $result[] = $srv['addr'] . ":" . $srv['port'];
                }
            }
        }

        return $result;
    }

    public function get_servers_status()
    {
        $result = [];
        $servers = $this->get_server('all');
        if ($servers) {
            foreach ($servers as $s) {
                try {
                    $result[$s['addr']] = array_merge($this->api_request("ha-heartbeat", "dhcp4", "", $s['api_addr']), $s);
                } catch (Throwable $e) {
                    $result[$s['addr']]['status'] = "Not available";
                }
            }
        }
        return print_r($result, true);
    }

    public function get_servers_config()
    {
        $result = [];
        $servers = $this->get_server('all');
        if ($servers) {
            foreach ($servers as $s) {
                try {
                    $result[$s['name']] = $this->api_request('config-get', 'dhcp4', '', $s['api_addr'])['data'];
                } catch (Throwable $e) {
                    $result[$s['name']] = "Not available";
                }
            }
        }
        return $result;
    }

    public function write_subnet($data, $type = 'IPv4')
    {
        $ipv = $type == 'IPv4' ? '4' : '6';
        $service = $this->get_service_name('dhcp', $type);
        $result = $this->config;

        $currentSubnetNum = $this->findInAssocArray($this->subnets4, 'id', $data['id'], true);

        if ($currentSubnetNum !== false) {
            $result[$service]['subnet' . $ipv][$currentSubnetNum] = $data;
        } else {
            $result[$service]['subnet' . $ipv][] = $data;
        }
        $this->write_config($service, $result);
    }

    public function add_lease($ip, $hw_addr, $subnet_id)
    {

    }

    function _msg_($error_text, $gen_except = false, $write_log = true)
    {

    }

    function _log_($text)
    {
        $f = fopen($this->LogFile, 'a+');
        fwrite($f, date("Y-m-d H:i:s") . " file: " . __FILE__ . " line: " . __LINE__ . " func: " . __METHOD__ . " msg: " . $text . "\r\n");
        fclose($f);
    }
}