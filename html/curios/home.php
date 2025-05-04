<?php

include("bin/basic.inc");
$db = basic_db_connect(); # Connects or dies

$index = basic_index();

### Lets see how many curios, numbers, update time...
# (counts the invisibles too, but anything else would take longer)

$query = "SHOW TABLE STATUS";
$stmt = lib_mysql_query($query, $db, 'home.php: error while seeking status');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if ($row['Name'] == 'curios') {
        $curios_count = $row['Rows'];
        $update_time = $row['Update_time'];
    } elseif ($row['Name'] == 'numbers') {
        $numbers_count = $row['Rows'];
    }
}
$update_time = preg_replace(
    "/(\d+)\-0?(\d+)\-0?(\d+) 0?(\d+:\d+):\d+/",
    "$2/$3/$1 at $4 UTC",
    $update_time
);

### Now lets define some template variables

$t_meta['description'] = "An exciting collection of curiosities,
      wonders and trivia about prime numbers and integer factorization.
      Pleasant browsing for those who love mathematics at all levels.";
$t_title = "Home Page";
# $t_subtitle = '(another <A href="/">Prime Pages</A>\' collection)';

$t_text = <<< TEXT

<figure class="figure float-right w-25 ml-3">
  <a href="https://www.amazon.com/Prime-Curios-Dictionary-Number-Trivia/dp/1448651700">
    <img src="includes/gifs/cover.png"
       class="figure-img img-fluid z-depth-3" alt="Image of the book cover">
  </a>
  <figcaption class="figure-caption pl-4 d-none d-md-inline">
    <a href="https://www.amazon.com/Prime-Curios-Dictionary-Number-Trivia/dp/1448651700">
      Order <span class="d-none d-lg-inline">the book</span> at Amazon</a>
  </figcaption>
</figure>

<p>"Prime Curios!" is an exciting collection of curiosities, wonders and trivia
related to prime numbers.</p>

<p>I have met many folk who could not see the value in stopping to smell a
wildflower, collecting a unique coin, or watching the rolling clouds in a
spring-time thunderstorm.&nbsp;  The old maxim states: "Beauty is in the eye of
the beholder."&nbsp; Why not sample a few of our curios and see how our eye
compares?</p>

<p class="border-bottom p-1 ml-3 border-top col-11  col-md-8 deep-orange lighten-5 text-center text-large">
$index
</p>

<p>This is an evolving collection at the <a href="/">Prime Pages</a>; so we would be
pleased to hear your <a href="includes/mail.php">opinions and suggestions</a>.</p>

<p>Managing Editors:
<ul>
  <li>Content editor: G. L. Honaker, Jr.
  <li>Technical editor: Reginald McLean
</ul>

<h2><span class="d-none d-sm-inline">The</span> <span class="d-inline d-sm-none">Our</span>
  Goal and Level <span class="d-none d-sm-inline">of this Collection</span></h2>

<p>Our goal is to create a collection (a dictionary if you will) of individual prime
numbers with interesting properties or forms.&nbsp;  So just what is exciting about the
prime <a href="page.php?short=313">313</a>?&nbsp; What might we discover about <a
href="page.php?number_id=23603">18180808181</a>?&nbsp; Since the number of
primes is infinite, we can not list them all; but we can list the small ones that are
especially curious.</p>

<p>Do you know an interesting number we should add?&nbsp;  Can you explain
your curio in a way that would be understandable to a general
audience?&nbsp;  In a tone that would make others want to hear more?&nbsp;
If so, <a href="submit.php">let us know</a>.</p>

<p>Similar to curios, we also have prime number related puzzles: <a href="puzzios/index.php">puzzios</a>! Check out the latest one here: <a href="puzzios/chessearch.php">CHESSearch</a>!</p>

<div class="row my-3">
<div class="mx-auto"><a class="btn btn-deep-orange font-weight-bold" href="first.php">Can you find the first missing prime curio?</a></div>
</div>

<p>There are currently $curios_count curios corresponding to $numbers_count different numbers
in our database, that leaves an infinite number for you to discover!</p>

<p class="border-top pt-2 small">Database last updated $update_time.</p>

TEXT;

include("template.php");
