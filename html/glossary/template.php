<?php
require_once("bin/basic.inc");  # if called directly need the colors... defined
# View this page online to see an explanation of the $t_variables

# Message will appear inside a dismissible alert tag--use just span and inline?
$t_banner_message_all_pages = '';

## CURREMTLY UNUSED!!!!!
if (empty($GLOBALS[ 't_adjust_path' ])) {
    $GLOBALS[ 't_adjust_path' ] = '';
}

##  if (!empty($t_allow_cache) and $t_allow_cache == 'no') {
##  # Assuming these are dynamic pages, do not allow cache, set $t_allow_cache to over-ride
##    @header("Last-Modified ".gmdate("D, d M Y H:i:s")," GMT");
##    @header("Expires ".gmdate("D, d M Y H:i:s")," GMT");
##    @header("Cache-Control: no-cache, must-revalidate");
##    @header("Pragma: no-cache");
##  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>The Prime Glossary: <?php echo (isset($t_title) ? $t_title : '$t_title defines the title') ?></title>
    <meta name="description" content="<?php if (!empty($t_meta['description'])) {
        echo $t_meta['description'];
                                      } else {
                                            ?> This glossary contains information on primes (and related subjects) for students in grade-school through graduate school.  Visit us to find out what a word means, to discover some new information about primes, to find a topic for a research paper, ....<?php
                                      } ?>">
    <meta name="keywords" content="<?php if (!empty($t_meta['add_keywords'])) {
        echo "$t_meta[add_keywords],";
                                   } ?> prime, primes, number theory, mathematics, curiosities, trivia, education, dictionary, definition, glossary">
    <!-- PrimePage icon -->
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
<!-- Google Fonts Roboto
Removed by Chris, see the style below -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap">
    <!-- Bootstrap core CSS -->
    <!-- link rel="stylesheet" href="mdb/css/bootstrap.min.css" -->
    <link href="/css/bootstrap-4.3.1.css" rel="stylesheet">
    <!-- Material Design Bootstrap -->
    <link rel="stylesheet" href="/mdb/css/mdb.min.css">
    <!-- Your custom styles (optional) -->
    <link rel="stylesheet" href="/mdb/css/mystyle.css">
 <style>
html p,ul,dl,ol,blockquote { font-family: "Times New Roman", Times, serif !important; }
b { font-weight: bold; }
body {font-size: 110%; }
 </style>
    <!-- link rel="stylesheet" media="print" href="print.css" / -->
    <!-- add anything to the head? -->
    <?php if (!empty($t_meta['add_lines'])) {
        echo $t_meta['add_lines'] . "\n";
    } ?>
</head>

<body>
<!--Main Navigation-->
<header>
  <!--Navbar-->
  <nav class="navbar navbar-expand-lg navbar-dark <?php echo $mdbdrkcolor;?>">

    <!-- Additional container -->
    <div class="container">

      <!-- Navbar brand -->
      <a class="navbar-brand" href="/glossary/">Prime Glossary</a>

      <!-- Collapse button -->
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#basicExampleNav"
      aria-controls="basicExampleNav" aria-expanded="false" aria-label="Toggle navigation"> <span class="navbar-toggler-icon"></span> </button>

      <!-- Collapsible content -->
      <div class="collapse navbar-collapse" id="basicExampleNav">

        <!-- Links -->
        <ul class="navbar-nav mr-auto">
          <li class="nav-item"> <a class="nav-link" href="<?php echo $t_adjust_path;?>home.php">Home</a></li>
          <li class="nav-item"> <a class="nav-link" href="<?php echo $t_adjust_path;?>index.php">Index</a></li>
          <li class="nav-item"> <a class="nav-link" href="<?php echo $t_adjust_path;?>search.php">Search</a></li>
          <li class="nav-item"> <a class="nav-link" href="<?php echo $t_adjust_path;?>includes/mail.php">Contact</a></li>
          <li class="nav-item"> <a class="nav-link" href="/notes/faq/">FAQ</a></li>
          <!-- Dropdown -->
          <li class="nav-item active dropdown"> <a class="nav-link dropdown-toggle" id="navbarDropdownMenuLink2" data-toggle="dropdown" aria-haspopup="true"
            aria-expanded="false" role="menu">PrimePages</a>
            <div class="dropdown-menu dropdown-primary" aria-labelledby="navbarDropdownMenuLink2"> <a class="dropdown-item" href="/">PrimePages' Home</a> <a class="dropdown-item" href="/primes/">5000 Largest</a> <a class="dropdown-item" href="/top20/">Top 20 Primes</a> <a class="dropdown-item" href="/bios/">Prover Bios</a> <a class="dropdown-item" href="/curios/">Prime Curios!</a> </div>
          </li>
        </ul>
        <!-- Links -->
        <script> <!-- w3.org claims this is unnecessary:  type="text/javascript" -->
          function Gsitesearch(curobj){
            curobj.q.value="site:t5k.org "+curobj.qfront.value
          }
        </script>
        <form class="form-inline" action="https://www.google.com/search" onSubmit="Gsitesearch(this)">
          <div class="md-form my-0">
            <input name="q" type="hidden">
            <input name="qfront" class="form-control mr-sm-2" type="text" placeholder="Search PrimePages" aria-label="Search">
          </div>
        </form>
      </div>
      <!-- Collapsible content -->

    </div>
    <!-- Additional container -->

  </nav>
  <!--/.Navbar-->
