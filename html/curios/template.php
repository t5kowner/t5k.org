<?php
# View this page online to see an explanation of the $t_variables
require_once("bin/basic.inc");  # if called directly need the colors... defined

# show all errors on text pages error_reporting(E_ALL);
if (file_exists("/var/www/html/TESTSITE")) {
    error_reporting(E_ALL);
}

# Message will appear inside a dismissible alert tag--use just span and inline?
# Keep any <div> tags... here (rather than where this is preprended to $t_text below)
$t_banner_message_all_pages = '';
# $t_banner_message_all_pages = 'We just completed a major update. <a href="https://primes.utm.edu/primes/includes/mail.php">Let us know</a> if anything is not working.';

# Defaults
if (!isset($t_adjust_path)) {
    $t_adjust_path = '';
}
if (!isset($t_title2) and isset($t_title)) {
    $t_title2 = $t_title;  # In rare cases the browser window title (title2) should not match the page title
}

if (isset($t_allow_cache) and $t_allow_cache == 'no') {
  # Sometimes these are dynamic pages, set $t_allow_cache to 'no' to try to avoid
  # browser caching.
    @header("Last-Modified " . gmdate("D, d M Y H:i:s"), " GMT");
    @header("Expires " . gmdate("D, d M Y H:i:s"), " GMT");
    @header("Cache-Control: no-cache, must-revalidate");
    @header("Pragma: no-cache");
}

#Curios page only
if (empty($GLOBALS[ 't_class' ])) {
    $GLOBALS[ 't_class' ] = '';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Prime Curios! <?php echo (isset($t_title2) ? $t_title2 : '$t_title defines the title (and $t_title2)') ?></title>
    <meta name="description" content="<?php if (!empty($t_meta['description'])) {
        echo $t_meta['description'];
                                      } else {
                                            ?>An exciting collection of curiosities, wonders and trivia about prime numbers and integer factorization. Pleasant browsing for those who love mathematics at all levels.<?php
                                      } ?>">
    <meta name="keywords" content="<?php if (!empty($t_meta['add_keywords'])) {
        echo "$t_meta[add_keywords],";
                                   } ?> curios trivia dictionary prime, primes, number theory, mathematics, curiosities">
    <!-- PrimePage icon -->
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <!-- Google Fonts Roboto -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap">
    <!-- Bootstrap core CSS -->
    <!-- link rel="stylesheet" href="mdb/css/bootstrap.min.css" -->
    <link href="/css/bootstrap-4.3.1.css" rel="stylesheet">
    <!-- Material Design Bootstrap -->
    <link rel="stylesheet" href="/mdb/css/mdb.min.css">
    <!-- Your custom styles (optional) -->
    <link rel="stylesheet" href="/mdb/css/mystyle.css">
    <!-- link rel="stylesheet" media="print" href="print.css" / -->
    <!-- add anything to the head? -->
    <?php if (!empty($t_meta['add_lines'])) {
        echo $t_meta['add_lines'] . "\n";
    } ?>

    <?php if (isset($canonical)) : ?>
    <link rel="canonical" href="<?= $canonical ?>" />
    <?php endif; ?>
</head>

<body>
<!--Main Navigation-->
<header>
  <!--Navbar-->
  <nav class="navbar navbar-expand-lg navbar-dark <?php echo $mdbdrkcolor;?>">

    <!-- Additional container -->
    <div class="container">

      <!-- Navbar brand -->
      <a class="navbar-brand" href="/curios/">Prime Curios!</a>

      <!-- Collapse button -->
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#basicExampleNav"
      aria-controls="basicExampleNav" aria-expanded="false" aria-label="Toggle navigation"> <span class="navbar-toggler-icon"></span> </button>

      <!-- Collapsible content -->
      <div class="collapse navbar-collapse" id="basicExampleNav">

        <!-- Links -->
