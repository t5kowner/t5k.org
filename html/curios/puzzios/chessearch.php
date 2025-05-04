<?php

$t_meta['description'] = "A prime puzzle (puzzio).";
$t_title = "Prime Puzzios: CHESSearch";
$t_meta['add_keywords'] = "prime,puzzle,puzzio,chess";

$t_text = <<<HERE
  <div style="text-align: center;">
	  <h1 style="font-weight: 500"><a href="https://www.lulu.com/shop/michael-keith-and-g-l-honaker-jr/chessearch/paperback/product-v8kndkr.html">CHESSearch</a></h1>
	  <h6>by <a href="/curios/ByOne.php?submitter=Honaker">Honaker</a> and <a href="/curios/ByOne.php?submitter=Keith">Keith</a></h2>
	  <img src="../includes/puzzios/chess.png"/>
	  </br></br>
	  <div style="font-size: 1.1rem">
		  <p>Find the square where a queen can be placed to attack* five digits.</p>
		  <p>Then put the <i>unattacked</i> digits in the right order to form a prime number.</p>
	  </div>
	  <p>Hint: At the time Magnus Carlsen achieved this rating, it was the highest in chess history.</p>
	  <p>*Note that the queen does not attack the square it sits on.</p>
  </div>
HERE;

$t_adjust_path = '../';
include("../template.php");
