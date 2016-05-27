<?php
session_start();
$output = "";
$result = "";

if (!isset($_COOKIE['visited'])) {
    setcookie('visited', 'yes', time() + 3600); // set visited cookie
    $ini_array = parse_ini_file("config.ini", true);
    $_SESSION['ini_array'] = $ini_array;
    //error_log('Location: first-time');
} else {
    $ini_array = $_SESSION['ini_array'];
    //error_log( print_r( $ini_array, true ) );
    //error_log('Location: sub-time');
}


if ($_GET) {
    // error_log( print_r( $ini_array, true ) );
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
                    session_write_close();
                    $output = shell_exec($cmd);
                } catch (Exception $e) {
                    $output = 'Caught exception: ' . $e->getMessage();
                    error_log($output);
                }

                if (preg_match("/sending code/i", $output)) {
                    $result = true;
                }
                // error_log($output);
                if (preg_match("/switching on|switching off/i", $output)) {
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

    $data = array( 'output' => $output, 'result' => $result);
    header('Content-Type: application/json');
    echo json_encode($data);
}
?>
