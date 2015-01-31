var MyPi = {};
MyPi.updateTimeInterval = 1000;
MyPi.updateDHT22Interval = 60000;
MyPi.updateUptimeInterval = 60000;
MyPi.updateImageInterval = 250;
MyPi.feed = {
    'switch-d': {
        'context': ($('canvas#switch-d').length) ? $('canvas#switch-d')[0].getContext('2d') : undefined,
        'image': new Image(),
    },
    'switch-e': {
        'context': ($('canvas#switch-e').length) ? $('canvas#switch-e')[0].getContext('2d') : undefined,
        'image': new Image(),
    }
};

MyPi.feed['switch-d'].image.onload = function () {
    MyPi.feed['switch-d'].context.drawImage(MyPi.feed['switch-d'].image, 0, 0);
}

MyPi.feed['switch-e'].image.onload = function () {
    MyPi.feed['switch-e'].context.drawImage(MyPi.feed['switch-e'].image, 0, 0);
}

$(document).ready(function() {
    $.ajaxSetup({ cache: false });
    
    enableChooser();
    refreshCronTable(addEvents);
    updateTime();
    updateUptime();

    if ($(':hidden#use_dht22_module').val()) updateDHT22();
    if ($(':hidden#use_camera_module').val()) {
        updateImage('d');
        updateImage('e');
    }

    // Buttons
    $('.switchButton').each(function(index, button) {
        button = $(button);
        // Get letter
        var regexSwitchID = /switch-([a-e])/;
        var switchID = regexSwitchID.exec(button.attr('class'))[1];
        if(button.hasClass('on')) {
            button.click(function() {
                turnOn(switchID);
            });
        } else if(button.hasClass('off')) {
            button.click(function() {
                turnOff(switchID);
            });
        }
    });

    $("#crontab-form").submit(function(event) {
        event.preventDefault();
        var form = this;
        var data = $('#crontab-form').serializeArray();
        data.push({name: 'action', value: 'addcron'});
        $.post('crontab_manager.php', data)
            .done(function(data) {
                if (data.result) {
                    refreshCronTable(addEvents);
                }
            })
            .fail(function() {
                alert( "$.post failed!" );
            })
            .always(function() {
                form.reset();
                disableChooser();
            });
    });
});

function disableChooser(){
    $("input[class*='chooser']").each(function () {
        var for_element = this.name.replace(/_chooser/,"");
        $('#'+for_element).prop("disabled", true);
    });
}

function enableChooser() {
    $(".chooser").click(function(e) {
        var for_element = this.name.replace(/_chooser/,"");
        $('#'+for_element).prop("disabled", this.value !== "1");
        $('#'+for_element).scrollTop(30);
    });
}

function turnOn(switchID) {
    callSwitchControl(switchID, 1);
}

function turnOff(switchID) {
    callSwitchControl(switchID, 0);
}

function callSwitchControl(switchID, state) {
    function on_error(text) {
        alert("Control failed"+((text)?': '+text:''));
    }
    $.get('crontab_manager.php', { 'action': 'control', 'switch': switchID, state: state })
        .done(function(data) {
            if (data.result) {
                var label = $('label[class="switch-'+switchID+'"]');
                if (switchID == "c" || switchID == "d" || switchID == "e") {
                    if (data.output.indexOf("started") > -1) {
                        var value = label.text();
                        var result = value.replace(/^([\w\W]*)(\b\s*)$/, "$& <span class=\"text-danger\">(Running...)</span>");
                        $(label).html(result);
                    }
                    if (data.output.indexOf("stopped") > -1)
                        label.find("span").remove();
                }
            } else {
               on_error(data.output); 
            }
        })
        .fail(function() {
            on_error();
        });
}

function updateDHT22() {
    $.ajax({
        url: 'update.php',
        type: 'GET',
        cache: false,
        timeout: 10000,
        data: { action: "dht22" },
    }).done(function(data) {
        if (data.result) {
            $("#dht22").html(data.output.temperature+'&#8451; / '
                + data.output.humidity + '%');
            setTimeout(function () {
                updateDHT22();
            }, MyPi.updateDHT22Interval);
        }
    }).fail(function(jqXHR, textStatus) {
        if(textStatus == 'timeout') {     
            $("#dht22").html('Unable to poll DHT22 sensor'); 
        }
    });
}

function updateTime() {
    $.ajax({
        url: 'update.php',
        type: 'GET',
        cache: false,
        data: { action: "updatetime" },
    }).done(function(data) {
        if (data.result) {
            $("#timer").html(data.output);
            setTimeout(function () {
                updateTime();
            }, MyPi.updateTimeInterval);
        }
    });
}

