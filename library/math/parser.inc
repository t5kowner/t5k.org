<?php

namespace Math;

function getDecimalExpansion(string $desc, ?float &$ln = null): string
{
    //TODO rewrite parser...
    //a temp file is needed otherwise $desc can push this over the max length for a command
    $tmpFile = tempnam(sys_get_temp_dir(), 'parser_');
    file_put_contents($tmpFile, $desc);
    $output = shell_exec("/var/www/html/primes/support/math/parser -p " . escapeshellarg($tmpFile));
    unlink($tmpFile);
    $arr = explode(" ", $output);
    $ln = $arr[1];
    return $arr[0];
}
