<?php

/**
 * @file
 *
 * Watchdog
 */

namespace Pyramid\Component\Watchdog;

class Watchdog {
    
    //日志版本
    protected static $version = 1;
    
    //记录
    public static function write($data = array(), $type = 'access') {
        if (!isset($data['servername']) || !isset($data['remoteip']) || !isset($data['bundle'])) {
            $data['bundle'] = 'SYSEXC';
        }
        if (isset($data['url']) && ($p = parse_url($data['url']))) {
            $data += $p;
        }
        $data += array(
            'servername'  => '',
            'remoteip'    => '',
            'ssid'        => '',
            'uid'         => 0,
            'datetime'    => time(),
            'exectime'    => 0,
            'method'      => 'GET',
            'referer'     => '',
            'agent'       => '',
            'scheme'      => 'http',
            'query'       => '',
            'path'        => '/',
            'host'        => '',
            'port'        => 80,
        );
        $entry = array(
            ':version'     => static::$version,
            ':servername'  => $data['servername'],
            ':remoteip'    => $data['remoteip'],
            ':bundle'      => strtoupper($data['bundle']),
            ':ssid'        => $data['ssid'],
            ':uid'         => (int) $data['uid'],
            ':datetime'    => substr($data['datetime'],0,10),
            ':exectime'    => (int) $data['exectime'],
            ':method'      => strtoupper($data['method']),
            ':referer'     => $data['referer'],
            ':agent'       => $data['agent'],
            ':scheme'      => strtolower($data['scheme']),
            ':query'       => $data['query'],
            ':path'        => strtolower($data['path']),
            ':host'        => strtolower($data['host']),
            ':port'        => (int) $data['port'],
        );
        Storage::write($type, $entry);
    }
    
}
