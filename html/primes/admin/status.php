<?php

//this is designed to be run as a cronjob
//it outputs data if it may need additional follow-up

include_once("/var/www/html/primes/bin/basic.inc");

$isDebugMode = $argc > 1;

$db = basic_db_connect();

//first check: how large is the verification backlog?
$MEGA_PRIME_VERIFICATION_THRESHOLD = 5;
$PREV_MEGA_TOTAL_FILE = "/var/www/html/primes/admin/prev_mega_total";

//similar to prime score, but without an extra log
//because we care about how long it takes to run *A* test,
//not how many tests have to be run on average to find a prime

//estimate "InProcess" tests are 50% done
$query = "select SUM(POW(log10/LOG(10),2)*LOG(log10/LOG(10))/4896907062064) from prime WHERE prime='InProcess'";
$mega_total = lib_mysql_query($query, $db)->fetch(PDO::FETCH_NUM)[0];
$query = "select SUM(POW(log10/LOG(10),2)*LOG(log10/LOG(10))/2448453531032) from prime WHERE prime='Untested'";
$mega_total += lib_mysql_query($query, $db)->fetch(PDO::FETCH_NUM)[0];

$prev_mega_total = @file_get_contents($PREV_MEGA_TOTAL_FILE);

if ($isDebugMode) {
    echo "Verification queue: $mega_total megas\n";
    echo "Previous run: $prev_mega_total megas\n";
}

if ($mega_total > $MEGA_PRIME_VERIFICATION_THRESHOLD && ($mega_total > $prev_mega_total + .05)) {
    echo "Verification queue: $mega_total megas which is greater than the threshold of $MEGA_PRIME_VERIFICATION_THRESHOLD.\n";
    echo ($mega_total - $prev_mega_total) . " greater than last check.\n";
    echo "Should more verifiers be added?\n";
}

file_put_contents($PREV_MEGA_TOTAL_FILE, $mega_total);

//TODO: second check. See if any verification processes broke (prime in "InProcess" status for a long time)
