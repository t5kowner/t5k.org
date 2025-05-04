<?php

include_once("bin/basic.inc");    #  Used currently only fr blocking evil... 7/2009

$t_meta['description'] = "Options to download lists of the largest primes.";
$t_title = "The Top 5000: Downloading the List";
// $t_meta['add_keywords'] = "history, introduction, uses, Gauss";
$t_subtitle = "The List of Largest Known Primes";
$t_adjust_path = '';
$t_submenu = 'download';

$directory = "/var/www/html/primes/lists";
$size_all_txt = fsize("$directory/all.txt");
$file_date_stamp = date("d F Y h:i:s a", filemtime("$directory/all.txt"));
$size_all_zip = fsize("$directory/all.zip");

$t_text = <<< TEXT
  <p>The list of 5000 largest known primes will always be available here.&nbsp;
  You can view it and <a href="search.php">search it</a> here!  If you want
  to download a snapshot of the list to print or view at another time, there
  are several possibilities (these files were last modified on $file_date_stamp).
  Please use the contact link above to let us know of any problems.</p>
        <dl>
          <dt><a href="/primes/lists/all.txt">all.txt</a> </dt>
          <dd>The whole list as a printable text file!&nbsp; This is a
		large file: $size_all_txt.</dd>
          <dt><a href="/primes/lists/all.zip">all.zip</a> </dt>
          <dd>The whole list (all.txt) zipped, so it is roughly one fourth the
            size of all.txt: $size_all_zip.</dd>
        </dl>

      <p>You might also consider one of the summary pages (these are automatically
        updated when new primes are accepted onto the list!)</p>
        <dl>
          <dt><a href="/top20/index.php">The Top Twenty</a></dt>
          <dd>The Top Twenty lists is a series of pages each dedicated to listing
            the top twenty primes of certain selected forms.</dd>
          <dt><a href="/largest.html">The Largest Known Primes</a></dt>
          <dd>This is a single page which summarizes much of the information in
            the pages about the 5000 largest known primes from introduction
	    through list of the top ten primes of a few selected forms.</dd>
        </dl>

      <p>Related lists outside of this collection:</p>
        <dl>
	  <dt>Henri & Renaud Lifchitz's <a href="http://www.primenumbers.net/prptop/prptop.php">PRP Top record</a>
	  <dd>A list of large <a
	    href="/glossary/page.php?sort=PRP" class="glossary">probable-primes</a>.&nbsp;
	    These are numbers that are
	    likely to be prime, but the primality has not been proven.&nbsp;
	    (The lists above are of proven primes only.)
        </dl>
TEXT;

include("template.php");

function fsize($file)
{
       $a = array("B", "KB", "MB", "GB", "TB", "PB");
       $pos = 0;
       $size = filesize($file);
    while ($size >= 1024) {
           $size /= 1024;
            $pos++;
    }
      return round($size, 2) . " " . $a[$pos];
}
