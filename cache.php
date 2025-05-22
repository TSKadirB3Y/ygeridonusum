<?php
class Cache {
    private $cache_dir;
    private $cache_time;

    public function __construct($cache_dir = 'cache', $cache_time = 3600) {
        $this->cache_dir = $cache_dir;
        $this->cache_time = $cache_time;

        // Cache dizini yoksa oluştur
        if (!file_exists($this->cache_dir)) {
            mkdir($this->cache_dir, 0777, true);
        }
    }

    public function start($key) {
        $cache_file = $this->cache_dir . '/' . md5($key) . '.cache';
        
        // Cache dosyası var mı ve süresi geçmemiş mi kontrol et
        if (file_exists($cache_file) && (time() - filemtime($cache_file) < $this->cache_time)) {
            // Cache'den oku
            $content = file_get_contents($cache_file);
            echo $content;
            return true;
        }
        
        // Cache yok veya süresi geçmiş, yeni cache oluştur
        ob_start();
        return false;
    }

    public function end($key) {
        $cache_file = $this->cache_dir . '/' . md5($key) . '.cache';
        $content = ob_get_clean();
        
        // Cache dosyasına yaz
        file_put_contents($cache_file, $content);
        echo $content;
    }

    public function clear($key = null) {
        if ($key === null) {
            // Tüm cache'i temizle
            $files = glob($this->cache_dir . '/*.cache');
            foreach ($files as $file) {
                unlink($file);
            }
        } else {
            // Belirli bir cache'i temizle
            $cache_file = $this->cache_dir . '/' . md5($key) . '.cache';
            if (file_exists($cache_file)) {
                unlink($cache_file);
            }
        }
    }

    public function setHeaders() {
        // Cache kontrolü için header'ları ayarla
        header('Cache-Control: public, max-age=' . $this->cache_time);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $this->cache_time) . ' GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');
    }
}
?> 