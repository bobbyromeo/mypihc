<!DOCTYPE html>
<!-- <html lang="en" manifest="offline.manifest.php"> -->
<html lang="en">
<head>
    <title>MyPi Home Control</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <!-- Le styles -->
    <link href="css/bootstrap.css" rel="stylesheet">
    <style>
    body {
        padding-top: 60px; /* 60px to make the container go all the way to the bottom of the topbar */
    }

    #footer {
        position: relative;
        height: 100px;
        clear:both;
        padding-top:20px;
        background-color: #555555;
        color: #fff;
    }

    div.desc{
        background-color: #000;
        bottom: 0;
        color: #fff;
        left: 0;
        opacity: 0.5;
        /*position: absolute;*/
        width: 100%;
        text-align: center;
    }

    .fix{
        width: 100%;
        padding: 0px;
    }

    canvas {
        background-color: black;
        width: 100%;
        height: auto;
    }

    .the-table {
        table-layout: fixed;
        word-wrap: break-word;
    }

    .voffset  { margin-top: 2px; }
    .voffset1 { margin-top: 5px; }
    .voffset2 { margin-top: 10px; }
    .voffset3 { margin-top: 15px; }
    .voffset4 { margin-top: 30px; }
    .voffset5 { margin-top: 40px; }
    .voffset6 { margin-top: 60px; }
    .voffset7 { margin-top: 80px; }
    .voffset8 { margin-top: 100px; }
    .voffset9 { margin-top: 150px; }

    .btn:focus {
        outline: none;
    }
    .btn-group {
        display: flex;
    }
    </style>
</head>

<?php
    $ini_array = parse_ini_file("config.ini", true);
    $pir_array = $ini_array['pir'];
    if(file_exists($ini_array['config']['save_to_dir']))
        $path_good = true;
    else
        $path_good = false;

?>
<body>
    <nav class="navbar navbar-inverse navbar-fixed-top">
        <div class="container-fluid">
            <div class="navbar-header">
<!--            <ul class="nav navbar-nav">
                    <li><a class="navbar-brand" href="#">MyPi Home Control</a></li>
                </ul> -->
                <a class="navbar-brand" rel="home" href="#" title="Buy Sell Rent Everyting">
                    <img style="max-width:40px; margin-top: -7px;"
                         src="img/mypihc-logo.png">
                    <a class="navbar-brand" href="#">MyPi Home Control</a>
                </a>
            </div>
