<?php

/**
 * @file
 *
 * Watchdog Report
 */

namespace Pyramid\Component\Watchdog;

class Report {

    protected $results = array();
    
    /**
     * 解析cvs文件
     *
     * @param
     *
     */
    public function csv($start, $end = null, $bundle = null, $type = 'access') {
        if (!$end) {
            $end = time();
        }
        if ($bundle) {
            $bundles = (array) $bundle;            
        }
        $bundles[] = '#';
        $file = Storage::getStorageFile($type, date('Ymd', $start));
        $storage = new FileStorage($file, 'rb');
        while ($v = $storage->read()) {
            $this->parse($v, $start, $end, $bundles);
        }
        
        return $this->results;
    }
    
    /**
     * 获取解析结果
     */
    public function getResults() {
        return $this->results;
    }
    
    /**
     * 执行解析
     */
    public function parse($v, $start, $end, $bundles) {
        list($version, $servername, $remoteip,
             $vbundle, $ssid, $uid, $datetime,
             $exectime, $method, $referer,
             $agent, $scheme, $query, 
             $path, $host, $port) = $v;        

        if ($datetime >= $start && $datetime <= $end) {
            foreach ($bundles as $bundle) {
                if ($bundle == '#' || $bundle == $vbundle) {
                    $this->parseIp($bundle, $remoteip);
                    $this->parsePerformance($bundle, $exectime);
                }
            }
        }
    }
    
    //独立IP
    protected function parseIp($bundle, $remoteip) {
        if (!isset($this->results[$bundle]['ip'][$remoteip])) {
            $this->results[$bundle]['ip'][$remoteip] = 0;
        }
        $this->results[$bundle]['ip'][$remoteip]++;
    }
    
    //性能
    protected function parsePerformance($bundle, $exectime) {
        if (!isset($this->results[$bundle]['performance'])) {
            $this->results[$bundle]['performance'] = array(
                '0-100'     => 0,
                '100-500'   => 0,
                '500-1000'  => 0,
                '1000-2000' => 0,
                '2000-3000' => 0,
                '3000-4000' => 0,
                '4000+'     => 0,
            );
        }
        if ($exectime > 4000) {
            $this->results[$bundle]['performance']['4000+']++;
        } elseif ($exectime > 3000) {
            $this->results[$bundle]['performance']['3000-4000']++;
        } elseif ($exectime > 2000) {
            $this->results[$bundle]['performance']['2000-3000']++;
        } elseif ($exectime > 1000) {
            $this->results[$bundle]['performance']['1000-2000']++;
        } elseif ($exectime > 500) {
            $this->results[$bundle]['performance']['500-1000']++;
        } elseif ($exectime > 100) {
            $this->results[$bundle]['performance']['100-500']++;
        } else {
            $this->results[$bundle]['performance']['0-100']++;
        }
    }

}
