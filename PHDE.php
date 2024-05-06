<?php
/**
 * PHDE is a PHP Development Environment utility class.
 * 
 * This class provides methods for managing PHP error reporting, setting HTTP response headers, and handling file downloads.
 * 
 * @author Sakibur Rahman (@sakibweb)
 */
class PHDE {
    
    /**
     * Constructor method to initialize error reporting settings.
     *
     * @param bool $state If true, enable error reporting and display errors; if false, disable error reporting and hide errors.
     */
    public function __construct($state = false) {
        if($state === true) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        } elseif($state === false) {
            error_reporting(0);
            ini_set('display_errors', 0);
        } else {
            error_reporting(0);
            ini_set('display_errors', 0);
        }
    }
    
    /**
     * Enable error reporting and display errors.
     */
    public static function yes() {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    }

    /**
     * Disable error reporting and hide errors.
     */
    public static function no() {
        error_reporting(0);
        ini_set('display_errors', 0);
    }
    
    /**
     * Enable error reporting and display errors.
     */
    public function on() {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    }

    /**
     * Disable error reporting and hide errors.
     */
    public function off() {
        error_reporting(0);
        ini_set('display_errors', 0);
    }

    /**
     * Set HTTP response headers for API responses.
     *
     * @param string $method MIME type of the response content.
     */
    public static function api($method = 'application/json') {
        header('Content-Type: '.$method);
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");
    }

    /**
     * Set HTTP response headers for file downloads.
     *
     * @param string $name Filename of the file being downloaded.
     * @param int $length Length of the file being downloaded.
     */
    public static function file($name, $length) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.$name.'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: '.$length);
    }
}
?>
