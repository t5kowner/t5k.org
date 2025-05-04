<?php

# Expects include("bin/basic.inc"); $db = basic_db_connect(); # Connects or dies
# to have been already called.

# This is modified from the perl routine compare ( with &desc_cmp removed )
# and calls it when necessary.  Converting &desc_cmp would be silly because
# unless the parser is converted we'd need to call it repeatedly so would
# be recompiling it repeatedly.  Calling compare just compiles it once...

# The routine on_list(desc[,log[,eps]]) checks if the parsable description
# 'a' is a prime on the list (e.g., primes in AP--check they are there!)
# 'log' should be the log of 'a' (or omitted) and 'eps' and error bound
# for the logs (limits how far we search).  Make it large for a very slow
# (but definitely accurate) search.  Should defaults to the value in
# constants.pm.

function on_list($desc = '', $log = '', $eps = '0.000001')
{
    if (empty($desc)) {
        print "ERROR: on_list() was not passed a prime description.\n";
        return 'undef';
    }

  # First, lets see if this exact description is there
    $id = lib_get_column("description = '$desc'", 'prime', 'id', $GLOBALS['db']);
    if (!empty($id)) {
        return $id;
    }

  # Sad, oh well, lets get the log base ten of the number to know what to compare to
    if (!isset($log10) or !is_real($log10)) {
        $log10 = shell_exec('/var/www/html/primes/support/math/parser -e ' .
        escapeshellarg($desc) . ' 2>&1');

        if (!is_numeric($log10)) {
            lib_warn("ERROR: on_list was passed the unparsable string \"$desc\"\n");
            return 'undef';
        }
        $log10 = $log10 / log(10);
    }
  # print "$desc has log_10 $log10 (\$eps is $eps)\n";

  # Bound the size we expect the log base 10 to be
    $upper_limit = $log10 * (1 + $eps);
    $lower_limit = $log10 / (1 + $eps);

  # How many primes are within $eps of this prime?  Hopefully none

    $number = lib_rows_in_table('prime', $GLOBALS['db'], "log10 > $lower_limit and log10 < $upper_limit");
    if ($number == 0) {
        return 'undef';
    }

  # Darn, let's call in the big guns on this one!

    $result = shell_exec('/var/www/html/primes/support/modules/compare -e ' .
        escapeshellarg($desc) . ' 2>&1');

    if (preg_match('/^(\d+|undef)$/', $result)) {
        return $result;
    }
    lib_die("unexpected result '$result' from compare when called by on_list");
}
