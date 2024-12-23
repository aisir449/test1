<?
namespace NC\UnloadingB24;

use Bitrix\Main\Config\Option;

class Logger
{
    private static $instance;
    private static $fileName;
    private static $dir;
    private static $file;

    public static function getLogger()
    {
        if (self::$instance === null)
            self::$instance = new self();

        return self::$instance;
    }

    public function __construct()
    {
        $logDir = Option::get("mediaportal.main", "store_log_path");

        $file_name = self::$fileName === null ? date('d-m-Y') . '.log' : self::$fileName;

        if (self::$dir === null) {
            //self::$dir = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "exchange_log" . DIRECTORY_SEPARATOR;
            self::$dir = $_SERVER['DOCUMENT_ROOT']  . DIRECTORY_SEPARATOR . "local" . DIRECTORY_SEPARATOR . $logDir . DIRECTORY_SEPARATOR;

        }

        if (strpos(self::$dir, DIRECTORY_SEPARATOR, -1) != true)
            self::$dir .= DIRECTORY_SEPARATOR;

        if (!is_dir(self::$dir))
            mkdir(self::$dir);

        self::$file = fopen(self::$dir . $file_name, 'a');
    }

    public function __destruct()
    {
        fclose(self::$file);
    }

    private function _log($message, $type = "[INFO]")
    {
        //$logMode = Option::get("mediaportal.main", "store_log_mode");
        //if (!$logMode && $logMode != 'N') return;

        $line = date('[H:i:s]');

        $line .= $type . ' ';

        $line .= '- ' . $message;

        fwrite(self::$file, $line . PHP_EOL);
    }

    private static function isLogPermitted()
    {
        $logMode = Option::get("mediaportal.main", "store_log_mode");
        if (!$logMode && $logMode != 'N') return false;
        return true;
    }


    public static function error($message)
    {
        if (self::isLogPermitted()) {
            self::getLogger()->_log($message, "[ERROR]");
        }
    }

    public static function warning($message)
    {
        if (self::isLogPermitted()) {
            self::getLogger()->_log($message, "[WARNING]");
        }
    }

    public static function debug($message, $var = null)
    {
        if (self::isLogPermitted()) {
            if ($var != null)
                $message .= ': ' . print_r($var, true);

            self::getLogger()->_log($message, "[DEBUG]");
        }
    }

    public static function log($message)
    {
        if (self::isLogPermitted()) {
            self::getLogger()->_log($message, "[INFO]");
        }
    }

    public static function setDir($path)
    {
        self::$dir = $path;
    }

    public static function setFileName($name)
    {
        self::$fileName = $name;
    }
}