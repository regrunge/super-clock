var monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

var moonPhrases = {
    'new_moon': 'If the new moon holds the old moon in her lap, fair weather.',
    'waxing_crescent': 'When the moon is not full, the stars shine more brightly.',
    'first_quarter': 'I like to think that the moon is there even if I am not looking at it.',
    'waxing_gibbous': 'If you strive for the moon, maybe you\'ll get over the fence.',
    'full_moon': 'The moon, her face be red, of water she speaks.',
    'waning_gibbous': 'Aim for the moon. If you miss, you may hit a star.',
    'last_quarter': 'When a finger points to the moon, the imbecile looks at the finger.',
    'waning_crescent': 'The moon is a friend for the lonesome to talk to.',
};

var modalTimeout = null;

function modal(msg){
    if (modalTimeout) {
        clearInterval(modalTimeout);
        modalTimeout = null;
    }

    $('#modal').html('').append(msg).addClass('on');
    modalTimeout = setTimeout(function(){
        $('#modal').removeClass('on');
    }, 4000);
}

function digitalClockUpdate(){
    var date = new Date();
    var time = { hours: date.getHours(), minutes: date.getMinutes(), seconds: date.getSeconds()};
    var iters = ['hours', 'minutes', 'seconds'];
    var numberClasses = ['zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine'];

    for(var i in iters){
        var num = parseInt(time[ iters[i] ]);
        if(num < 10) {
            num = '0' + num;
        }

        var el = num.toString().split('');
        $('.digital-clock .' + iters[i] + ' .digit').each(function(index){
            var numClass = numberClasses[el[index]];
            $(this).attr('class', 'digit ' + numClass);
        });
    }
}

var worPop = 0;
function worldPopulationUpdate(increment){
    worPop += increment;
    var text = worPop.toFixed(5).toString().split('.');
    var integral = text[0].split('').map((e) => ($('<div>' + e + '</div>')));
    var decimal = text[1].split('').map((e) => ($('<div>' + e + '</div>')));
    $('#world-population .integral').html('').append(integral);
    $('#world-population .decimal').html('').append(decimal);
}

