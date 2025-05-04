<?php

$t_adjust_path = "primes/";
$t_submenu =  "Donate";

$t_title = "Donate";

$t_text = <<< HERE
      <p>While T5K isn't a huge operation, there are quite a few small costs that add up. Any and all donations are appreciated - thank you for helping!</p>
      <p>You can use <a href="https://donate.stripe.com/4gw4jD4am9eH9ZmaEE">the Stripe link here</a> or the PayPal button below to donate. If you'd rather donate another way, <a href="primes/mail.php">let me know</a> and I'll consider adding it as an option.</a>
<form action="https://www.paypal.com/donate" method="post" target="_top"><input type="hidden" name="hosted_button_id" value="7LF6EVMPCLEXS" /><input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" title="PayPal - The safer, easier way to pay online!" alt="Donate with PayPal button" style="margin:auto; display:block"/><img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1" /></form>
<br><p>T5K averages somewhere around $40/month in expenses. Note that this can vary significantly depending on the number of primes submitted that month. Additionally, I would love to implement some additional features someday like proving more PRPs, verifying more comments (like Fermat divisors), and more. These features would require more computing power/money (at least short term, to catch up with old submissions).</p>
<p>As of 
HERE;
$t_text .= date("d F Y", filemtime("/var/www/html/balance"));
$t_text .= ", T5K's balance is $";
$t_text .= trim(file_get_contents("/var/www/html/balance"));
$t_text .= ". </p><p>Once again, thanks for your support!</p>";

include("primes/template.php");