<!--             <div class="navbar-header navbar-right">
                <ul class="nav navbar-nav">
                    <li><a id="" href="#"></a></li>
                </ul>
            </div> -->
        </div>
    </nav>

    <div class="container-fluid">
        <fieldset>
        <legend>Stats</legend>

        <div class="row-fluid">
            <div class="col-xs-12 col-md-12">
                <div class="row-fluid">
                    <div class="col-xs-6 col-md-3">
                        <strong>Current time:</strong>
                    </div>
                    <div class="col-xs-6 col-md-3">
                        <i><span id="timer"></span></i>
                    </div>
                    <div class="col-xs-6 col-md-3">
                        <strong>Up time:</strong>
                    </div>
                    <div class="col-xs-6 col-md-3">
                        <i><span id="uptime"></span></i>
                    </div>
                </div>

                <div class="row-fluid">
                    <div class="col-xs-6 col-md-3">
                        <strong>Save to path:</strong>
                    </div>
                    <div class="col-xs-6 col-md-3">
                        <?php echo ($path_good) ? '<i><span>'.$ini_array['config']['save_to_dir'].'</span></i>':'<i><span class="text-danger">Invalid path!</span></i>'; ?>
                    </div>
                    <?php if (isset($ini_array['config']['use_dht22_module']) && $ini_array['config']['use_dht22_module'] == true) { ?>
                    <div class="col-xs-6 col-md-3">
                        <strong>Temp./Hum.:</strong>
                    </div>
                    <div class="col-xs-6 col-md-3">
                        <div id="ajaxSpinnerContainer">
                            <img src="img/ajax-loader.gif" id="ajaxSpinnerImage" title="working...">
                        </div>
                        <i><span id="dht22"></span></i>
                    </div>
                    <?php } ?>
                </div>

                <input id="use_dht22_module" type="hidden" value="<?php echo (isset($ini_array['config']['use_dht22_module']) && $ini_array['config']['use_dht22_module'] == true)?true:false;?>" name="use_dht22_module">
                <input id="use_camera_module" type="hidden" value="<?php echo (isset($ini_array['config']['use_camera_module']) && $ini_array['config']['use_camera_module'] == true)?true:false;?>" name="use_camera_module">
                <input id="use_pir_module" type="hidden" value="<?php echo (isset($ini_array['config']['use_pir_module']) && $ini_array['config']['use_pir_module'] == true)?true:false;?>" name="use_pir_module">
            </div>
        </div>
        </fieldset>
    </div>
    <br>
    <div class="container-fluid">
        <fieldset>
        <legend>Real-time Controls</legend>
            <div class="row-fluid">
                <div class="col-xs-12 col-md-4">
                    <?php if ( isset($ini_array['config']['use_433mhz_module']) && $ini_array['config']['use_433mhz_module'] == false &&
                         isset($ini_array['config']['use_pir_module']) && $ini_array['config']['use_pir_module'] == false &&
                         isset($ini_array['config']['use_camera_module']) && $ini_array['config']['use_camera_module'] == false) { ?>
                        <div class="row-fluid">
                            <em>No controls enabled in config.ini.</em>
                        </div>
                    <?php } ?>
                    <?php if (isset($ini_array['config']['use_433mhz_module']) && $ini_array['config']['use_433mhz_module'] == true) { ?>
                    <div class="row-fluid">
                        <div class="col-xs-6 col-md-6 voffset1">
                            <label class="switch-a"><?php echo $ini_array['switch-a']['name']; ?></label>
                        </div>
                        <div class="col-xs-6 col-md-6 voffset1">
                            <div class="btn-group">
                                <button class="switchButton switch-a on btn btn-large btn-success" type="button">ON</button>
                                <button class="switchButton switch-a off btn btn-large btn-danger" type="button">OFF</button>
                            </div>
                        </div>
                    </div>
                    <div class="row-fluid">
                        <div class="col-xs-6 col-md-6 voffset1">
                            <label class="switch-b"><?php echo $ini_array['switch-b']['name']; ?></label>
                        </div>
                        <div class="col-xs-6 col-md-6 voffset1">
                            <div class="btn-group">
                                <button class="switchButton switch-b on btn btn-large btn-success" type="button">ON</button>
                                <button class="switchButton switch-b off btn btn-large btn-danger" type="button">OFF</button>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                    <!-- PIR Switch -->
                    <?php if (isset($ini_array['config']['use_pir_module']) && $ini_array['config']['use_pir_module'] == true) { ?>
                    <div class="row-fluid">
                        <div class="col-xs-6 col-md-6 voffset1">
                            <label class="switch-c">
                                <?php echo $ini_array['switch-c']['name']; ?>
                            </label>
                            <img class="hidden" src="img/ajax-loader.gif" id="switch-c-spinner" title="working...">
                        </div>
                        <div class="col-xs-6 col-md-6 voffset1">
                            <div class="btn-group">
                                <button class="switchButton switch-c on btn btn-large btn-success" type="button" <?php echo (!$path_good)?'disabled="disabled"':''?>>ON</button>
                                <button class="switchButton switch-c off btn btn-large btn-danger" type="button" <?php echo (!$path_good)?'disabled="disabled"':''?>>OFF</button>
                            </div>
                        </div>
                    </div>
                    <div class="row-fluid">
                        <div class="col-xs-12 col-md-12 voffset1">
                            <span class="small lead">
                                <?php
                                    $string = "";
                                    foreach ($pir_array as $key => $value) {
                                        if ($value)
                                            $string .= ", $key";
                                    }
                                    echo '('.str_replace('_', ' ', substr($string, 2)).')';
                                ?>
                            </span>
                        </div>
                    </div>
                    <?php } ?>
                    <!-- Cameras Switches -->
                    <?php if (isset($ini_array['config']['use_camera_module']) && $ini_array['config']['use_camera_module'] == true) { ?>
                    <div class="row-fluid">
                        <div class="col-xs-6 col-md-6 voffset1">
                            <label class="switch-d">
                                <?php echo $ini_array['switch-d']['name']; ?>
                            </label>
                            <img class="hidden" src="img/ajax-loader.gif" id="switch-d-spinner" title="working...">
                        </div>
                        <div class="col-xs-6 col-md-6 voffset1">
                            <div class="btn-group">
                                <button class="switchButton switch-d on btn btn-large btn-success" type="button" <?php echo (!$path_good)?'disabled="disabled"':''?>>ON</button>
                                <button class="switchButton switch-d off btn btn-large btn-danger" type="button" <?php echo (!$path_good)?'disabled="disabled"':''?>>OFF</button>
                            </div>
                        </div>
                    </div>
                    <div class="row-fluid">
                        <div class="col-xs-6 col-md-6 voffset1">
                            <label class="switch-e">
                                <?php echo $ini_array['switch-e']['name']; ?>
                            </label>
                            <img class="hidden" src="img/ajax-loader.gif" id="switch-e-spinner" title="working...">
                        </div>
                        <div class="col-xs-6 col-md-6 voffset1">
                            <div class="btn-group">
                                <button class="switchButton switch-e on btn btn-large btn-success" type="button" <?php echo (!$path_good)?'disabled="disabled"':''?>>ON</button>
                                <button class="switchButton switch-e off btn btn-large btn-danger" type="button" <?php echo (!$path_good)?'disabled="disabled"':''?>>OFF</button>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div> <!-- END Switches -->

                <div class="col-xs-12 col-md-8">
                    <div class="col-xs-12 col-md-6 voffset1">
                        <?php if (isset($ini_array['config']['use_camera_module']) && $ini_array['config']['use_camera_module'] == true) { ?>
                        <div class="fix">
        <!--                <img class="img-responsive" id="switch-d" alt="" src="img/getting_feed.jpg" name="<?php echo $ini_array['camera1']['name'] ?>" >
         -->                <canvas class="img-responsive" id="switch-d" width="640" height="480"></canvas>
                            <div class="desc">
                                <p class="desc_content"><?php echo $ini_array['camera1']['name'].' ('.$ini_array['camera1']['ip'].')'?></p>
                            </div>
                        </div>
                        <?php } ?>
                    </div>

                    <div class="col-md-6 voffset1">
                        <?php if (isset($ini_array['config']['use_camera_module']) && $ini_array['config']['use_camera_module'] == true) { ?>
                        <div class="fix">
    <!--                    <img class="img-responsive" id="switch-e" alt="" src="img/getting_feed.jpg" name="<?php echo $ini_array['camera2']['name'] ?>" >
     -->                    <canvas class="img-responsive" id="switch-e" width="640" height="480"></canvas>
                            <div class="desc">
                            <p class="desc_content"><?php echo $ini_array['camera2']['name'].' ('.$ini_array['camera2']['ip'].')'?></p>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                </div>

            </div> <!-- END ROW -->
        </fieldset>
    </div>
    <br>

    <div class="container-fluid">
        <fieldset>
        <legend>Scheduling</legend>
            <div class="row-fluid">
                <div class="col-xs-12 col-md-12"><div class="row-fluid">

                <form method="post" action="switchcontrol.php" id="crontab-form">
                    <div class="form-group">
                        <div class="col-xs-12 col-md-1">
                            <h4>Control</h4>
                            <select name="switch" id="switch" class="form-control">
                                <?php if (isset($ini_array['config']['use_433mhz_module']) && $ini_array['config']['use_433mhz_module'] == true) { ?>
                                <option value="switch-a"><?php echo $ini_array['switch-a']['name']; ?></option>
                                <option value="switch-b"><?php echo $ini_array['switch-b']['name']; ?></option>
                                <?php } ?>
                                <?php
                                if (isset($ini_array['config']['use_pir_module']) && $ini_array['config']['use_pir_module'] == true)
                                    if ($path_good)
                                        echo '<option value="switch-c">'.$ini_array['switch-c']['name'].'</option>';
                                ?>
                                <?php
                                if (isset($ini_array['config']['use_camera_module']) && $ini_array['config']['use_camera_module'] == true)
                                    if ($path_good)
                                        echo '<option value="switch-d">'.$ini_array['switch-d']['name'].'</option>'.
                                            '<option value="switch-e">'.$ini_array['switch-e']['name'].'</option>';
                                ?>
                                <?php
                                if (isset($ini_array['config']['use_dht22_module']) && $ini_array['config']['use_dht22_module'] == true)
                                    echo '<option value="switch-f">'.$ini_array['switch-f']['name'].'</option>';
                                ?>
                            </select>
                        </div>
                        <div class="col-xs-12 col-md-1">
                            <h4>State</h4>
                            <select name="state" id="state" class="form-control">
                                <option value="turn_on">On</option>
                                <option value="turn_off">Off</option>
                            </select>
                        </div>
                        <div class="col-xs-12 col-md-2">
                            <h4>Minute</h4>
                            <label for="minute_chooser_every">Every Minute</label>
                            <input type="radio" name="minute_chooser" id="minute_chooser_every" class="chooser" value="0" checked="checked">
                            <br>
                            <label for="minute_chooser_choose">Choose</label>
                            <input type="radio" name="minute_chooser" id="minute_chooser_choose" class="chooser" value="1">
                            <br>
                            <select name="minute" id="minute" class="form-control" multiple="multiple" disabled>
                                <option value="0">0</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                                <option value="6">6</option>
                                <option value="7">7</option>
                                <option value="8">8</option>
                                <option value="9">9</option>
                                <option value="10">10</option>
                                <option value="11">11</option>
                                <option value="12">12</option>
                                <option value="13">13</option>
                                <option value="14">14</option>
                                <option value="15">15</option>
                                <option value="16">16</option>
                                <option value="17">17</option>
                                <option value="18">18</option>
                                <option value="19">19</option>
                                <option value="20">20</option>
                                <option value="21">21</option>
                                <option value="22">22</option>
                                <option value="23">23</option>
                                <option value="24">24</option>
                                <option value="25">25</option>
                                <option value="26">26</option>
                                <option value="27">27</option>
                                <option value="28">28</option>
                                <option value="29">29</option>
                                <option value="30">30</option>
                                <option value="31">31</option>
                                <option value="32">32</option>
                                <option value="33">33</option>
                                <option value="34">34</option>
                                <option value="35">35</option>
                                <option value="36">36</option>
                                <option value="37">37</option>
                                <option value="38">38</option>
                                <option value="39">39</option>
                                <option value="40">40</option>
                                <option value="41">41</option>
                                <option value="42">42</option>
                                <option value="43">43</option>
                                <option value="44">44</option>
                                <option value="45">45</option>
                                <option value="46">46</option>
                                <option value="47">47</option>
                                <option value="48">48</option>
                                <option value="49">49</option>
                                <option value="50">50</option>
                                <option value="51">51</option>
                                <option value="52">52</option>
                                <option value="53">53</option>
                                <option value="54">54</option>
                                <option value="55">55</option>
                                <option value="56">56</option>
                                <option value="57">57</option>
                                <option value="58">58</option>
                                <option value="59">59</option>
                            </select>
                        </div>
                        <div class="col-xs-12 col-md-2">
                            <h4>Hour</h4>
                            <label for="hour_chooser_every">Every Hour</label>
                            <input type="radio" name="hour_chooser" id="hour_chooser_every" class="chooser" value="0" checked="checked">
                            <br>
                            <label for="hour_chooser_choose">Choose</label>
                            <input type="radio" name="hour_chooser" id="hour_chooser_choose" class="chooser" value="1">
                            <br>
                            <select name="hour" id="hour" class="form-control" multiple="multiple" disabled>
                                <option value="0">12 Midnight</option>
                                <option value="1">1 AM</option>
                                <option value="2">2 AM</option>
                                <option value="3">3 AM</option>
                                <option value="4">4 AM</option>
                                <option value="5">5 AM</option>
                                <option value="6">6 AM</option>
                                <option value="7">7 AM</option>
                                <option value="8">8 AM</option>
                                <option value="9">9 AM</option>
                                <option value="10">10 AM</option>
                                <option value="11">11 AM</option>
                                <option value="12">12 Noon</option>
                                <option value="13">1 PM</option>
                                <option value="14">2 PM</option>
                                <option value="15">3 PM</option>
                                <option value="16">4 PM</option>
                                <option value="17">5 PM</option>
                                <option value="18">6 PM</option>
                                <option value="19">7 PM</option>
                                <option value="20">8 PM</option>
                                <option value="21">9 PM</option>
                                <option value="22">10 PM</option>
                                <option value="23">11 PM</option>
                            </select>
                        </div>
                        <div class="col-xs-12 col-md-2">
                            <h4>Day</h4>
                            <label for="day_chooser_every">Every Day</label>
                            <input type="radio" name="day_chooser" id="day_chooser_every" class="chooser" value="0" checked="checked">
                            <br>
                            <label for="day_chooser_choose">Choose</label>
                            <input type="radio" name="day_chooser" id="day_chooser_choose" class="chooser" value="1">
                            <br>
                            <select name="day" id="day" class="form-control" multiple="multiple" disabled="">
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                                <option value="6">6</option>
                                <option value="7">7</option>
                                <option value="8">8</option>
                                <option value="9">9</option>
                                <option value="10">10</option>
                                <option value="11">11</option>
                                <option value="12">12</option>
                                <option value="13">13</option>
                                <option value="14">14</option>
                                <option value="15">15</option>
                                <option value="16">16</option>
                                <option value="17">17</option>
                                <option value="18">18</option>
                                <option value="19">19</option>
                                <option value="20">20</option>
                                <option value="21">21</option>
                                <option value="22">22</option>
                                <option value="23">23</option>
                                <option value="24">24</option>
                                <option value="25">25</option>
                                <option value="26">26</option>
                                <option value="27">27</option>
                                <option value="28">28</option>
                                <option value="29">29</option>
                                <option value="30">30</option>
                            </select>
                        </div>
                        <div class="col-xs-12 col-md-2">
                            <h4>Month</h4>
                            <label for="month_chooser_every">Every Month</label>
                            <input type="radio" name="month_chooser" id="month_chooser_every" class="chooser" value="0" checked="checked">
                            <br>
                            <label for="month_chooser_choose">Choose</label>
                            <input type="radio" name="month_chooser" id="month_chooser_choose" class="chooser" value="1">
                            <br>
                            <select name="month" id="month" class="form-control" multiple="multiple" disabled="disabled">
                                <option value="1">January</option>
                                <option value="2">February</option>
                                <option value="3">March</option>
                                <option value="4">April</option>
                                <option value="5">May</option>
                                <option value="6">June</option>
                                <option value="7">July</option>
                                <option value="8">Augest</option>
                                <option value="9">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                            </select>
                        </div>
                        <div class="col-xs-12 col-md-2">
                            <h4>Weekday</h4>
                            <label for="weekday_chooser_every">Every Weekday</label>
                            <input type="radio" name="weekday_chooser" id="weekday_chooser_every" class="chooser" value="0" checked="checked">
                            <br>
                            <label for="weekday_chooser_choose">Choose</label>
                            <input type="radio" name="weekday_chooser" id="weekday_chooser_choose" class="chooser" value="1">
                            <br>
                            <select name="weekday" id="weekday" class="form-control" multiple="multiple" disabled="disabled">
                                <option value="0">Sunday</option>
                                <option value="1">Monday</option>
                                <option value="2">Tuesday</option>
                                <option value="3">Wednesday</option>
                                <option value="4">Thursday</option>
                                <option value="5">Friday</option>
                                <option value="6">Saturday</option>
                            </select>
                        </div>
                    </div>
                    <div class="row-fluid">
                        <div class="col-xs-12 voffset2">
                            <button type="submit" class="btn btn-primary">Add Scheduled Job</button>
                        </div>
                    </div>
                </form>
                </div></div>
            </div>
        </fieldset>
    </div>
    <br>

    <div class="container-fluid">
         <fieldset>
            <legend>Scheduled Jobs</legend>
            <div class="row-fluid">
                <div class="col-xs-12 col-md-12"><div class="row-fluid">
                <table class="table table-striped table-responsive the-table" id="cron_table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Control</th>
                            <th>State</th>
                            <th>Schedule</th>
                            <th>Script</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                </div></div>
            </div>
        </fieldset>
    </div>

    <div id="footer">
        <div style="text-align: center;">
            <span class="version">Ver.
            <?php
                $output = shell_exec("git log --pretty=format:'%h %ai' --abbrev-commit --date=short -1");
                if (!empty($output)) {
                    list($commit, $date, $time, $offset) = split(" ", $output);
                    echo $commit . ' (' . str_replace('-', '/', $date) . ' ' . $time . ')';
                }
            ?>
            <br>bobby@bobbyromeo.com
            </span>
        </div>
    </div>

    <!-- Le javascript
    =================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="js/jquery-1.11.2.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/script.js"></script>

</body>
</html>