$( document ).ready(function() {
    function drawDial(target, qty, zeroed = 1){
        for(var i=0; i < qty;i++){
            var real = zeroed ? i : 1 + i;
            var num = zeroed ? ('0' + real).substr(('0' + real).length - 2) : real;
            var obj = $(
                '<span>' + num + '</span>'
            );

            var rotation = real * 360 / qty;

            obj.css({
                transformOrigin: '0 50%',
                transform: 'translateY(-50%) rotate(' + rotation + 'deg)',
            });

            if(i === 0){
                obj.addClass('zero');
            }

            target.append(obj);
        }
    }

    function dissectTime(date){
        var split = date.time.split(':');
        var seconds = parseInt(split[2]);
        var minutes = parseInt(split[1]) + (seconds / 60);
        var hours = parseInt(split[0]) + (minutes / 60);

        return { hours, minutes, seconds };
    }

    function dissectDate(date){
        var split = date.date.split('-');
        var year = split[0];
        var month = split[1] -1;
        var day = split[2];

        return new Date(year, month, day);
    }

    $.post('http://xtools.test:8888/superclock/index.php', {}, function(response){
        var toe = dissectTime(response.time_on_earth);
        var tom = dissectTime(response.time_on_mars);

        $('.analog-clock .dial').each(function(){
            var value = $(this).parents('.analog-clock').first().hasClass('mars') ? tom : toe;
            $(this).attr('class').split(' ').forEach((cl) => {
                if(value[cl]){
                    $(this).data('start', value[cl]);
                }
            });
        });

        /* Analog Clocks @start */

        $('.dial').each(function(){
            var parentWidth = $(this).parents().first().width();
            var size = parentWidth * $(this).data('size');
            $(this).css('width', size).css('height', size).css('top', (parentWidth - size) / 2).css('marginBottom', '-' + size);

            var initialRotation = 360 / ($(this).data('qty') / $(this).data('start'));

            var css = {
                width: size,
                height: size,
                top: (parentWidth - size) / 2,
                marginBottom: 0 - size,
                transform: 'rotate(-' + initialRotation + 'deg)',
            };

            $(this).css(css);

            var rotatingChild = $('<div></div>');

            rotatingChild.css({
                animationDuration: $(this).data('rotation') + 'ms',
            });

            $(this).append(rotatingChild);

            drawDial(rotatingChild, $(this).data('qty'), $(this).data('zeroed'));
        });

        /* Analog Clocks @end */

        /* Dates @start */
        var doe = dissectDate(response.time_on_earth);
        var eDateElement = $('<div class="date metal">Earth UTC: ' +
            monthNames[doe.getMonth()] + ' ' +
            doe.getDate() + ', ' +
            doe.getFullYear() +
            '</div>');

        $('.analog-clock.earth').append(eDateElement);

        var mDateElement = $('<div class="date metal">Mars Standard Date: ' + parseInt(response.time_on_mars.date) + '</div>');
        $('.analog-clock.mars').append(mDateElement);

        eDateElement.on('click', function(){
            modal('It\'s just a date, what did you expect?');
        });

        mDateElement.on('click', function(){
            modal('It\'s just a date, again. On Mars. I am unable to tell you more about it.');
        });
        /* Dates @end */

        /* Moon phase @start */
        $('#moon-phase .container .earth-shadow').addClass(response.moon_phase);
        $('#moon-phase .description .title').html(response.moon_phase.replace('_', ' '));
        $('#moon-phase .description .quote').html(moonPhrases[response.moon_phase]);
        $('#moon-phase .description').on('click', function(){
            modal('Know your Moon! Seriously, spend some time learning about it.');
        });
        /* Moon phase @end */

        /* DATE @start */
        $('#date .container .day').append(doe.getDate());
        var monthDIVs = [];
        monthDIVs.push($('<div class="english" title="ENGLISH">' + monthNames[doe.getMonth()] + '</div>'));
        Object.keys(response.months_names).forEach((key) => {
            monthDIVs.push($('<div class="' + key + '" title="' + key.replace('_', ' ').toUpperCase()+ '">' + response.months_names[key] + '</div>'));
        });

        $('#date .container .month').append(monthDIVs);
        $('#date .container .year').append(doe.getFullYear());
        /* DATE @end */

        /* CALENDARS @start */
        var mayanFlips = [];
        response.mayan_calendar.forEach((item) => {
            var tmp = $('<div><div class="flip">' + item['value'] + '</div><div class="label">'+item['name']+'</div></div>');
            mayanFlips.push(tmp);
        });

        $('#calendars .container .mayan').append(mayanFlips).on('click', function(){modal('The world ended on December the 20th 2012 when we reached the 13th BAKTUN.')});

        var runic = response.rune_calendar;
        $('#calendars .container .runic').append($('<div>(' + runic.day.name + ') &#x16' + runic.day.code + '; / (' + runic.year.name + ') &#x16' + runic.year.code + ';</div>')).on('click', function(){modal('The runic date practically tells you the day of the week and the year number in a 19 years lunar cycle.')});

        $('#calendars .container .roman').append($('<div>' + response.roman_date + '</div>')).on('click', function(){modal('The Roman wrote numbers in this funny way.')});

        $('#calendars .container .jewish').append($('<div>' + response.jewish_calendar + '</div>')).on('click', function(){modal('Straight from PHP, no idea what this is.')});

        var closestEquinoxSolstice = response.closest_equi_solc;
        $('#closest_event .sign').html(closestEquinoxSolstice.sign).on('click', function(){
            modal('It should be one of the zodiac signs, but I don\'t grok which is which.');
        });

        var equiSolName = $('<div class="metal">' + closestEquinoxSolstice.event.replace('_', ' ').toUpperCase() + '</div>');
        var equiSolDays = $('<div class="metal">' + (closestEquinoxSolstice.days >= 0 ? closestEquinoxSolstice.days + ' days ago' : 'in ' + closestEquinoxSolstice.days + ' days') + '</div>');

        equiSolName.on('click', function(){modal('Your closeness to one of four solar events.')});
        equiSolDays.on('click', function(){modal('This should be self explanatory.')});
        $('#closest_event .other').append(equiSolName).append(equiSolDays);
        /* CALENDARS @end */

        /* DIGITAL CLOCK @start */
        digitalClockUpdate();
        setInterval(function(){
            digitalClockUpdate();
        }, 1000);

        $('.digital-clock').on('click', function(){modal('This is your local time, and yes, it is shaking by design.')});
        /* DIGITAL CLOCK @end */

        /* POPULATION @start */
        var worldPopulation = response.world_population;
        worPop = worldPopulation.current_total;
        var increment = worldPopulation.every_second * 0.1;
        worldPopulationUpdate(increment);
        setInterval(function(){
            worldPopulationUpdate(increment);
        }, 100);
        /* POPULATION @end */

        /* SPEED OF STUFF @start */
        var fastThings = response.speed_of_stuff;
        var speedContainer = $('#speed .container');
        var ratio = Math.max.apply(Math, Object.values(fastThings));

        Object.keys(fastThings).forEach(function(item){
            var odo = $('<div class="odometer"></div>');
            var div = $('<div></div>');
            var span1 = $('<span>'+item.split('_').join(' ')+'</span>');
            var span2 = $('<span>'+fastThings[item]+' Km/s</span>');

            div.css({
                animationDuration: 100000 / fastThings[item] / ratio + 's',
            });

            odo.append(span1).append(span2).append(div);

            speedContainer.append(odo);
        });

        /* SPEED OF STUFF @end */

        /* ON THIS DAY @start */
        var sentence = response.on_this_day;
        var container = $('#on-this-day .container');
        if(sentence){

            container.append($('<p>On this day ' + doe.getDate() + ' ' + doe.getMonth() +' 2016 we lost:</p>'))
                .append($('<p>' + sentence + '</p>'));
        } else {
            container
                .append($('<p>On this day ' + doe.getDate() + ' ' + doe.getMonth() +' in 2016 no one died, somewhat unbelievable.</p>'))
                .append($('<p>We therefore remember some of them: Leonard Cohen, Carrie Fisher, David Bowie and Dario Fo.</p>'));
        }

        container.on('click', function(){
            modal('In 2016 some of the greatest icons in our generation have left us.')
        });

        /* ON THIS DAY @end */
    });
});