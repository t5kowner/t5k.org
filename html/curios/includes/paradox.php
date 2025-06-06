<?php

$t_meta['description'] = "Discussion of the 'all primes are curios' paradox that 
   is modeled on that by Bertrand Russel";
$t_title = "A Curious Paradox";
$t_meta['add_keywords'] = "definitions, primes, terms, largest known primes";
// $t_subtitle = "";

$t_text = <<<HERE
  <p>Is that truly possible?&nbsp; Can <a
href=../first.php#theorem> every positive integer have an
associated Prime Curio</a>?&nbsp; No.&nbsp; If we list
everything that any one person (perhaps yourself?) found
interesting about primes, it would be a finite list, because we
are finite (we can do a finite number of things at once and
live for a finite period of time).&nbsp; Yes, we can write an
algorithm for assigning "curious" properties to integers, but
using an algorithm would defeat the intent of our definition of
curios.&nbsp; Since the number of humans is finite, it follows
then that the number of curios must also be finite -- but the
set of integers is infinite!</p>

  <p>Our proof that every integer has an associated Prime Curio
is what is called a semantical paradox.&nbsp; Perhaps the
oldest of these is from Epimenides the Cretan who said that:</p>

<blockquote>
  All Cretans were liars and all other statements made by Cretans were certainly
lies.
</blockquote>

  <p>Here is a simpler form of this paradox: suppose I say "I am
lying."&nbsp; If I am lying when I say this, then I am telling the
truth; and if I am telling the truth when I say it, then I am
lying.&nbsp; Epimenides probably lived in the fifth or sixth
century BC and is most likely the philosopher referred to by
the Apostle Paul in Titus 1:12.&nbsp; The stories of
Epimenides' life are so fanciful (e.g., that he lived for
hundreds of years and once slept for 57 years) that little is
truly known about him.</p>

  <p>Our proof is actually a version of a more recent
semantical paradox sent by G. G. Berry of the
Bodleian Library to Bertrand Russell in a letter dated 21
December 1904.&nbsp; (This letter is reprinted in
[Garciadiego92].)&nbsp; You will often find Berry's paradox stated
as "every integer is interesting."&nbsp; If you reread our proof,
you will be able to reconstruct the "proof" of Berry's paradox.</p>

  <p>Berry is also credited with the invention of the greeting
card paradox--he would introduce himself with a card that on
one side said:</p>

<blockquote>
  The statement on the other side of this card is false.
</blockquote>

<p>and on the other said:</p>

<blockquote>
  The statement on the other side of this card is true.
</blockquote>

<p>If you think through these statements, then you will see we
have another version of the Epimenides paradox: if either of
the statements are true, then they must be false as well.</p>

<p>A slightly later version of these paradoxes is Richard's
paradox (1906).&nbsp; His paradox can be stated in the form:</p>

<blockquote>
  Every positive integer can be uniquely defined using at most
100 keystrokes on a typewriter.&nbsp;
</blockquote>

<p>To "prove" this statement you create the type of paradox above
by considering the set S of all integers that cannot be so
described.&nbsp; By the well ordering principal this set has a
least member, and is in fact:</p>

<blockquote>
  The least positive integer that cannot be described in at
most 100 keystrokes.
</blockquote>

<p>But of course we just described it uniquely using less that 100
keystrokes, so to avoid a contradiction the set S must be
empty!&nbsp; Yet, we can also easily disprove this statement because
a typewriter has less that 200 keys (probably closer to 100),
and it follows that 100 keystrokes can describe less than
200<sup>100</sup> integers, so Richard's paradox cannot be true.</p>

<P>Russell discussed each of these paradoxes (and several more)
in his "Mathematical logic as based on the theory of types
[Russell1908] (reprinted in [Heijenoort67]) and concludes that
they do not affect the logical calculus which is incapable of
expressing their character.</p>

<p>Again, these are semantical paradoxes unlike Russell's
famous paradox of the set of all sets that do not contain
themselves.&nbsp; This paradox is often recast as a question
about a barber:</p>

<blockquote>
If there is a town in which the barber shaves (exactly) those
who do not shave themselves, then does the barber shave
himself?
</blockquote>

<p>If he does shave himself, then he does not; and if he does not,
then he does.</p>

<h3>References</h3>

<blockquote> <dl>
  <dt>Garciadiego92
  <dd>A. R. Garciadiego, "Bertrand Russell and the origins of
the set-theoretic 'paradoxes'."  Birh&auml;user-Verlag, Basel, 1992.
<i>xxx</i> + 264 pp. ISBN 3-7643-2669-7
  <dt>Heijenoort67
  <dd>J. van Heijenoort, "From Frege to G&ouml;del, a source book in
mathematical logic, 1879--1931."  Harvard University Press,
Cambridge Mass. 1967. <i>xi</i> + 660 pp.
  <dt>Russell1908
  <dd>B. Russell, Mathematical logic as based on the theory of
types.  Amer. J. Math. 30, 1908, 222-262. Reprinted in B.
Russell, "Logic and Knowledge," London: Allen & Unwin, 1956,
59-102, and in J. van Heijenoort, "From Frege to G&ouml;del,"
Cambridge, Mass.: Harvard University Press, 1967, 152-182.
</dl> </blockquote>
HERE;

$t_adjust_path = '../';
include("../template.php");
