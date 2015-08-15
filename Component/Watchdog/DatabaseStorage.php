<?php

/**
 * @file
 *
 * Watchdog Storage
 */

namespace Pyramid\Component\Watchdog;

use Pyramid\Component\Database\Database;

/**
 * database storage
 */
class DatabaseStorage {
    
    protected $target;
    
    public function __construct($target = 'default') {
        $this->target = $target;
    }
    
    public function write(array $data) {
        Database::getConnection($this->target)->query(
            "INSERT INTO {watchdog} (
                version,
                servername,
                remoteip,
                bundle,
                ssid,
                uid,
                datetime,
                exectime,
                method,
                referer,
                agent,
                scheme,
                query,
                path,
                host,
                port
            )
            VALUES (
                :version, 
                :servername, 
                :remoteip, 
                :bundle, 
                :ssid, 
                :uid, 
                :datetime, 
                :exectime, 
                :method, 
                :referer, 
                :agent, 
                :scheme, 
                :query, 
                :path, 
                :host, 
                :port
            )",
            $data
        );
    }

}
