<?php
require 'vendor/autoload.php';
require 'lib/extends.php';
use TiBeN\CrontabManager\CrontabJob;
use TiBeN\CrontabManager\CrontabAdapter;
//use TiBeN\CrontabManager\CrontabRepository;

$ini_array = parse_ini_file("config.ini", true);

// Post
if ($_POST) {
    // error_log( print_r( $_POST, true ) );
    // Add crontab
    if (isset($_POST['action']) && $_POST['action'] == "addcron") {
        $output = "";
        $result = false;
        try {
            if(isset($_POST['switch']) && array_key_exists($_POST['switch'], $ini_array) &&
                isset($_POST['state']) && array_key_exists($_POST['state'], $ini_array['switch-a'])) {
                $crontabRepository = new CrontabRepositoryExtends(new CrontabAdapter($ini_array['config']['cron_user'], true));

                $crontabJob = new CrontabJob();
                $crontabJob->minutes = ($_POST['minute_chooser'] == "0") ? "*" : $_POST['minute'];
                $crontabJob->hours = ($_POST['hour_chooser'] == "0") ? "*" : $_POST['hour'];
                $crontabJob->dayOfMonth = ($_POST['day_chooser'] == "0") ? "*" : $_POST['day'];
                $crontabJob->months = ($_POST['month_chooser'] == "0") ? "*" : $_POST['month'];
                $crontabJob->dayOfWeek = ($_POST['weekday_chooser'] == "0") ? "*" : $_POST['weekday'];

                $cmd = __DIR__ . '/' . join('/', array(trim($ini_array[$_POST['switch']][$_POST['state']], '/')));

                $crontabJob->taskCommandLine = ($ini_array[$_POST['switch']]['use_sudo']) ? 'sudo ' . $cmd : $cmd;
                $crontabJob->comments = 'PowerControl:'.uniqid().':'.$ini_array[$_POST['switch']]['name'].':'.$_POST['state'];
                $crontabJob->enabled = true;

                $crontabRepository->addJob($crontabJob);
                $crontabRepository->persist();
                $result = true;
            } else {
                error_log("Invalid post parameters");
            }
        } catch (Exception $e) {
            $output = 'Caught exception: ' . $e->getMessage();
            error_log($output);
        }

        $data = array( 'output' => $output, 'result' => $result);
        header('Content-Type: application/json');
        echo json_encode($data);
    } else {
        exit();
    }
}

// Delete/Disable/List Crontabs
if ($_GET) {
    // error_log( print_r( $_GET, true ) );
    if (isset($_GET['action']) && $_GET['action'] == "listcron") {
        $result = false;
        $output = "";
        try {
            $crontabRepository = new CrontabRepositoryExtends(new CrontabAdapter($ini_array['config']['cron_user'], true));
            $arrayJobs = $crontabRepository->findJobByRegexComment('/PowerControl/');
            $result = true;
        } catch (Exception $e) {
            $output = 'Caught exception: ' . $e->getMessage();
            error_log($output);
        }
        $data = array( 'output' => $arrayJobs, 'result' => $result);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

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
            $script = substr($cmd, 0, strrpos($cmd, ' '));
            if (file_exists($script)) {
                if (isset($ini_array[$getSwitch]['use_as_camera']) && !empty($ini_array[$getSwitch]['use_as_camera'])) {
                    $camera = $ini_array[$getSwitch]['use_as_camera'];
                    $cmd = $cmd . ' ' . $camera;
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

        $data = array( 'output' => $output, 'result' => $result);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    if (isset($_GET['action']) && ($_GET['action'] == "delete" || $_GET['action'] == "disable")) {
        if(isset($_GET['uuid']) && !empty($_GET['uuid'])) {
            $result = false;
            $output = "";
            $uuid = $_GET['uuid'];
            try {
                $crontabRepository = new CrontabRepositoryExtends(new CrontabAdapter($ini_array['config']['cron_user'], true));
                $results = $crontabRepository->findJobByRegexComment('/'.preg_quote($uuid).'/');
                if(count($results) == 1) {
                    if ($_GET['action'] == "delete") {
                        $crontabJob = $results[0];
                        $crontabRepository->removeJob($crontabJob);
                        $crontabRepository->persist();
                    }
                    if ($_GET['action'] == "disable") {
                        $crontabJob = $results[0];
                        $crontabJob->enabled = ($crontabJob->enabled == "1" ? false : true);
                        $crontabRepository->persist();
                    }
                }
                $result = true;
            } catch (Exception $e) {
                $output = 'Caught exception: ' . $e->getMessage();
                error_log($output);
            }
            $data = array( 'output' => $output, 'result' => $result);
            header('Content-Type: application/json');
            echo json_encode($data);
        }
    }
}

?>
