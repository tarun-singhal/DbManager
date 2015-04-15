<?php
/**
 * To parse the INI file and execute the query to the respective database. 
 * @author tarunsinghal
 *
 */
class script
{

    protected $dir;

    protected $ini_file = array();

    protected $dbDetails = array();

    protected $e_count, $r_count = 0;

    
    public function parse_ini_advanced($array)
    {
        $returnArray = array();
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $e = explode(':', $key);
                if (! empty($e[1])) {
                    $x = array();
                    foreach ($e as $tk => $tv) {
                        $x[$tk] = trim($tv);
                    }
                    $x = array_reverse($x, true);
                    foreach ($x as $k => $v) {
                        $c = $x[0];
                        if (empty($returnArray[$c])) {
                            $returnArray[$c] = array();
                        }
                        if (isset($returnArray[$x[1]])) {
                            $returnArray[$c] = array_merge($returnArray[$c], $returnArray[$x[1]]);
                        }
                        if ($k === 0) {
                            $returnArray[$c] = array_merge($returnArray[$c], $array[$key]);
                        }
                    }
                } else {
                    $returnArray[$key] = $array[$key];
                }
            }
        }
        return $returnArray;
    }

    public function recursive_parse($array)
    {
        $returnArray = array();
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    $array[$key] = $this->recursive_parse($value);
                }
                $x = explode('.', $key);
                if (! empty($x[1])) {
                    $x = array_reverse($x, true);
                    if (isset($returnArray[$key])) {
                        unset($returnArray[$key]);
                    }
                    if (! isset($returnArray[$x[0]])) {
                        $returnArray[$x[0]] = array();
                    }
                    $first = true;
                    foreach ($x as $k => $v) {
                        if ($first === true) {
                            $b = $array[$key];
                            $first = false;
                        }
                        $b = array(
                            $v => $b
                        );
                    }
                    $returnArray[$x[0]] = array_merge_recursive($returnArray[$x[0]], $b[$x[0]]);
                } else {
                    $returnArray[$key] = $array[$key];
                }
            }
        }
        return $returnArray;
    }

    /**
     * Get the file extension
     * @param string $str
     * @return string
     */
    public function getExtension($str)
    {
        $i = strrpos($str, ".");
        if (! $i) {
            return "";
        }
        $l = strlen($str) - $i;
        $ext = substr($str, $i + 1, $l);
        return $ext;
    }

    /**
     * get the all file associated to the query execution
     */
    public function getFileName()
    {
        $this->dir = __DIR__ . "/ini_dir/";
        if (is_dir($this->dir)) {
            if ($dh = opendir($this->dir)) {
                while (($file = readdir($dh)) !== false) {
                    if ($file != '.' && $file != '..') {
                        $ext = $this->getExtension($file);
                        if (strtolower($ext) == 'ini') {
                            $this->ini_file[] = $file;
                            echo "filename: " . $file . "\n";
                        }
                    }
                }
                closedir($dh);
            }
        }
    }

    /**
     * Get all db adapter
     */
    public function dbAdapter()
    {
        $applicationVars = parse_ini_file(dirname(dirname(__FILE__)) . '/db_update/db.ini', TRUE);
        foreach ($applicationVars as $key => $val) {
            $this->dbDetails[$key] = array(
                'host' => $applicationVars[$key]['host'],
                'database' => $applicationVars[$key]['dbname'],
                'username' => $applicationVars[$key]['username'],
                'password' => $applicationVars[$key]['password']
            );
        }
    }

    /**
     * process the selected INI file into selected database
     */
    public function processINIFile()
    {
        if (! function_exists('mysqli_init') && ! extension_loaded('mysqli')) {
            echo 'We don\'t have mysqli!!!';
        } else {
            echo "We have mysqli installed! \n";
        }
        
        if (count($this->ini_file) == 0)
            echo "Please provide INI File \n";
        
        foreach ($this->ini_file as $key => $file_name) {
            
            $array = parse_ini_file($this->dir . $file_name, true);
            $sql_ini_array = $this->recursive_parse($this->parse_ini_advanced($array));
            
            if (count($sql_ini_array) == 0) {
                echo "Either given INI file '$file_name' is empty OR corrupt, Please check and try again.... \n";
                continue;
            }
            
            if (isset($sql_ini_array['db1']) && count($sql_ini_array['db1']) == 0 && isset($sql_ini_array['db2']) && count($sql_ini_array['db2']) == 0) {
                echo "Given INI file '$file_name' is empty, Please check and try again.... \n";
                continue;
            }
            
            if (isset($sql_ini_array['db1'])) {
                $db1_sql = $sql_ini_array['db1'];
                $this->processSql($this->dbDetails['db1'], $db1_sql, 'DB 1');
                if (isset($db1_sql['e']))
                    $this->e_count = count($db1_sql['e']);
                if (isset($db1_sql['r']))
                    $this->r_count = count($db1_sql['r']);
                
                echo "DB 1 : executable($this->e_count) AND Rollback($this->r_count) Query run successfully ......\n";
            }
            
            if (isset($sql_ini_array['db2'])) {
                
                $db2_sql = $sql_ini_array['db2'];
                $this->processSql($this->dbDetails['db2'], $db2_sql, 'DB 2');
                if (isset($db2_sql['e']))
                    $this->e_count = count($db2_sql['e']);
                else
                    $this->e_count = 0;
                
                if (isset($db2_sql['r']))
                    $this->r_count = count($db2_sql['r']);
                else
                    $this->r_count = 0;
                
                echo "DB 2 : Executable($this->e_count) AND Rollback($this->r_count) Query run successfully ......\n";
            }
            
            // rename executed ini file
            @rename($this->dir . $file_name, $this->dir . $file_name . '_' . time() . '.txt');
        }
    }

    /**
     * Process query into database
     * @param array $adapter
     * @param array $sql_ini
     * @param string $db
     */
    public function processSql($adapter, $sql_ini = array(), $db = '')
    {
        $mysqli = new mysqli($adapter['host'], $adapter['username'], $adapter['password'], $adapter['database']);
        $result = $mysqli->query("SET FOREIGN_KEY_CHECKS=0");
        
        if (isset($sql_ini['e']) && count($sql_ini['e']) > 0) {
            foreach ($sql_ini['e'] as $index => $query) {
                $result = $mysqli->query($query);
                $log = array(
                    'error' => $mysqli->error,
                    'errno' => $mysqli->errno,
                    'database' => $db,
                    'query' => $query
                );
                $this->logExecutedSql($log); // Log the sql
            }
        }
        $result = $mysqli->query("SET FOREIGN_KEY_CHECKS=1");
    }

    /**
     * Log mechanism of the sql
     * @param array $log
     */
    public function logExecutedSql($log)
    {
        $log['timestamp'] = date('Y-m-d h:i:s');
        $sqlLog = implode(' ### ', $log);
        $file = "db_log.txt";
        $fp = fopen($file, 'a+');
        fwrite($fp, "$sqlLog\n");
        fwrite($fp, "==================================== \n");
        fclose($fp);
    }
}

$obj = new script();
$obj->getFileName();
$obj->dbAdapter();
$obj->processINIFile();
?>
