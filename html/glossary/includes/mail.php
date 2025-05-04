<?php

include('../bin/basic.inc');
require_once "../../../library/constants/environment.inc";
$t_submenu =  "mail";

require_once 'HTMLPurifier.auto.php';
# sudo apt-get install -y php-htmlpurifier
$config = HTMLPurifier_Config::createDefault();
# $config->set('HTML.Allowed', 'a[href],b,code,li,ol,ul,em,pre,strong,u,strike,p,br,h2,h3,h4,h5');
$config->set('HTML.TidyLevel', 'heavy');
$config->set('HTML.Doctype', 'HTML 4.01 Transitional');
$purifier = new HTMLPurifier($config);

if (basic_DatabaseName() == 'primes') {
    include_once('../bin/http_auth.inc');
    if (my_is_ip_blocked()) {
        lib_die('You may not submit entries while your IP is blocked.', 'warning');
    }
}

$t_adjust_path = '../';
# $t_limit_lang = 'en_US';
$t_meta['add_lines'] = "<script async src='https://www.google.com/recaptcha/api.js'></script>
<script>
   var RecaptchaOptions = { theme : 'white', tabindex : 2 };
</script>";

# Register and sanitize the variables

$mail_from = (!empty($_POST['mail_from']) ? trim($_POST['mail_from']) : '');
$temp = filter_var($mail_from, FILTER_SANITIZE_EMAIL);
$mail_from = $temp ? $temp : '';

$mail_text = (!empty($_POST['mail_text']) ? trim($_POST['mail_text']) : '');
#$temp = htmlspecialchars($mail_text,ENT_SUBSTITUTE & ENT_NOQUOTES);
$mail_text = $purifier->purify($mail_text);

# E.g., content_editor, technical_editor...
$mail_who  = (!empty($_POST['mail_who'])  ? trim($_POST['mail_who'])  : '');
$mail_who  = preg_replace('/[^\w]/', ' ', $mail_who);

# never returned to client, just compared to preset $send_mail ad $send_anyway
$sendmail  = ( isset($_POST['sendmail'])  ? trim($_POST['sendmail'])  : '');

# Can I track what page they called this page from (problem--this page then calles itself!)
$from_url  = ( isset($_POST['from_url'])  ? trim($_POST['from_url'])  : (isset($_SERVER['HTTP_REFERER']) ? htmlentities($_SERVER['HTTP_REFERER']) : '') );

$t_title = "E-Mail the Editors";

$send_mail   = "Send Mail";
$clear_form  = "Clear Form";
$send_anyway = "Send Anyway";