</header>
<!--Main Navigation-->
<header class="page-footer black p-0"> <!-- Abusing Breadcrumbs! -->
  <div class="container">
    <nav aria-label="breadcrumb">
      <!-- ol class="breadcrumb black p-1" -->
      <?php if (!empty($t_submenu)) {
            echo $t_submenu;
      } ?>
      <!-- /ol -->
    </nav>
  </div>
</header>

<!--Main layout-->
<main class="mt-5">
<!--Main container-->
<div class="container">

<!-- Start Banner Message -->
<?php if (!empty($t_banner_message_all_pages)) { ?>
<div class="mt-n5 alert <?php echo $mdbltcolor;?> alert-dismissible fade show d-print-none" role="alert"><?php echo $t_banner_message_all_pages; ?>
  <button type="button" class="close" data-dismiss="alert" aria-label="Close"> <span aria-hidden="true">&times;</span> </button>
</div>
<?php } ?>
<!-- End Banner Message -->
<h1 class="mt-n4 mb-4 rounded <?php echo $mdbmedcolor;?> p-2 pl-4 z-depth-2"> <?php echo (isset($t_title) ? $t_title : '$t_title defines the title') ?> </h1>
<?php # Add a validation test link to the test pages (only)
if (file_exists("/var/www/html/TESTSITE")) {
    if (!empty($t_title)) {
        echo '<div class="alert alert-success" role="alert">' . "\$t_title is \"$t_title\"" . '</div>';
    }
    if (!empty($t_subtitle)) {
        echo '<div class="alert alert-danger" role="alert">' . "\$t_subtitle is \"$t_subtitle\"" . '</div>';
    }
    if (!empty($t_add_menu)) {
        echo '<div class="alert alert-danger" role="alert">' . "\$t_add_menu is \"$t_add_menu\"" . ' Move to $t_submenu?</div>';
    }
    if (!empty($t_submenu)) {
        echo '<div class="alert alert-success" role="alert">' . "\$t_submenu is \"$t_submenu\"" . '</div>';
    }
    if (!empty($t_adjust_path)) {
        echo '<div class="alert alert-danger" role="alert">' . "\$t_adjust_path is \"$t_adjust_path\"" . '</div>';
    }
    if (!empty($t_allow_cache)) {
        echo '<div class="alert alert-danger" role="alert">' . "\$t_allow_cache is \"$t_allow_cache\"" . '</div>';
    }
    if (!empty($add2_keywords)) {
        echo '<div class="alert alert-danger" role="alert">add2_keywords is "' . htmlentities($add2_keywords) . '"  Use $t_meta[\'add_keywords\'] instead. </div>';
    }
    if (!empty($t_meta[ 'add_keywords' ])) {
        echo '<div class="alert alert-success" role="alert">$t_meta[\'add_keywords\'] is "' . $t_meta[ 'add_keywords' ] . '"</div>';
    }
    if (!empty($t_meta[ 'add_lines' ])) {
        echo '<div class="alert alert-success" role="alert">$t_meta[\'add_lines\'] is "' . htmlentities($t_meta[ 'add_lines' ]) . '"</div>';
    }
    if (!empty($t_meta[ 'description' ])) {
        echo '<div class="alert alert-success" role="alert">' . "\$t_meta['description'] is \"$t_meta[description]\"" . '</div>';
    }
}
?>
<?php if (empty($t_text)) { ?>
<p>Put the text for the page in the variable <b>$t_text</b> (it will replace this!)
  Other variables you can set include:</p>
<blockquote>
  <dl>
    <dt>$t_title
      <dd>Used as page title and title in the head
    <dt>$t_submenu
      <dd>NEW!!! Placed on the breadcrumbs bar
    <dt>$t_adjust_path
      <dd><strong><span class="text-danger">Curently unused!!!</span> The template is written as if page will be in the root directory (for collection), if pages are not, then need to adjust path by adding something like "../".
    <dt>$t_allow_cache
      <dd>Set to 'no' to avoid sending the headers which try to stop the browser from cache'ing pages (they are not sent by default).
    <dt>$t_meta['add_lines']
      <dd> Adds to the header lines (e.g., a base meta tag)
    <dt>$t_meta['description']
      <dd>Replaces default page description.
    <dt>$t_meta['add_keywords']
      <dd>Adds to the keywords
  </dl>
</blockquote>
    <?php
} else {
    echo $t_text;
}
?>

