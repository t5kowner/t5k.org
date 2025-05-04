<?php

#t# Locked to English only

include_once('../bin/basic.inc');
$t_adjust_path = '../';
$t_limit_lang = 'en_US';

$object = "the <i>Largest Known Primes</i> (and related pages)";
$Object = "The <i>Largest Known Primes</i> (and related pages)";
$editors = "Editor (Reginald McLean) and Backup Editor (Rytis Slatkevičius)";
$description = "$Object consists of the List of the 5000 Largest Known Primes
  (and selected smaller primes), as well as the databases of previous submissions,
  records, and pages used to search, display and otherwise interact with these
  databases.&nbsp; The related pages include subcollections such as
  <i>The Prime Glossary</i>, <i>the Top Twenty</i>, and <i>PrimePages'
  Biographies</i>.";

$t_title = "Legal Notices";
$t_text = '';
if (!empty($description)) {
    $t_text = "<p>$description";
}
$t_text = <<< HERE
      <h1><b>Copyright Notice</b></h1>
      <p>Copyright of the PrimePages' $object is held by its Managing $editors</p>
      <p>This site would not have been possible without the work of Chris Caldwell, who managed this site (hosted at primes.utm.edu) from 1994-2023.</p>
      $t_text
      <p>You have permission to use $object (excluding
        individual contributions and works derived solely from those contributions,
        for which rights are reserved by the individual authors) according to the terms of the 
        <a href="https://github.com/t5kowner/t5k.org/blob/master/LICENSE">GNU General Public License v3.0</a>. 
        Any exceptions to this, other than for purposes of fair use, are forbidden without written
        permission from a Managing Editor.</p>
      <p>Authors contributing an entry or entries to $object
        retain copyright to their submisions but grant to the Managing
        Editor(s) a license to publish their submissions and derivations thereof on the
        World Wide Web, as well as in all other media. All rights not expressly
        granted to the PrimePages and its editors, including the right to publish
        their entry or entries in any and all media, are retained by the authors.
      </p>
      <p></p>
      <h1> Licensing Agreement</h1>
      <p>By contributing to $object authors grant
        to its Managing Editor(s) a perpetual, non-exclusive, worldwide right to
        copy, distribute, transmit and publish their contribution on the World
        Wide Web. </p>
      <p>The authors also grant to the Managing Editor(s) a perpetual, non-exclusive,
        worldwide right to copy, distribute, transmit and publish any and all
        derivative works prepared or modified by the Managing Editor from the
        original contribution, in whole or in part, by any variety of methods
        on all types of publication and broadcast media, now known or hereafter
        invented. </p>
      <p>Authors also grant to $object Managing Editor(s)
        a perpetual, non-exclusive, worldwide right to translate their contribution,
        as well as any modified or derivative works, into any and all languages
        for the same purposes of copying, distributing, transmitting and publishing
        their work.</p>
      <h1>Statement of Liability </h1>
      <p>By contributing to $object authors grant
        to its Managing Editor(s) immunity from all liability arising from their
        work. All authors are responsible for securing permission to use any copyrighted
        material, including graphics, quotations, and photographs, within their
        contributions. </p>
      <p>$Object disclaim any and all responsibility
        for copyright violations and any other form of liability arising from
        the content of $object or from any material linked to
        the Encyclopedia. </p>
      <p>The Managing Editor(s) will be pleased to have any copyright violations
        brought to their attention, so that such issues may be resolved. </p>
HERE;

include("../template.php");