if ($sendmail) {
    $mail_headers  = "MIME-Version: 1.0\n";
    $mail_headers .= "Content-type: text/html; charset=iso-8859-1\n";
    $mail_headers  = "From: website@t5k.org\n";
    $mail_headers  = "Reply-to: $mail_from\n";

    $mail_subject = "About the " . basic_CollectionName() . " (to the $mail_who editor)";

  # recaptcha 2 code
    $response = $_POST["g-recaptcha-response"];
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = array(
        'secret' => T5K_RECAPTCHA_SECRET,
        'response' => $_POST["g-recaptcha-response"]
    );
    $options = array(
        'http' => array (
            'method' => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query($data)
        )
    );
    $context  = stream_context_create($options);
    $verify = file_get_contents($url, false, $context);
    $captcha_success = json_decode($verify);
  # recaptcha 2 code

    if ($captcha_success->success == false) {
        $t_text = my_error('', "The reCAPTCHA response was not correct.");
    } elseif (empty($mail_text)) {
        $t_text = my_error("You need to give us a message to send!");
    } elseif (
        ! preg_match("/^[^@]+@[^@.]+\.[^@]+$/", $mail_from)
        and $sendmail != $send_anyway
    ) {
        $mail_anyway = 1; # Tells default_text() to add "$send_anyway" button
        $t_text = my_error('', sprintf("Invalid e-mail address: %s", $mail_from) . "<br>\n<br>\n"
        . sprintf(
            "If you want a response, you need to fix this and then press %s below.
	If you want to send your mail without a return address, press %s.",
            "$send_mail",
            "$send_anyway"
        ));
    } else {
        if ($mail_who == 'content') {
            $mail_to = basic_address('content editor');
        } else {
            $mail_to = basic_address('technical editor');
        }

        if ($sendmail == $send_anyway) {
            $mail_headers = ''; # Empty or invalid address with $send_anyway clicked
        }

        $extra = "\r\n\r\n----------------------------------------" .   # \r\n because this will be viewed in Windows
        "\n Referring page:\t\t " . $from_url .
        "\n Working directory:\t " . getcwd() .
        "\n Script location:\t\t /primes/includes/mail.php" .
        "\n Connected from:\t " . $_SERVER["REMOTE_ADDR"] .
        "\n Reply-to:\t\t " . $mail_from .
        "\n Via:\t\t\t $_SERVER[HTTP_USER_AGENT]" .
        "\n Accepts languages:\t $_SERVER[HTTP_ACCEPT_LANGUAGE]" .
        "\n ";

        if (mail($mail_to, $mail_subject, $mail_text . $extra, $mail_headers, '-fadmin@t5k.org')) {
            $t_text = "<p>The following message has been successfully sent to the editors.
	<p>To: $mail_who editor<br>Subject: $mail_subject<br>$mail_headers
	<P>" . nl2br($mail_text) . '</p>';
        } else {
            $t_text = my_error("Sendmail failed!", "This should not happen, use a different
	method to let our technical editor know : admin@t5k.org");
        }
    }
} else {
    $t_text = default_text();
}

include("../template.php");

# Done, now support routines.

### my_error($error,$notes) prints $error big, and $notes normal size.

function my_error($error, $notes = '')
{
    $out = '<div class=error>';
    if (!empty($error)) {
        $out .= "<h3>$error</h3>\n";
    }
    if (!empty($notes)) {
        $out .= "$notes\n";
    }
    return $out . '</div>' . default_text('no intro');
}

### That's it, except for the default_text


function default_text($no_intro = '')
{
    global $mail_text, $mail_from, $mail_who, $mail_anyway, $from_url;
    global $publickey, $send_mail, $clear_form, $send_anyway;

    if (basic_DatabaseName() === 'curios') {
        $intro = <<< HERE

  <p>The Prime Curios! web site currently has two editors with very
  different responsibilities:</p>

  <blockquote>

  <p><b>G. L. Honaker, Jr., the content editor</b>, came up with the idea of this
  collection.&nbsp;  He makes all final decisions on content and writes most of the
  curios.&nbsp; If you would like to praise the collection, suggest a rewording, or have a
  question about the content, then you want to talk to G. L.</p>

  <p><b>Reginald McLean, the technical editor</b>, runs the server; manages the
  database; and
  does the PHP, MySQL, perl and HTML programming.&nbsp;  If an index fails, or you get
  strange error messages, or a search does something odd, then you need to talk to Reginald.</p>
  </blockquote>

  <p>For <a href="http://www.primecurios.com">the Prime Curios! book</a> the
  roles were shared.</p>
HERE;
    } else {
        $intro = "<p class=\"mt-4\">The Largest Known Primes database is only possible because of the help of
	hundreds of individual like you that take the time to make comments, submit suggestions and
	especially to point out errors. Thank-you for taking time to write.</p>\n\n<p class=\"mb-4\">
	Note: If you are pointing out an error, it helps us if you can be very specific about what
	the error is and where it is located.</p>\n";
    }

    $temp1 = "State your e-mail address (so that we may respond).";
    $temp2 = "Follow the instructions below.";

    $form = <<< HERE
<div class="container">
<form action="$_SERVER[PHP_SELF]" method=post>
<table class="md2 mx-auto $GLOBALS[mdbltcolor]" dir=ltr>
HERE;

    if (basic_DatabaseName() === 'curios') {
      # Allow to select who they are mailing to
        $form .= <<< HERE
    <tr>
      <td colspan="2" class="p-3 font-weight-bold">
        Send mail to:
      </td>
    </tr><tr>
      <td colspan="2" class="ml-2">
        &nbsp;  &nbsp; <input type="radio" name="mail_who" value="content" class="p-3 ml-2"
          title="&nbsp; About content matters"

HERE;

        if (empty($mail_who) or $mail_who == 'content') {
            $form .= 'checked';
        }

        $form .= <<< HERE
        >  the content editor: G. L. Honaker, Jr.
      </td>
    </tr>
    <tr>
      <td colspan="2" class="ml-2">
        &nbsp;  &nbsp; <input type="radio" name="mail_who" value="technical" class="p-3 ml-2"
          title="&nbsp; About technical issues"

HERE;

        if (!empty($mail_who) and $mail_who != 'content') {
            $form .= 'checked';
        }

        $form .= <<< HERE
        > the technical editor: Reginald McLean
      </td>
    </tr>
HERE;
    }

    $form .= <<< HERE
    <tr>
      <td colspan="2" class="p-3 font-weight-bold">
        <label for="mail_from">$temp1</label>
        <input type="text" class="form-control rounded-3 mt-2" id="mail_from" name="mail_from" placeholder="" value="$mail_from" tabindex=1>
      </td>
    </tr>
    <tr>
      <td class="p-3 font-weight-bold">
	$temp2
        <div style="padding-left: 1em;" class="g-recaptcha" data-sitekey="6LepqNUjAAAAAH8wq10x4dqy8wmxQzqEpj7wHo5y"></div>
      </td>
      <td class="text-center">
        <input type="submit" class="btn btn-primary p-2 m-1" name="sendmail" value="$send_mail" tabindex=3>
        <br><input class="btn btn-primary p-2 m-1" type="reset" value="$clear_form" tabindex=4>
HERE;

    if ($mail_anyway) {
        $form .= '<br><input  class="btn btn-primary p-2 m-1" type="submit" name="sendmail" value="' . $send_anyway . '">';
    }
    $temp1 = "Type your comments below.";

    $form .= <<< HERE
      </td>
   </tr>
    <tr>
      <td colspan=2 class="p-3 font-weight-bold">
	$temp1
	<br><textarea class="form-control rounded-3 mt-2" rows="13" name="mail_text">$mail_text</textarea>
      </td>
    </tr>
  </table>
<input type="hidden" id="from_url" name="from_url" value="$from_url">

</form>
</div>
HERE;

    if ($no_intro) {
        return $form;
    }
    return $intro . $form;
}
