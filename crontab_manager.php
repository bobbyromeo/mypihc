<?php
require 'vendor/autoload.php';
require 'lib/extends.php';
use TiBeN\CrontabManager\CrontabJob;
use TiBeN\CrontabManager\CrontabAdapter;
//use TiBeN\CrontabManager\CrontabRepository;

session_start();

if (!isset($_SESSION['ini_array'])) {
    $ini_array = parse_ini_file("config.ini", true);
    $_SESSION['ini_array'] = $ini_array;
    //error_log('Location: first-time');
} else {
    $ini_array = $_SESSION['ini_array'];
    //error_log('Location: sub-time');
}

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
