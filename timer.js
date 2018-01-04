var greenchime = true;
var yellowchime = true;
var redchime = true;

var SpeechType = (function () {
    function SpeechType(name, greenTime, yellowTime, redTime, id) {
        this.name = name;
        this.greenTime = greenTime;
        this.yellowTime = yellowTime;
        this.redTime = redTime;
        this.id = id;
    }
    return SpeechType;
})();

var TSTimer = (function () {
    function TSTimer(speeches) {
        var _this = this;
        this.started = false;
        this.speeches = speeches;
		var formattedTime = '';
		var buttoncount = 0;
		var current_name = null;
		this.audioElement = document.createElement('audio');
		this.audioElement.setAttribute('src', $('#chimeurl').val());

        $.each(this.speeches, function (indexInArray, valueOfElement) {
            var newButton = $('<span>').attr('id', valueOfElement.id).addClass('speech-type hidecount').html(valueOfElement.name);
            newButton.click(function (event) {
                _this.activateSpeech($(event.target).attr('id'));
            });
            newButton.appendTo('#buttons');
			buttoncount++;
			if((buttoncount % 6) == 0)
				$('#buttons').append('<br />&nbsp;<br />');
        });

        $(window).resize(function () {
            _this.resizeTime();
        });

        this.resizeTime();
		
        $('#btnReset').click(function () {
            _this.resetButton();
        });

        $('#btnStart').click(function () {
            _this.startButton();
        });
    }
    TSTimer.prototype.resetButton = function () {
        if(this.started)
			this.stop();
	    $('#trafficlight').text('0:00');
        $('body').css('background-color', '#EFEEEF');
        this.startTime = null;
		greenchime = true;
		yellowchime = true;
		redchime = true;
    };

    TSTimer.prototype.startButton = function () {
        if (this.started) {
            this.stop();
        } else {
            this.start();
        }
    };

    TSTimer.prototype.resizeTime = function () {
        var width = $(window).width();
        var x = Math.floor((width < 900) ? (width / 900) * 28 : 28);
        $('#trafficlight').css('font-size', x + 'em');
    };

    TSTimer.prototype.setElementText = function (elapsedSeconds) {
		this.formattedTime = this.formatTime(elapsedSeconds);
		if(this.started && (elapsedSeconds < this.overtime) && ! $('#showdigits').is(':checked'))
			$('#trafficlight').html('<img src="' + $('#stopwatchurl').val() + '" />');
	    else
			$('#trafficlight').text(this.formattedTime);
		
        if (elapsedSeconds >= this.overtime) {
            $('#trafficlight').text(this.formattedTime);
        } else if (elapsedSeconds >= this.red) {
            $('body').css('background-color', '#FF4040');
			if(redchime)
				{
				this.audioElement.play();
				redchime = false;
				}
        } else if (elapsedSeconds >= this.yellow) {
            $('body').css('background-color', '#FCDC3B');
			if(yellowchime)
				{
				this.audioElement.play();
				yellowchime = false;
				}
        } else if (elapsedSeconds >= this.green) {
            $('body').css('background-color', '#A7DA7E');
			if(greenchime)
				{
				if($('#playchime').is(':checked'))
					this.audioElement.play();
				else
					yellowchime = redchime = false;
				greenchime = false;
				}

        }
    };

    TSTimer.prototype.timerEvent = function () {
        if (!this.startTime) {
            this.startTime = new Date();
        }
        var timeNow = new Date();
        var elapsedSeconds = this.timeDiffInSeconds(this.startTime, timeNow);
        this.setElementText(elapsedSeconds);
    };

    TSTimer.prototype.timeDiffInSeconds = function (earlyTime, lateTime) {
        var diff = lateTime.getTime() - earlyTime.getTime();
        return Math.floor(diff / 1000);
    };

    TSTimer.prototype.formatTime = function (elapsedSeconds) {
        var minutes = Math.floor(elapsedSeconds / 60);
        var seconds = elapsedSeconds % 60;
        return minutes + ":" + ((seconds < 10) ? "0" + seconds.toString() : seconds.toString());
    };

    TSTimer.prototype.start = function () {
        var _this = this;
        $('#btnStart').html('Stop');
		$('.hidecount').hide();
        this.started = true;
        if (this.startTime) {
            var newStartTime = new Date().getTime() - (this.stopTime.getTime() - this.startTime.getTime());
            this.startTime.setTime(newStartTime);
        }
        this.green = this.getSecondsFromTextBox('#green-light');
        this.yellow = this.getSecondsFromTextBox('#yellow-light');
        this.red = this.getSecondsFromTextBox('#red-light');
		this.overtime = this.red + 30;
        this.timerToken = setInterval(function () {
            return _this.timerEvent();
        }, 100);
    };

    TSTimer.prototype.stop = function () {
        $('#btnStart').html('Start');
        this.started = false;
        this.stopTime = new Date();
		$('.hidecount').show();
		$('#trafficlight').text(this.formattedTime);
		this.logStopTime();
        clearTimeout(this.timerToken);
    };

	TSTimer.prototype.logStopTime = function () {
        $('#timelog').append('<p>' + $('#speakername').val() + '<br />' + this.formattedTime + '</p>');
    };

    TSTimer.prototype.getSecondsFromTextBox = function (id) {
        var greenLight = $(id).val();
        return parseInt(greenLight.split(':')[0]) * 60 + parseInt(greenLight.split(':')[1]);
    };

    TSTimer.prototype.setDefault = function () {
        this.activateSpeech('st-standard');
    };

    TSTimer.prototype.activateSpeech = function (speechId) {
		var button_name = '';
		this.resetButton();
        $.each(this.speeches, function (indexInArray, valueOfElement) {
            if (valueOfElement.id === speechId) {
                $('#green-light').val(valueOfElement.greenTime);
                $('#yellow-light').val(valueOfElement.yellowTime);
                $('#red-light').val(valueOfElement.redTime);
				button_name = this.name;
				TSTimer.current_name = button_name = button_name.replace('&nbsp;',' ');
				if((button_name == 'Standard') || (button_name == 'Advanced') || (button_name == 'Icebreaker'))
					$('#speakername').val('');
				else
					$('#speakername').val(button_name);
            }
        });
        $('.active-speech').removeClass('active-speech');
        $('#' + speechId).addClass('active-speech');
    };
    return TSTimer;
})();

$(document).ready(function () {
    var speeches = [];
    speeches.push(new SpeechType("Table&nbsp;Topics", "1:00", "1:30", "2:00", "st-table-topics"));
    speeches.push(new SpeechType("Evaluation", "2:00", "2:30", "3:00", "st-evaluation"));
    speeches.push(new SpeechType("Icebreaker", "4:00", "5:00", "6:00", "st-icebreaker"));
    speeches.push(new SpeechType("Standard", "5:00", "6:00", "7:00", "st-standard"));
    speeches.push(new SpeechType("Advanced", "8:00", "9:00", "10:00", "st-advanced"));
    speeches.push(new SpeechType("Test", "0:02", "0:06", "0:10", "st-test"));
	$('.agenda_speakers').each(function( index ) {
  		speeches.push(new SpeechType($( this ).val(), $( this ).attr('green'), $( this ).attr('yellow'), $( this ).attr('red'), "agenda-speech" + index));
	});
    var timer = new TSTimer(speeches);
    timer.setDefault();	
});