function updateUptime() {
    $.ajax({
        url: 'update.php',
        type: 'GET',
        cache: false,
        data: { action: "uptime" },
    }).done(function(data) {
        if (data.result) {
            $("#uptime").html(data.output);
            setTimeout(function () {
                updateUptime();
            }, MyPi.updateUptimeInterval);
        }
    });
}

function updateImage(switchID) {
    function on_error() {
        $('.btn-group .switch-'+switchID).attr('disabled', true);
        $("#switch option[value='switch-"+switchID+"']").remove();
        $('canvas#switch-'+switchID).parent().find('p.desc_content').text('Feed disabled');
    }
    $.ajax({
        url: 'update.php',
        type: 'GET',
        cache: false,
        timeout: 2000,
        data: { action: "updateImage", "switch": switchID },
    }).done(function(data) {
        if (data.result) {
            $('.btn-group .switch-'+switchID).attr('disabled', false);
            displayImage(data.output, switchID);
            setTimeout(function () {
                updateImage(switchID);
            }, MyPi.updateImageInterval);
        } else {
            on_error();
        }
    }).fail(function(jqXHR, textStatus, errorThrown) {
        //alert("Error: " + textStatus + ": " + errorThrown);
        on_error();
    });
}

// var displayImage = function (base64Data, elementID) {
//     $('img#switch-'+elementID).attr("src", "data:image/jpg;base64," + base64Data);
// };

function displayImage(base64Data, elementID) {
    var imageObj = MyPi.feed['switch-'+elementID].image;
    imageObj.src = "data:image/png;base64," + base64Data;
}

function addEvents() {
    $('.btn-danger.delete').click(function(e){
        var uuid = $(this).closest('tr').find( ":hidden[name=comments]" ).val();
        $.get('crontab_manager.php', { 'action': 'delete', 'uuid': uuid })
            .done(function(data) {
                if(data.result) {
                    refreshCronTable(addEvents);
                }
            })
            .fail(function() {
                alert( "Delete cron failed!" );
            });
    });

    $('.btn-default.disable').click(function(e){
        var enabled = $(this).closest('tr').find( ":hidden[name=enabled]" ).val();
        var uuid = $(this).closest('tr').find( ":hidden[name=comments]" ).val();
        $.get('crontab_manager.php', { 'action': 'disable', 'enabled': enabled,'uuid': uuid })
            .done(function(data) {
                if(data.result) {
                    refreshCronTable(addEvents);
                }
            })
            .fail(function() {
                alert( "Disable cron failed!" );
            });
    });
}

function refreshCronTable(callback) {
    $.get('crontab_manager.php', { 'action': 'listcron'}) 
        .done(function(data) {
            if(data.result) {
                var tblRow = '';
                if (!data.output.length) {
                    $('#cron_table').find('thead').hide()
                    tblRow = '<em>No jobs scheduled.</em>';
                } else {
                    $('#cron_table').find('thead').show();
                }
                $.each(data.output, function(i, cron){
                    tblRow += (cron.enabled ? '<tr>' : '<tr class="danger">');
                    tblRow += "<td>"+i+"</td>";

                    var comments = cron.comments.split(":");
                    tblRow += '<td>'+comments[2]+'</td>';
                    tblRow += '<td>'+comments[3].replace('_', ' ')+'</td>';

                    tblRow += '<td>'+[
                        cron.minutes,
                        cron.hours,
                        cron.dayOfMonth,
                        cron.months,
                        cron.dayOfWeek].join(' ')+'</td>';

                    tblRow += '<td>'+cron.taskCommandLine+'</td>';

                    tblRow += '<td><div class="btn-group pull-left">'
                        +'<button type="button" class="btn btn-default disable" aria-label="Left Align">'
                        +'<span class="'+(cron.enabled ? 'glyphicon glyphicon-pause' : 'glyphicon glyphicon-play')+'" aria-hidden="true"></span></button>'
                        +'<button type="button" class="btn btn-danger delete" aria-label="Left Align">'
                        +'<span class="glyphicon glyphicon-remove" aria-hidden="true"></span></button>'
                        +'</div></td>';

                    tblRow += '<input type="hidden" name="comments" value="'+cron.comments+'" />'
                        +'<input type="hidden" name="enabled" value="'+cron.enabled+'" />';
                    tblRow += "</tr>";
                });
                $('#cron_table tbody').html(tblRow);

                if (typeof callback === "function")
                    callback();
            }
        })
        .fail(function() {
            alert( "List cron table failed!" );
        });
}