<!-- For print copies only -->
<div class="d-none d-print-block mt-5 pl-3">Printed from the PrimePages &lt;t5k.org&gt; &copy; Reginald McLean.</div>
</div>
</main>
<!--Main container-->

<footer class="page-footer font-small <?php echo $mdbdrkcolor;?> pt-4 mt-4">

  <!-- Footer Links -->
  <div class="container text-center">

    <!-- Grid row -->
    <div class="row">

      <!-- hr class="clearfix w-100 d-md-none pb-3" -->

      <!-- Grid column -->
      <div class="col-sm-4 mt-md-0 mt-3 mb-3">
        <!-- Links -->
        <h5 class="font-weight-bold" id="cc_more">Collections</h5>
        <ul class="list-unstyled">
          <li><a href="/glossary/">Prime Glossary</a></li>
          <li><a href="/curios/">Prime Curios!</a></li>
          <li><a href="/prove/">Proving Primality</a></li>
          <li><a href="/notes/faq/">Frequently Asked Questions</a></li>
          <li><a href="/notes/proofs/">Proofs</a></li>
        </ul>
      </div>
      <!-- Grid column -->

      <!-- Grid column -->
      <div class="col-sm-4 mb-md-0 mb-3">
        <!-- Links -->
        <h5 class="font-weight-bold">Prime Lists</h5>
        <ul class="list-unstyled">
          <li><a href="/largest.html">Largest Known Primes</a></li>
          <li><a href="/primes/">Top 5000 List</a></li>
          <li><a href="/lists/">Lists of Small Primes</a></li>
          <li><a href="/top20/">Records for Different Types</a></li>
          <li><a href="/mersenne/">Mersenne Primes</a></li>
        </ul>
      </div>
      <!-- Grid column -->

      <!-- Grid column -->
      <div class="col-sm-4 mb-md-0 mb-3">
        <!-- Links -->
        <h5 class="font-weight-bold">Pages</h5>
        <ul class="list-unstyled">
          <li> <a href="/donate.php">Donate</a></li>
          <li> <a href="/notes/by_year.html">Largest Known by Year</a></li>
          <li> <a href="/bios/">Prover Bios</a></li>
          <li> <a href="/bios/top20.php">Top 20 Provers</a></li>
          <li> <a href="/primes/includes/mail.php">Contact Us</a></li>
        </ul>
      </div>
      <!-- Grid column -->

    </div>
    <!-- Grid row -->

  </div>
  <!-- Footer Links -->

  <div class="footer-copyright text-center py-3"><!-- Library Item? "/Library/copyright.lbi" -->
    <a href="/glossary/includes/copyright.php" class="none">Copyright &copy; <?php echo date("Y"); ?></a> &nbsp; <a href="https://t5k.org/">PrimePages</a>.&nbsp; (<a href="/notes/privacy.html">Privacy notice</a>)<!-- Library Item? --></div>
</footer>
<!-- Footer -->

<!-- THIS WAS NOT IN MD5:  jQuery (necessary for Bootstrap's JavaScript plugins) -->
<!-- XX script src="../js/jquery-3.3.1.min.js"></script -->
<!-- Include all compiled plugins (below), or include individual files as needed -->
<!-- XX script src="../js/popper.min.js"></script -->
<!-- XX script src="../js/bootstrap-4.3.1.js"></script -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
</body>
</html>