<?php
if (isset($number_id) and is_numeric($number_id)) {
    $temp = "?number_id=$number_id";
} elseif (isset($short) and is_numeric($short)) {
    $temp = "?long=$short";
} else {
    $temp = "";
}
?>
        <ul class="navbar-nav mr-auto">
          <li class="nav-item"> <a class="nav-link" href="<?php echo $t_adjust_path;?>home.php">Home</a></li>
          <li class="nav-item"> <a class="nav-link" href="<?php echo $t_adjust_path;?>index.php">Index</a></li>
          <li class="nav-item"> <a class="nav-link" href="<?php echo $t_adjust_path;?>search.php">Search</a></li>
          <li class="nav-item"> <a class="nav-link" href="/glossary/">Glossary</a></li>
          <!-- Dropdown -->
          <li class="nav-item active dropdown"> <a class="nav-link dropdown-toggle" id="navbarDropdownMenuLink2" data-toggle="dropdown" aria-haspopup="true"
            aria-expanded="false" role="menu">Participate</a>
            <div class="dropdown-menu dropdown-primary" aria-labelledby="navbarDropdownMenuLink2">
<a class="dropdown-item" href="<?php echo $t_adjust_path;?>includes/mail.php">Mail Editors</a>
<a class="dropdown-item" href="<?php echo $t_adjust_path . 'submit.php' . $temp;?>">Submit Curio</a>
<a class="dropdown-item" href="<?php echo $t_adjust_path;?>puzzios/">Prime Puzzios</a>
<a class="dropdown-item" href="<?php echo $t_adjust_path;?>includes/primetest.php">Test Number</a></div>
          </li>
        </ul>
        <!-- Links -->
        <form class="form-inline" action="<?php echo $t_adjust_path;?>page.php" method="get">
          <div class="md-form my-0">
            <input name="short" class="form-control mr-sm-2" type="text" placeholder="Search Curios!" aria-label="Search">
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
            echo ' &nbsp; &nbsp; ' . $t_submenu;
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
<div class="mt-n5 mb-4 alert <?php echo $mdbltcolor;?> alert-dismissible fade show d-print-none" role="alert"><?php echo $t_banner_message_all_pages; ?>
  <button type="button" class="close" data-dismiss="alert" aria-label="Close"> <span aria-hidden="true">&times;</span> </button>
</div>
<?php } ?>
<!-- End Banner Message -->

<?php
  $t_c = $mdbmedcolor;
#  if ($t_class === 'prime') $t_c = 'dusty-grass-gradient';
#  if ($t_class === 'prp') $t_c = 'tempting-azure-gradient';
#  if ($t_class === 'composite') $t_c = 'ripe-malinka-gradient';
#  if ($t_class === 'unit') $t_c = 'morpheus-den-gradient';
#  if ($t_class === 'unknown') $t_c = 'juicy-peach-gradient';
?>

<h1 title="<?php echo $t_class;?>" class="mt-n4 mb-4 rounded <?php echo $t_c;?> p-2 pl-4 z-depth-2"><?php
    echo (isset($t_title) ? $t_title : '$t_title defines the title')
?> </h1>

