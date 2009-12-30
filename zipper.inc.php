<?php

class ovp_zipper {
    public static function pack_dir() {
        $dir = getcwd();
        $files = self::list_files($dir);
        
        $zip = new ZipArchive;
        $res = $zip->open('source.zip', ZipArchive::CREATE);
        if (!$res) {
            return false;
        }
        foreach ($files as $file) {
            $zip->addFile($file);
        }
        $zip->close();
    }
    
    private static function list_files($dir) {
        $handle = opendir($dir);

        $result = array();
        while (false !== ($file = readdir($handle))) {
	    if ($file != '.' && $file != '..') {
                $result[] = $file;
            }
        }
        closedir($handle);

        return $result;
    }
}


?>
