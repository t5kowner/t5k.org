<?php

$t_meta['description'] = "Frequently asked questions at the Prime Curios!";
$t_title = "A Few Answers";
$t_meta['add_keywords'] = "definitions, primes, terms, largest known primes";

$t_text = <<<HERE

<ul>
  <li>Questions about the page on submitters (ByOne.php)<br><br>
    <ol>
      <li>
        <p><a name="unknown" id="unknown"></a>Why are some
        e-mail addresses labeled unknown?</p>
        <p>
          If we know an e-mail address we will put it in
          the database.&nbsp; 'unknown' means just that, we
          do not know it.&nbsp; If we know it, it will
          either show or be marked private.
        </p>
      </li>
      <li>
        <p><a name="private" id="private"></a>Why are some
        e-mail addresses labeled private?</p>
        <p>
          We will never publish any individual's e-mail
          address without their permission.&nbsp; Placing
          an e-mail address on the web will greatly
          increase the amount of junk mail and viruses sent
          to that address.&nbsp; If your e-mail is marked
          private, and you would like it to show (so that
          folks can contact you), just let the editors
          know.&nbsp; And if you later change your mind and
          want it private again, again let the editors
          know!
        </p>
      </li>
      <li>
        <p><a name="unedited_submission" id=
        "unedited_submission"></a>What is an 'unedited submission' and why are they counted?</p>
        <p>
          An unedited submission is one that has not yet been
          approved (or deleted) by an editor.&nbsp; No entries are
          visible until they have been approved.&nbsp; Note
          the submission <a href=
          "/curios/includes/guidelines.php">guidelines</a>
	  state that no more than seven curios may be submitted in 
	  a seven day period.&nbsp;  Any additional curios will be
	  deleted without an editor ever seeing them and possibly
	  without notifying the submitter. 
        </p>
      </li>
    </ol>
  </li>
</ul>

HERE;


$t_adjust_path = '../';
include("../template.php");
