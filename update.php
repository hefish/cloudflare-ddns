#!/usr/bin/php

<?php
/**
 * Created by PhpStorm.
 * User: hefish
 * Date: 2018/6/24
 * Time: 21:14
 */

require_once "config.php";



class UpdateDNS {

    protected $record_id;
    protected $ip;
    protected $last_update;


    function __construct()
    {
        global $config;

        if (file_exists($config['data-file'])) {
            $data = json_decode(file_get_contents($config['data-file']), TRUE);
            $this->record_id = $data['record_id'];
            $this->ip = $data['ip'];
            $this->last_update = $data['last_update'];
        }else {
            $this->record_id = NULL;
            $this->ip = NULL;
            $this->last_update = 0;
        }
    }

    function __destruct()
    {
        global $config;

        $data = [
            'record_id' => $this->record_id,
            'ip' => $this->ip,
            'last_update' => $this->last_update
        ];
        file_put_contents($config['data-file'], json_encode($data));
    }

    public function update() {
        global $config;

        $now_ip = file_get_contents("http://api.ipify.org/");
        if ($now_ip == $this->ip) {
            print "no change  \n";
            print "{$config['sub_domain']} => {$this->ip} \n";
            return ;
        }



        $key = new Cloudflare\API\Auth\APIKey($config['user'], $config['api_key']);
        $adapter = new Cloudflare\API\Adapter\Guzzle($key);
        $zones = new Cloudflare\API\Endpoints\Zones($adapter);

        $zone_id = $zones->getZoneID($config['domain']);

        $dns = new Cloudflare\API\Endpoints\DNS($adapter);

        if ($this->record_id != NULL) {
            if ($dns->updateRecordDetails($zone_id, $this->record_id, [
                'type' => 'A',
                'name' => $config['sub_domain'],
                'content' => $now_ip
            ])) {
                echo 'ok. ';
                echo $config['sub_domain']." => ". $now_ip. "\n";
                $this->ip = $now_ip;
                $this->last_update = time();
            }
            else {
                echo 'update failed \n';
                $this->record_id = NULL;
            }

        }
        else {

            foreach ($dns->listRecords($zone_id)->result as $record) {
                if ($record->name == $config['sub_domain']) {
                    $this->record_id = $record->id;
                    break;
                }
            }

            if ($dns->updateRecordDetails($zone_id, $this->record_id, [
                'type' => 'A',
                'name' => $config['sub_domain'],
                'content' => $now_ip
            ])) {
                echo 'ok. ';
                echo $config['sub_domain']." => ". $now_ip. "\n";
                $this->ip = $now_ip;
                $this->last_update = time();
            }
            else {
                echo 'update failed \n';
                $this->record_id = NULL;
            }

        }





    }
}


$o = new UpdateDNS();
$o->update();
