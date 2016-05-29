<?php
session_start();
$output = "";
$result = "";

if (!isset($_SESSION['ini_array'])) {
    $ini_array = parse_ini_file("config.ini", true);
    $_SESSION['ini_array'] = $ini_array;
    //error_log('Location: first-time');
} else {
    $ini_array = $_SESSION['ini_array'];
    //error_log('Location: sub-time');
}

error_log( print_r( $_SESSION['ini_array'], true ) );
if ($_GET) {
    // error_log( print_r( $_GET, true ) );
    if (isset($_GET['action']) && $_GET['action'] == "dht22") {
        if (isset($ini_array['config']['use_dht22_module']) && $ini_array['config']['use_dht22_module'] == true &&
            isset($ini_array['dht22']['script'])) {

            $cmd = __DIR__ . '/' . join('/', array(trim($ini_array['dht22']['script'], '/')));
            if ($ini_array['dht22']['use_sudo']) {
                session_write_close();
                $output = shell_exec('sudo ' . $cmd);
            } else {
                session_write_close();
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
                if ($ini_array[$camera]['type'] == "foscam") {
                    $snapshot_uri = $ini_array['foscam']['snapshot_uri'];

                    $img_url = str_replace(array('{ip}', '{username}', '{password}'), array($ini_array[$camera]['ip'], $ini_array[$camera]['username'], $ini_array[$camera]['password']), $snapshot_uri);
                    // error_log($snapshot_uri);
                }
                // $img_url = 'http://'.$ini_array[$camera]['ip'].'/snapshot.cgi?user='.$ini_array[$camera]['username'].'&pwd='.$ini_array[$camera]['password'];
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

    if (isset($_GET['action']) && $_GET['action'] == "move") {
        if (isset($_GET['switch']) && !empty($_GET['switch'])) {
            $switch = $_GET['switch'];
            if (isset($_GET['direction']) && !empty($_GET['direction'])) {
                $direction = $_GET['direction'];
                switch ($direction) {
                    case "right":
                        // $command = "4";
                        $direction_uri = "move_right_uri";
                        break;
                    case "left":
                        //$command = "6";
                        $direction_uri = "move_left_uri";
                        break;
                    case "down":
                        //$command = "2";
                        $direction_uri = "move_down_uri";
                        break;
                    case "up":
                        //$command = "0";
                        $direction_uri = "move_up_uri";
                        break;
                    default:
                        // $command = "1";
                        $direction_uri = "";
                }

                try {
                    $camera = $ini_array['switch-'.$switch]['use_as_camera'];
                    if ($ini_array[$camera]['type'] == "foscam") {
                         $move_uri = $ini_array['foscam'][$direction_uri];
                    }
                    //$url = 'http://'.$ini_array[$camera]['ip'].'/decoder_control.cgi?command='.$command.'&onestep=1&user='.$ini_array[$camera]['username'].'&pwd='.$ini_array[$camera]['password'];
                    $move_uri = str_replace(array('{ip}', '{username}', '{password}'), array($ini_array[$camera]['ip'], $ini_array[$camera]['username'], $ini_array[$camera]['password']), $move_uri);
                    $response = file_get_contents($move_uri);
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

