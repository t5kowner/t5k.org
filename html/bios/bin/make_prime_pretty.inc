<?php

#t# ready (rtl span)

#   &MakePretty($String[,\%Options]) reformats math for HTML display.
#       Changes ^ to sup (e.g., 2^(3+4) -> 2<sup>3+4</sup>)...
#       adds white-space and back quotes when necessary to help
#       line wrapping

function MakePretty($string, $options = '')
{
    if (empty($string)) {
        return('');
    }
  # lib_mydie("No string passed to MakePretty");
  # use <sup> for integer exponents
    $string = preg_replace('/([^\^]+)\^(\d+)$/', '$1<sup>$2</sup>', $string);
    $string = preg_replace('/([^\^]+)\^(\d+)([\+\-\*\/\)\],])/', '$1<sup>$2</sup>$3', $string);
  # change multiplication symbols
  # $string = preg_replace('/\*/',' &middot; ',$string);
    $string = preg_replace('/\*/', '&#8202;&middot;&#8202;', $string);  # &hairsp; is U+200A or &#8202;
  # change +/- to &plusmn;  (ISO-Latin 1 #177)
    $string = preg_replace('/\+\/\-/', '&plusmn;', $string);
  # Let the second term in binary ! be a subscript
    $string = preg_replace('/(\d+)\!(\d+)/', '$1!<sub>$2</sub>', $string);
  # Add a space after commas
    $string = preg_replace('/\,/', ', ', $string);
  # Add a spaces around +, -
    $string = preg_replace('/\+/', ' + ', $string);
    $string = preg_replace('/\-/', ' - ', $string);
    $string = preg_replace('/(\d)\/(\d)/', '$1&#8202;/&#8202;$2', $string);
  # The very very long integers need to be split.
    $string = preg_replace("/(\d+)(\d{50})/", '$1\\\\<br> $2', $string);
    $string = preg_replace("/(\d+)(\d{50})/", '$1\\\\<br> $2', $string);
    return("<span dir=ltr>$string</span>");
}
