<?php

class SuperClock {
    private $date = null;
    private $result = null;

    private $last_new_moon = null;
    protected $moon_synodic_period = 2551442.82048; // 29.5305882 days

    public function __construct($now = null) {
        $date = new DateTime($now, new DateTimeZone('UTC'));
        $this->date = $date;
        $this->last_new_moon = new DateTime('2019-01-06 01:28', new DateTimeZone('UTC'));
    }

    /* MOON @start */
    private function defineMoonPhases($val){
        if($val >= 0.98 || $val <= 0.02){
            return 'new_moon';
        } elseif ($val <= 0.23) {
            return 'waxing_crescent';
        } elseif ($val <= 0.27) {
            return 'first_quarter';
        } elseif ($val <= 0.48) {
            return 'waxing_gibbous';
        } elseif ($val <= 0.52) {
            return 'full_moon';
        } elseif ($val <= 0.73) {
            return 'waning_gibbous';
        } elseif ($val <= 0.77) {
            return 'last_quarter';
        } elseif ($val < 0.98) {
            return 'waning_crescent';
        }
    }

    private function getMoonPhase() {
        $diff_from_last_new_moon = $this->date->getTimestamp() - $this->last_new_moon->getTimestamp();
        $number_of_cycles = $diff_from_last_new_moon / $this->moon_synodic_period;
        $normalized_value = fmod($number_of_cycles, 1);

        return $this->defineMoonPhases($normalized_value);
    }
    /* MOON @end */

    /* MARS @start https://en.wikipedia.org/wiki/Timekeeping_on_Mars */
    private function getCoordinatedMarsTime() {
        $jd_utc = $this->date->getTimestamp() / 86400 + 2440587.5;
        $tai_utc = 37;
        $msd = ($jd_utc + $tai_utc / 86400 - 2405522.0025054) / 1.0274912517;
        $mst = new DateTime('@' . ceil(fmod($msd, 1) * 24 * 60 * 60));

        return ['date' => $msd, 'time' => $mst->format('H:i:s')];
    }
    /* MARS @end */

    /* RUNE @start */
    private function runesCalendar(){
        // Younger Futharkh (add &#x16[..];)
        $alphabet = [
            ['name' => 'fé',        'code' => 'a0'],
            ['name' => 'úr',        'code' => 'a2'],
            ['name' => 'thurs',     'code' => 'a6'],
            ['name' => 'as',        'code' => 'ac'],
            ['name' => 'reið',      'code' => 'b1'],
            ['name' => 'kaun',      'code' => 'b4'],
            ['name' => 'hagall',    'code' => 'bc'],
            ['name' => 'nauðr',     'code' => 'be'],
            ['name' => 'ísa',       'code' => 'c1'],
            ['name' => 'ár',        'code' => 'c5'],
            ['name' => 'sól',       'code' => 'cb'],
            ['name' => 'týr',       'code' => 'cf'],
            ['name' => 'björk',     'code' => 'd2'],
            ['name' => 'maðr',      'code' => 'd8'],
            ['name' => 'lögr',      'code' => 'da'],
            ['name' => 'yr',        'code' => 'e6']
        ];

        // additional 3 runes to get to a 19 years Metonic cycle
        $additional = [
            ['name' => 'arlaug',    'code' => 'ee'],
            ['name' => 'tvimadur',  'code' => 'ef'],
            ['name' => 'belgthor',  'code' => 'f0']
        ];

        $golden_numbers = array_merge($alphabet, $additional);

        $sinceAD1638 = $this->date->format('Y') - 1638;
        $point_in_cycle = $sinceAD1638 % 19; // Metonic Cycle

        $since_beginning_of_the_year = $this->date->format('z');
        $day_in_cycle = $since_beginning_of_the_year % 7;

        return [
            'year' => $golden_numbers[ $point_in_cycle ],
            'day' => $golden_numbers[ $day_in_cycle ]
        ];

    }
    /* RUNE @end */

    /* GREEK @start https://en.wikipedia.org/wiki/Attic_calendar https://en.wikipedia.org/wiki/Julian_calendar */
    private function monthsNames(){
        $months = [
            ['greek_en' => 'Gamelion',        'greek_gr' => 'Γαμηλιών',     'babylonian' => 'Šabaṭu'],
            ['greek_en' => 'Anthesterion',    'greek_gr' => 'Ἀνθεστηριών',  'babylonian' => 'Addaru'],
            ['greek_en' => 'Elaphebolion',    'greek_gr' => 'Ἑλαφηβολιών',  'babylonian' => 'Nisānu'],
            ['greek_en' => 'Mounichion',      'greek_gr' => 'Μουνιχιών',    'babylonian' => 'Āru'],
            ['greek_en' => 'Thargelion',      'greek_gr' => 'Θαργηλιών',    'babylonian' => 'Simanu'],
            ['greek_en' => 'Skirophorion',    'greek_gr' => 'Σκιροφοριών',  'babylonian' => 'Dumuzu'],
            ['greek_en' => 'Hekatombaion',    'greek_gr' => 'Ἑκατομβαιών',  'babylonian' => 'Abu'],
            ['greek_en' => 'Metageitnion',    'greek_gr' => 'Μεταγειτνιών', 'babylonian' => 'Ulūlu'],
            ['greek_en' => 'Boedromion',      'greek_gr' => 'Βοηδρομιών',   'babylonian' => 'Tišritum'],
            ['greek_en' => 'Pyanepsion',      'greek_gr' => 'Πυανεψιών',    'babylonian' => 'Samnu'],
            ['greek_en' => 'Maimakterion',    'greek_gr' => 'Μαιμακτηριών', 'babylonian' => 'Kislimu'],
            ['greek_en' => 'Poseideon',       'greek_gr' => 'Ποσειδεών',    'babylonian' => 'Ṭebētum'],
        ];

        return $months[$this->date->format('n') - 1];
    }
    /* GREEK @end */

