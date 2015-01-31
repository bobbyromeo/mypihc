<?php
$ini_array = parse_ini_file("config.ini", true);   
$output = "";
$result = true;

if ($_GET) {
    // error_log( print_r( $_GET, true ) );
    if (isset($_GET['action']) && $_GET['action'] == "dht22") {
        if (isset($ini_array['config']['use_dht22_module']) && $ini_array['config']['use_dht22_module'] == true &&
            isset($ini_array['dht22']['script'])) {

            $cmd = __DIR__ . '/' . join('/', array(trim($ini_array['dht22']['script'], '/')));
            if ($ini_array['dht22']['use_sudo']) {
                $output = shell_exec('sudo ' . $cmd);
            } else {
                $output = shell_exec($cmd);
            }
             
            if (!empty($output)) {
                $arr = explode(',', trim($output));
                $output = array('temperature' => $arr[0], 'humidity' => $arr[1]);
            }
        } else {
            $output = "DHT module is disabled";
        }
    }

    if (isset($_GET['action']) && $_GET['action'] == "uptime") {
        $output = parseUptime();
    }
    
    if (isset($_GET['action']) && $_GET['action'] == "updatetime") {
        $output = date('d/m/Y H:i:s');
    }

    if (isset($_GET['action']) && $_GET['action'] == "updateImage") {
        if (isset($_GET['switch']) && !empty($_GET['switch'])) {
            $switch = $_GET['switch'];
            try {
                $camera = $ini_array['switch-'.$switch]['use_as_camera'];
                $img_url = 'http://'.$ini_array[$camera]['ip'].'/snapshot.cgi?user='.$ini_array[$camera]['username'].'&pwd='.$ini_array[$camera]['password'];
                $im = file_get_contents($img_url);
                if ($im === false) {
                    $result = false;
                } else {
                    $output = base64_encode($im);
                }
            } catch (Exception $e) {
                $output = 'Caught exception: ' . $e->getMessage();
                error_log($output);
                $result = false;
            }
        }
    }

    $data = array( 'output' => $output, 'result' => $result);
    header('Content-Type: application/json');
    echo json_encode($data);
}

function parseUptime() {
    $x = shell_exec('cat /proc/uptime');
    list($y, $idle) = explode(' ', $x);
    $min = floor($y / 60);
    $sec = $y % 60;
    
    $hr = floor($min / 60);
    $min = $min % 60;
    
    $day = floor($hr / 24);
    $hr1 = $hr % 24;

    $s1 = ($hr == 1) ? '' : 's';
    $s2 = ($min == 1) ? '' : 's';
    $s3 = ($day == 1) ? '' : 's';
    //$x = "$hr Hour$s1, $min Minute$s2";
    $y = "$day Day$s3, $hr1 Hour$s1, $min Minute$s2";
    return $y;
}
?>

