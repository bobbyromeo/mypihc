var MyPi = {};
MyPi.updateTimeInterval = 1000;
MyPi.updateDHT22Interval = 60000;
MyPi.updateCheckSwitchInterval = 60000;
MyPi.updateUptimeInterval = 60000;
MyPi.updateImageInterval = 250;
MyPi.feed = {
    'switch-q': {
        'context': ($('canvas#switch-q').length) ? $('canvas#switch-q')[0].getContext('2d') : undefined,
        'image': new Image(),
    },
    'switch-r': {
        'context': ($('canvas#switch-r').length) ? $('canvas#switch-r')[0].getContext('2d') : undefined,
        'image': new Image(),
    }
};

MyPi.feed['switch-q'].image.onload = function () {
    MyPi.feed['switch-q'].context.drawImage(MyPi.feed['switch-q'].image, 0, 0);
}

MyPi.feed['switch-r'].image.onload = function () {
    MyPi.feed['switch-r'].context.drawImage(MyPi.feed['switch-r'].image, 0, 0);
}

$(document).ready(function() {
    $.ajaxSetup({ cache: false });

    // Form choosers
    enableChooser();

    // Get list of current scheduled jobs and add to interface
    refreshCronTable(addEvents);

    // Get backend time and show in interface
    updateTime();

    // Calculate uptime of backend server and add to interface
    updateUptime();

    // If camera module is enabled
    if ($(':hidden#use_camera_module').val()) {
        // See if camera recordings are on by checking for pid file in backend
        checkSwitchStatus('q');
        checkSwitchStatus('r');
        // Begin getting feeds from cameras
        updateImage('q');
        updateImage('r');
        // Add links in canvases to allow for pan/tilt
        canvasLinks('q');
        canvasLinks('r');
    }


    // See if the PIR alarm is on by checking for pid file in backend
    if ($(':hidden#use_pir_module').val()) checkSwitchStatus('p');

    // If DHT22 temp./humdity sensor enabled
    if ($(':hidden#use_dht22_module').val()) updateDHT22();

    // Add events for switch Buttons
    $('.switchButton').each(function(index, button) {
        button = $(button);
        // Get letter
        var regexSwitchID = /switch-([a-z])/;
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

    // Form for crontabs additions
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
    var label = $('label[class*="switch-'+switchID+'"]');

    function on_error(text) {
        alert("Switch control failed, please check log: " + ((text)?': '+text: 'unknown'));
        label.removeClass("text-danger");
        $('#switch-'+switchID+'-spinner').addClass('hidden');
    }
    $.get('switch_manager.php', { 'action': 'control', 'switch': switchID, state: state })
        .done(function(data) {
            //console.log(data);
            if (data.result) {
                if (switchID == "p" || switchID == "q" || switchID == "r") {
                    if (data.output.indexOf("started") > -1) {
                        label.addClass("text-danger");
                        $('#switch-'+switchID+'-spinner').removeClass('hidden');
                    }
                    if (data.output.indexOf("stopped") > -1) {
                        label.removeClass("text-danger");
                        $('#switch-'+switchID+'-spinner').addClass('hidden');
                    }
                }
            } else {
               on_error(data.output);
            }
        })
        .fail(function() {
            on_error();
        });
}

function checkSwitchStatus(switchID) {
    var label = $('label[class*="switch-'+switchID+'"]');
    $.ajax({
        url: 'switch_manager.php',
        type: 'GET',
        timeout: 10000,
        data: { action: "checkSwitchStatus", "switch": switchID },
    }).done(function(data) {
        if (data.result) {
            label.addClass("text-danger");
            $('#switch-'+switchID+'-spinner').removeClass('hidden');
        } else {
            label.removeClass("text-danger");
            $('#switch-'+switchID+'-spinner').addClass('hidden');
        }
        setTimeout(function () {
            checkSwitchStatus(switchID);
        }, MyPi.updateCheckSwitchInterval);
    }).fail(function(jqXHR, textStatus) {
        label.removeClass("text-danger");
        $('#switch-'+switchID+'-spinner').addClass('hidden');
        if(textStatus == 'timeout') {
            alert('Unable to poll for switch status');
        }
    });
}

function updateDHT22() {
    $("#dht22").empty();
    $('#ajaxSpinnerContainer').show();
    $.ajax({
        url: 'site_manager.php',
        type: 'GET',
        timeout: 10000,
        data: { action: "dht22" },
    }).done(function(data) {
        if (data.result) {
            $("#dht22").html(data.output.temperature + (data.output.unit == 'fahrenheit' ? '&#8457; / ' : '&#8451; / ') + data.output.humidity + '%');
            $('#ajaxSpinnerContainer').hide();
            setTimeout(function () {
                updateDHT22();
            }, MyPi.updateDHT22Interval);
        }
    }).fail(function(jqXHR, textStatus) {
        $('#ajaxSpinnerContainer').hide();
        if(textStatus == 'timeout') {
            $("#dht22").html('Unable to poll DHT22 sensor');
        }
    });
}

function updateTime() {
    $.ajax({
        url: 'site_manager.php',
        type: 'GET',
        timeout: 10000,
        data: { action: "updatetime" },
    }).done(function(data) {
        if (data.result) {
            $("#timer").html(data.output);
            setTimeout(function () {
                updateTime();
            }, MyPi.updateTimeInterval);
        }
    }).fail(function(jqXHR, textStatus) {
        if(textStatus == 'timeout') {
            alert('Unable to poll for time');
        }
    });
}

function updateUptime() {
    $.ajax({
        url: 'site_manager.php',
        type: 'GET',
        timeout: 10000,
        data: { action: "uptime" },
    }).done(function(data) {
        if (data.result) {
            $("#uptime").html(data.output);
            setTimeout(function () {
                updateUptime();
            }, MyPi.updateUptimeInterval);
        }
    }).fail(function(jqXHR, textStatus) {
        if(textStatus == 'timeout') {
            alert('Unable to poll for up time');
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
        url: 'site_manager.php',
        type: 'GET',
        timeout: 10000,
        data: { action: "updateImage", "switch": switchID },
    }).done(function(data) {
        if (data.result) {
            //$('.btn-group .switch-'+switchID).attr('disabled', false);
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


function reloadConfig() {
    function on_error() {}
    $.ajax({
        url: 'site_manager.php',
        type: 'GET',
        timeout: 10000,
        data: { action: "reloadConfig" },
    }).done(function(data) {
        if (data.result) {
            location.reload();
        } else {
            on_error();
        }
    }).fail(function(jqXHR, textStatus, errorThrown) {
        //alert("Error: " + textStatus + ": " + errorThrown);
        on_error();
    });
}


function canvasLinks(switchID) {
    // Get canvas
    var canvas = document.getElementById("switch-"+switchID);
    // 2d context
    var ctx = canvas.getContext("2d");
    // ctx.translate(0.5, 0.5); // * Move the canvas by 0.5px to fix blurring

    // Block border
    // ctx.strokeStyle = "#5F7FA2";
    // ctx.strokeRect(50, 50, 185, 90);

    // Photo
    // var img = new Image();
    // img.src = "http://...";
    // img.onload = function(){
    //     ctx.drawImage(img, 59.5, 59.5); // Use -0.5px on photos to prevent blurring caused by * fix
    // }

    // Text
    // ctx.fillStyle = "#000000";
    // ctx.font = "15px Tahoma";
    // ctx.textBaseline = "top";
    // ctx.fillText("Username", 95, 65);

    // ***** Magic starts here *****
    var timeoutId = 0;

    // Links
    var Links = new Array(); // Links information
    var hoverLink = ""; // Href of the link which cursor points at
    ctx.fillStyle = "#000000"; // Default blue link color
    ctx.font = "25px Courier New"; // Monospace font for links
    ctx.textBaseline = "top"; // Makes left top point a start point for rendering text

    // Draw the link
    function drawLink(x,y,href,title){
        var linkTitle = title,
            linkX = x,
            linkY = y,
            linkWidth = ctx.measureText(linkTitle).width,
            linkHeight = parseInt(ctx.font); // Get lineheight out of fontsize

        // Draw the link
        // ctx.fillText(linkTitle, linkX, linkY);

        // Underline the link (you can delete this block)
        ctx.beginPath();
        // ctx.moveTo(linkX, linkY + linkHeight);
        // ctx.lineTo(linkX + linkWidth, linkY + linkHeight);
        // ctx.lineWidth = 1;
        // ctx.strokeStyle = "#0000ff";
        // ctx.stroke();

        // Add mouse listeners
        canvas.addEventListener("mousemove", on_mousemove, false);
        //canvas.addEventListener("click", on_click, false);
        canvas.addEventListener("mousedown", on_click, false);
        canvas.addEventListener("mouseup", on_mouseup, false);
        canvas.addEventListener("mouseleave", on_mouseup, false);

        // Add link params to array
        Links.push(x + ";" + y + ";" + linkWidth + ";" + linkHeight + ";" + href);
    }

    var on_mouseup = function(e){
        clearInterval(timeoutId);
    }

    // Link hover
    function on_mousemove (ev) {
        var x, y;

        // Get the mouse position relative to the canvas element
        if (ev.layerX || ev.layerX == 0) { // For Firefox
            x = ev.layerX;
            y = ev.layerY;
        }

        // Link hover
        for (var i = Links.length - 1; i >= 0; i--) {
            var params = new Array();

            // Get link params back from array
            params = Links[i].split(";");

            var linkX = parseInt(params[0]),
                linkY = parseInt(params[1]),
                linkWidth = parseInt(params[2]),
                linkHeight = parseInt(params[3]),
                linkHref = params[4];

            // Check if cursor is in the link area
            if (x >= linkX && x <= (linkX + linkWidth) && y >= linkY && y <= (linkY + linkHeight)){
                document.body.style.cursor = "pointer";
                hoverLink = linkHref;
                break;
            }
            else {
                document.body.style.cursor = "";
                hoverLink = "";
            }
        };
    }

    // Link click
    function on_click(e) {
        if (hoverLink){
            //window.open(hoverLink); // Use this to open in new tab
            //window.location = hoverLink; // Use this to open in current window
            timeoutId = setInterval(function() {
                $.ajax({
                    url: 'site_manager.php',
                    type: 'GET',
                    timeout: 2000,
                    data: { action: "move", "switch": switchID, direction: hoverLink },
                });
            }, 250);
        }
    }

    // Ready for use ! You are welcome !
    drawLink(1,$(canvas).height()/2-10,"left","LEFTY");
    drawLink($(canvas).width()-50,$(canvas).height()/2-10,"right","RIGHT");
    drawLink($(canvas).width()/2-20,0,"up","TOPPY");
    drawLink($(canvas).width()/2-20,$(canvas).height()-20,"down","DOWNY");
}

var waitForFinalEvent = (function () {
    var timers = {};
    return function (callback, ms, uniqueId) {
        if (!uniqueId) {
            uniqueId = "Don't call this twice without a uniqueId";
        }
        if (timers[uniqueId]) {
            clearTimeout (timers[uniqueId]);
        }
        timers[uniqueId] = setTimeout(callback, ms);
    };
})();

$(window).resize(function () {
    waitForFinalEvent(function(){
        canvasLinks('q');
        canvasLinks('r');
      //...
    }, 500, "some unique string");
});