    /* MAYAN @start */
    private function mayanCalendar(){
        $beginning_of_time = gregoriantojd(8,11,-3114);
        $baktun = 144000;
        $katun = 7200;
        $tun = 360;
        $winal = 20;

        $julian_day = gregoriantojd(
            $this->date->format('n'),
            $this->date->format('j'),
            $this->date->format('Y')
        );

        $difference = $julian_day - $beginning_of_time;

        $baktun_qty = floor($difference / $baktun);
        $difference = $difference % $baktun;

        $katun_qty = floor($difference / $katun);
        $difference = $difference % $katun;

        $tun_qty = floor($difference / $tun);
        $difference = $difference % $tun;

        $winal_qty = floor($difference / $winal);
        $difference = $difference % $winal;

        $kin_qty = $difference;

        return [
            ['name' => 'Baktun', 'value' => $baktun_qty],
            ['name' => 'K\'atun', 'value' => $katun_qty],
            ['name' => 'Tun', 'value' => $tun_qty],
            ['name' => 'Winal', 'value' => $winal_qty],
            ['name' => 'K\'in', 'value' => $kin_qty]
        ];
    }
    /* MAYAN @end */

    /* JEWISH @start */
    private function jewishCalendar(){
        $julian_day = gregoriantojd(
            $this->date->format('n'),
            $this->date->format('j'),
            $this->date->format('Y')
        );

        return mb_convert_encoding(
            jdtojewish($julian_day, true),
            "UTF-8",
            "ISO-8859-8"
        );
    }
    /* JEWISH @end */

    /* TODAY @start */
    private function numberToRomanRepresentation($number) {
        $map = array('M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1);
        $returnValue = '';
        while ($number > 0) {
            foreach ($map as $roman => $int) {
                if($number >= $int) {
                    $number -= $int;
                    $returnValue .= $roman;
                    break;
                }
            }
        }
        return $returnValue;
    }

    private function romanDate(){
        $day = $this->numberToRomanRepresentation($this->date->format('j'));
        $month = $this->numberToRomanRepresentation($this->date->format('n'));
        $year = $this->numberToRomanRepresentation($this->date->format('Y'));

        return $year . '-' . $month . '-' . $day;
    }

    private function diedOnThisDate(){
        $list = json_decode(file_get_contents('wikipedia.json'), true);

        return $list[$this->date->format('n') . '-' . $this->date->format('j')];
    }

    private function calculateEqSo(){
        $ws = new DateTime($this->date->format('Y') . '-12-21 12:00');
        $se = new DateTime($this->date->format('Y') . '-03-21 12:00');
        $ss = new DateTime($this->date->format('Y') . '-06-21 12:00');
        $ae = new DateTime($this->date->format('Y') . '-09-21 12:00');

        $equi_solc = [
            'winter_solstice' => $ws,
            'spring_equinox' => $se,
            'summer_solstice' => $ss,
            'autumn_equinox' => $ae
        ];

        $equi_solc_sings = [
            'winter_solstice'   => '&#x2651;',
            'spring_equinox'    => '&#x2648;',
            'summer_solstice'   => '&#x264B;',
            'autumn_equinox'    => '&#x264E;'
        ];

        $mapped_values = array_map(
            function($item){
                $diff = $item->diff($this->date);
                return ($diff->invert ? -1 : 1) * $diff->days;
            },
            $equi_solc
        );

        $closest = array_search(min(array_map('abs', $mapped_values)), array_map('abs', $mapped_values));

        return ['event' => $closest, 'days' => $mapped_values[$closest], 'sign' => $equi_solc_sings[$closest]];
    }

    private function peopleBorn(){
        $start = new DateTime('2019-01-01 00:01');
        $in_2019 = 7714576923;
        $every_day = 81757598 / 365.25;
        $every_second = $every_day / 86400;

        $current_total = ceil(($this->date->getTimestamp() - $start->getTimestamp()) * $every_second + $in_2019);
        return ['every_second' => $every_second, 'current_total' => $current_total];
    }

    private function speedOfStuff(){ // Km/s
        return [
            'hennessey_venom_f5' => 0.1345591667,
            'moon_revolution' => 1.0230556,
            'earth_revolution' => 29.78,
            'mercury_revolution' => 47.87,
            'halley_comet_speed' => 70.56,
            'juno_spacecraft' => 73.8,
            'speed_to_andromeda' => 110,
            'galactic_year' => 230,
            'solar_wind' => 450,
            'milky_way' => 552,
        ];
    }

    public function getInfoAboutToday() {
        $this->result = [
            'time_on_earth' => [
                'date' => $this->date->format('Y-m-d'),
                'time' => $this->date->format('H:i:s')
            ],
            'time_on_mars' => $this->getCoordinatedMarsTime(),
            'moon_phase' => $this->getMoonPhase(),
            'day_of_the_year' => $this->date->format('z'),
            'months_names' => $this->monthsNames(),
            'mayan_calendar' => $this->mayanCalendar(),
            'jewish_calendar' => $this->jewishCalendar(),
            'roman_date' => $this->romanDate(),
            'rune_calendar' => $this->runesCalendar(),
            'closest_equi_solc' => $this->calculateEqSo(),
            'world_population' => $this->peopleBorn(),
            'speed_of_stuff' => $this->speedOfStuff(),
            'on_this_day' => $this->diedOnThisDate(),
        ];

        return $this->result;
    }
    /* TODAY @end */
}

$sc = new SuperClock();
header('Content-type: application/json');
echo json_encode($sc->getInfoAboutToday());

