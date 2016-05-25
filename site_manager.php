<?php
$ini_array = parse_ini_file("config.ini", true);
$output = "";
$result = "";

if ($_GET) {
    // error_log( print_r( $_GET, true ) );
    if (isset($_GET['action']) && $_GET['action'] == "control" &&
        isset($_GET['switch']) && !empty($_GET['switch'])) {
        $output = "";
        $result = false;

        $getSwitch = $_GET['switch'];
        $getSwitch = 'switch-' . $getSwitch;
        $getTargetState = $_GET['state'];

        if (array_key_exists($getSwitch, $ini_array)) {
            if($getTargetState === '1') {
                $cmd = __DIR__ . '/' . join('/', array(trim($ini_array[$getSwitch]['turn_on'], '/')));
            } elseif ($getTargetState === '0') {
                $cmd = __DIR__ . '/' . join('/', array(trim($ini_array[$getSwitch]['turn_off'], '/')));
            } else {
                die("ERROR");
                error_log($output);
            }
            list($script) = explode(' ', $cmd);
            if (file_exists($script)) {
                if (isset($ini_array[$getSwitch]['use_as_camera']) && !empty($ini_array[$getSwitch]['use_as_camera'])) {
                    $camera = $ini_array[$getSwitch]['use_as_camera'];
                    if (strpos($cmd, $camera) === false) {
                        $cmd = $cmd . ' ' . $camera;
                    }
                }
                if ($ini_array[$getSwitch]['use_sudo'])
                    $cmd = 'sudo ' . $cmd;
                try {
                    $output = shell_exec($cmd);
                } catch (Exception $e) {
                    $output = 'Caught exception: ' . $e->getMessage();
                    error_log($output);
                }

                if (preg_match("/sending code/i", $output)) {
                    $result = true;
                }
                if (array_key_exists('pid_file', $ini_array[$getSwitch])) {
                    $pid_file = __DIR__ . $ini_array[$getSwitch]['pid_file'];
                    if ($getTargetState === '1' && file_exists($pid_file) && file_get_contents($pid_file)) {
                        $result = true;
                    }
                    if ($getTargetState === '0' && !file_exists($pid_file)) {
                        $result = true;
                    }
                }
            } else {
                $output = $script  . ' does not exist!';
                error_log($output);
            }
        }
    }

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
                preg_match_all('/\[[^\[\]]*\]/', $output, $matches);
                $arr = preg_replace("/[^0-9,.]/", "", explode(',', trim($matches[0][0])));
                $output = array('temperature' => $arr[0], 'humidity' => $arr[1], 'unit' => ($ini_array['dht22']['temp_in_fahrenheit']) ? 'fahrenheit' : 'celsius');
            }
        } else {
            $output = "DHT module is disabled";
        }
        $result = true;
    }

    if (isset($_GET['action']) && $_GET['action'] == "uptime") {
        $output = parseUptime();
        $result = true;
    }

    if (isset($_GET['action']) && $_GET['action'] == "updatetime") {
        $output = date('d/m/Y H:i:s');
        $result = true;
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
                    $result = true;
                }
            } catch (Exception $e) {
                $output = 'Caught exception: ' . $e->getMessage();
                error_log($output);
                $result = false;
            }
        }
    }

    if (isset($_GET['action']) && $_GET['action'] == "checkSwitchStatus") {
         if (isset($_GET['switch']) && !empty($_GET['switch'])) {
            $switch = $_GET['switch'];
            $pid_file = __DIR__ . $ini_array['switch-'.$switch]['pid_file'];
            if (file_exists($pid_file) && file_get_contents($pid_file)) {
                $result = true;
                $output = $pid_file;
            } else {
                $result = false;
                $output = "No pidfile found for switch: " . $switch . ", looking for: " . $pid_file;
            }
         }
    }

    if (isset($_GET['action']) && $_GET['action'] == "move") {
        if (isset($_GET['switch']) && !empty($_GET['switch'])) {
            $switch = $_GET['switch'];
            if (isset($_GET['direction']) && !empty($_GET['direction'])) {
                $direction = $_GET['direction'];
                switch ($direction) {
                    case "right":
                        $command = "4";
                        break;
                    case "left":
                        $command = "6";
                        break;
                    case "down":
                        $command = "2";
                        break;
                    case "up":
                        $command = "0";
                        break;
                    default:
                        $command = "1";
                }

                try {
                    $camera = $ini_array['switch-'.$switch]['use_as_camera'];
                    $url = 'http://'.$ini_array[$camera]['ip'].'/decoder_control.cgi?command='.$command.'&onestep=1&user='.$ini_array[$camera]['username'].'&pwd='.$ini_array[$camera]['password'];
                    $response = file_get_contents($url);
                    $result = true;
                    $output = $response;
                } catch (Exception $e) {
                    $output = 'Caught exception: ' . $e->getMessage();
                    error_log($output);
                    $result = false;
                }
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

