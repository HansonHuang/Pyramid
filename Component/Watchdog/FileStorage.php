<?php

/**
 * @file
 *
 * Watchdog Storage
 */

namespace Pyramid\Component\Watchdog;

/**
 * csv file storage
 */
class FileStorage {
    
    protected $handler;
    
    public function __construct($file, $mode = 'ab') {
        $this->handler = fopen($file, $mode);
    }
    
    public function write($data) {
        flock($this->handler, LOCK_EX);
        fputcsv($this->handler, $data, "\t");
        flock($this->handler, LOCK_UN);
    }
    
    public function read() {
        return fgetcsv($this->handler, 1024, "\t");
    }
    
    public function __destruct() {
        $this->handler && fclose($this->handler);
    }

}
