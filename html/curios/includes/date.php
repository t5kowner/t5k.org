<?php

// Humm!

include("../bin/basic.inc");

$t = getdate();
#print_r($t)

$t[hseconds] = $t[seconds] + 60 * $t[minutes];  # second of the hour
$t[dseconds] = $t[hseconds] + 3600 * $t[hours]; # Second of the day
$t[dminutes] = $t[minutes] + 60 * $t[hours];    # Minute of the day

$timeinfo = array (
  'second of the minute' => '$t[seconds]',
  'second of the hour'  => '$t[hseconds]',
  'second of the day'   => '$t[dseconds]',
  'second of the week'  => '$t[dseconds]+3600*24*$t[wday]',
  'second of the month' => '$t[seconds] +3600*24*$t[mday]',
  'second of the year'  => '$t[seconds] +3600*24*$t[yday]',

  'minute of the hour'  => '$t[minutes]',
  'minute of the day'   => '$t[dminutes]',
  'minute of the week'  => '$t[dminutes]+60*24*$t[wday]',
  'minute of the month' => '$t[dminutes]+60*24*$t[mday]',
  'minute of the year'  => '$t[dminutes]+60*24*$t[yday]',

  'hour of the day' => '$t[hours]',
  'hour of the week'    => '$t[hours]+24*$t[wday]',
  'hour of the month'   => '$t[hours]+24*$t[mday]',
  'hour of the year'    => '$t[hours]+24*$t[yday]',

  'day of the week ($t[weekday])'   => '$t[wday]+1',
  'day of the month'    => '$t[mday]',
  'day of the year'     => '$t[yday]',

  'month of the year'   => '$t[mon]',

  'year'        => '$t[year]',

  'hhmm' => 'sprintf(\'%2d%02d\',$t[hours],$t[minutes])',
  'MMDD' => 'sprintf(\'%2d%02d\',$t[mon],$t[mday])',
  'DDMM ($t[mday] $t[month])' => '$t[mday].sprintf(\'%02d\',$t[mon])',
  'MMDDYYYY' => 'sprintf(\'%2d%02d%04d\',$t[mon],$t[mday],$t[year])'
);

$out .= "<ul>\n";
foreach ($timeinfo as $key => $value) {
    eval('global $n; $n = ' . "$value;");
    $key = eval('return "' . $key . '";');
    if (gmp_prob_prime($n)) {
        $out .= "<li>The $key is prime ($n)<br>\n";
    } else {
  #     $out .= "<li>The $key is composite ($n)<br>\n";
    }
}
$out .= "</ul>\n";


$t_text = "At $t[hours]:$t[minutes]:$t[seconds] on 
	$t[mday] $t[month] $t[year]:<P>\n" . $out;
$t_title = "Is it a prime day for you too?";
$t_adjust_path = "../";
include("../template.php");