<?php # Add a validation test link to the test pages (only)
if (file_exists("/var/www/html/TESTSITE")) {
    echo "<div class=\"alert alert-dismissible fade show d-print-none deep-purple lighten-5\" role=\"alert\">\nVariables noted (if any):\n";
    if (!empty($t_title)) {
        echo '  <div class="alert alert-success" role="alert">' . "\$t_title is \"$t_title\"</div>\n";
    }
    if (!empty($t_title2)) {
        echo '  <div class="alert alert-success" role="alert">' . "\$t_title2 is \"$t_title2\" (use if the browser title must differ from the page title, otherwise omit)</div>\n";
    }
    if (!empty($t_subtitle)) {
        echo '  <div class="alert alert-warning" role="alert">' . "\$t_subtitle is \"$t_subtitle\". Deprecated, shown poorly peraps.</div>\n";
    }
    if (!empty($t_add_menu)) {
        echo '  <div class="alert alert-danger" role="alert">' . "\$t_add_menu is \"$t_add_menu\"" . ' Move to $t_submenu?</div>' . "\n";
    }
    if (!empty($t_submenu)) {
        echo '  <div class="alert alert-success" role="alert">' . "\$t_submenu is \"$t_submenu\"</div>\n";
    }
    if (!empty($t_adjust_path)) {
        echo '  <div class="alert alert-warning" role="alert">' . "\$t_adjust_path is \"$t_adjust_path\" (partially implemented)</div>\n";
    }
    if (!empty($t_allow_cache)) {
        echo '  <div class="alert alert-warning" role="alert">' . "\$t_allow_cache is \"$t_allow_cache\". (The 'no' case is untested, others do nothing.) </div>\n";
    }
    if (!empty($add2_keywords)) {
        echo '  <div class="alert alert-danger" role="alert">$add2_keywords is "' . htmlentities($add2_keywords) . '"  Use $t_meta[\'add_keywords\'] instead. </div>' . "\n";
    }
    if (!empty($t_meta[ 'add_keywords' ])) {
        echo '  <div class="alert alert-success" role="alert">$t_meta[\'add_keywords\'] is "' . $t_meta[ 'add_keywords' ] . '"</div>' . "\n";
    }
    if (!empty($t_meta[ 'add_lines' ])) {
        echo '  <div class="alert alert-success" role="alert">$t_meta[\'add_lines\'] is "' . htmlentities($t_meta[ 'add_lines' ]) . '"</div>' . "\n";
    }
    if (!empty($t_meta[ 'description' ])) {
        echo '  <div class="alert alert-success" role="alert">' . "\$t_meta['description'] is \"$t_meta[description]\"</div>\n";
    }
    if (!empty($t_class)) {
        echo '<div class="alert alert-success" role="alert">' . "\$t_class is \"$t_class\"" . '</div>';
    }
    if (!empty($t_short)) {
        echo '<div class="alert alert-danger" role="alert">' . "\$t_short is \"$t_short\"" . '</div>';
    }
    if (!empty($short)) {
        echo '<div class="alert alert-success" role="alert">' . "\$short is \"$short\"" . '</div>';
    }
    if (!empty($number_id)) {
        echo '<div class="alert alert-success" role="alert">' . "\$nunber_id is \"$number_id\"" . '</div>';
    }
    echo "\n<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\"> <span aria-hidden=\"true\">&times;</span> </button>\n</div>\n\n";
}
?>

<?php if (empty($t_text)) { ?>
<p>Put the text for the page in the variable <b>$t_text</b> (it will replace this!)
  Other variables you can set include:</p>
<blockquote>
  <dl>
    <dt>$t_title</dt>
      <dd>Used as page title and title in the head</dd>
    <dt>$t_submenu</dt>
      <dd>Added to the end of the breadcrumbs menu</dd>
    <dt>$t_subtitle</dt>
      <dd>Secondary title on this collection</dd>
    <dt><span class="text-danger">Curently untested</span> $t_adjust_path</dt>
      <dd><span class="text-danger">Curently unused????</span> The template is written as if page will be in the root directory (for collection), if pages are not, then need to adjust path by adding something l>
    <dt>$t_meta['add_lines']</dt>
      <dd> Adds to the header lines (e.g., a base meta tag)</dd>
    <dt>$t_meta['description']</dt>
      <dd>Replaces default page description.</dd>
    <dt>$t_meta['add_keywords']</dt>
      <dd>Prepends a comma delimeted words list to the keywords (no trailing , needed).</dd>
    <dt>$t_allow_cache
      <dd>Set to 'no' to avoid sending the headers which try to stop the browser from cache'ing pages (they are not sent by default).
    <dt>$t_class <dd>composite, prime, unit, unknown or other.
  </dl>
</blockquote>
    <?php
} else {
    echo $t_text;
}
?>

<!-- For print copies only -->
<div class="d-none d-print-block mt-5 pl-3">Printed from the PrimePages &lt;t5k.org&gt; &copy; G. L. Honaker and Reginald McLean</div>
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
          <li> <a href="/curios/includes/mail.php">Contact Us</a></li>
        </ul>
      </div>
      <!-- Grid column -->

    </div>
    <!-- Grid row -->

  </div>
  <!-- Footer Links -->

  <div class="footer-copyright text-center py-3"><!-- Library Item? "/Library/copyright.lbi" -->
    <a href="/curios/includes/copyright.php" class="none">Copyright &copy; <?php echo date("Y"); ?></a> &nbsp; <a href="https://t5k.org/">PrimePages</a>.&nbsp; (<a href="/notes/privacy.html">Privacy notice</a>)<!-- Library Item? --></div>
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
