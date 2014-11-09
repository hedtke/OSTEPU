<?php

    class MimeReader {
    
        // http://stackoverflow.com/a/134930/1593459
        public static function get_mime($file) {
        
            if (!file_exists($file)) return null;
            
            if (function_exists("finfo_file")) {
                $finfo = @finfo_open(FILEINFO_MIME_TYPE);
                if (!$finfo) return null;
                
                $mime = @finfo_file($finfo, $file);
                if (!$mime) return null;
                
                finfo_close($finfo);
                return $mime;
            } else if (function_exists("mime_content_type")) {
                return mime_content_type($file);
            } else if (!stristr(ini_get("disable_functions"), "shell_exec")) {
                $file = escapeshellarg($file);
                $mime = shell_exec("file -bi " . $file);
                return $mime;
            } else {
                return null;
            }
        }
    }
    
?>