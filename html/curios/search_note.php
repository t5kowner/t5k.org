<?php

#t#  Locked to English only

$t_submenu =  "Search Notes";
# removed to not ause trouble in /glossary and /curios  include('./bin/i18n.inc');

$t_title = 'Advanced Fulltext Search Options';
$t_limit_lang = 'en_US';
$t_text = <<<text

<p class="small">(Slightly altered from an old mysql page.)</p>

<p>MySQL (the database this collection is built on) uses a very simple
parser to split text into words.&nbsp; A ``word'' is any sequence of
characters consisting of letters, digits, <SAMP>`''</SAMP>, and
<SAMP>`_'</SAMP>.&nbsp;  Any ``word'' that is present in the stopword list
or is just too short is ignored.&nbsp;  The default minimum length of
words that will be found by full-text searches is four
characters.&nbsp;  Also common words, those that occur in at least 50%
of the documents, are also ignored (e.g., 'in', 'the', 'prime' ...)</p>


<P>We can also perform boolean full-text searches by using
one or more of the nine special characters:</p>

<blockquote><code>+ - &lt; > ( ) ~ * "</code></blockquote>

<p>When any one of these characters is present, the 50% threshold is
not used.</p>

<blockquote>
<DL>
  <dt><CODE>+</CODE>
  <dd>A leading plus sign indicates that this word <STRONG>must be</STRONG>
    present in every row returned.

  <dt><CODE>-</CODE>
  <dd>A leading minus sign indicates that this word <STRONG>must not
    be</STRONG> present in any row returned.

  <dt><CODE></CODE>
  <dd>By default (when neither plus nor minus is specified) the word is
     optional, but the rows that contain it will be rated higher.&nbsp;  This
     mimics the behavior of <CODE>MATCH() ... AGAINST()</CODE> without the
     <CODE>IN BOOLEAN MODE</CODE> modifier.

  <dt><CODE>&lt; &gt;</CODE>
  <dd>These two operators are used to change a word's contribution to the
    relevance value that is assigned to a row.&nbsp;  The <CODE>&lt;</CODE>
    operator decreases the contribution and the <CODE>&gt;</CODE> operator
     increases it.&nbsp;  See the example below.

  <dt><CODE>( )</CODE>
  <dd>Parentheses are used to group words into subexpressions.

  <dt><CODE>~</CODE>
  <dd>A leading tilde acts as a negation operator, causing the word's
     contribution to the row relevance to be negative.&nbsp;  It's useful for
     marking noise words.&nbsp;  A row that contains such a word will be rated
     lower than others, but will not be excluded altogether, as it would be with
     the <CODE>-</CODE> operator.&nbsp;

  <dt><CODE>*</CODE>
  <dd>An asterisk is the truncation operator.&nbsp;  Unlike the other
    operators, it should be <STRONG>appended</STRONG> to the word, not prepended.

  <dt><CODE>"</CODE>
  <dd>The phrase, that is enclosed in double quotes <CODE>"</CODE>, matches only
    rows that contain this phrase <STRONG>literally, as it was typed</STRONG>.
    </DD>
</DL>
</blockquote>

<p>And here are some examples: </p>

<blockquote><dl>
<dt><CODE>apple banana</CODE>
<dd>find rows that contain at least one of these words.
<dt><CODE>+apple +juice</CODE>
<dd>... both words.
<dt><CODE>+apple MacIntosh</CODE>
<dd>... word ``apple'', but rank it higher if it also contain ``MacIntosh''.
<dt><CODE>+apple -MacIntosh</CODE>
<dd>... word ``apple'' but not ``MacIntosh''.
<dt><CODE>+apple +(&gt;turnover &lt;strudel)</CODE>
<dd>... ``apple'' and ``turnover'', or ``apple'' and ``strudel'' (in any order),
but rank ``apple pie'' higher than ``apple strudel''.
<dt><CODE>apple*</CODE>
<dd>... ``apple'', ``apples'', ``applesauce'', and ``applet''.
<dt><CODE>"some words"</CODE>
<dd>... ``some words of wisdom'', but not ``some noise words''. </DD></DL>
</blockquote>

<h3>MySQL Stopwords</h3>

<p>MySQL by default does not index the following words:</p>

<blockquote>
"a", "a's", "able", "about", "above", "according", "accordingly", "across", "actually", "after",
"afterwords", "again", "against", "ain't", "all", "allow", "allows", "almost", "alone", "along",
"already", "also", "although", "always", "am", "among", "amongst", "an", "and", "another", "any",
"anybody", "anyhow", "anyone", "anything", "anyway", "anyways", "anywhere", "apart", "appear",
"appreciate", "appropriate", "are", "aren't", "around", "as", "aside", "ask", "asking", "associated",
"at", "available", "away", "awfully", "b", "be", "became", "because", "become", "becomes", "becoming",
"been", "before", "beforehand", "behind", "being", "believe", "below", "beside", "besides", "best",
"better", "between", "beyond", "both", "brief", "but", "by", "c", "c'mon", "c's", "came", "can",
"can't", "cannot", "cant", "cause", "causes", "certain", "certainly", "changes", "clearly", "co", "com",
"come", "comes", "concerning", "consequently", "consider", "considering", "contain", "containing",
"contains", "corresponding", "could", "couldn't", "course", "currently", "d", "definitely", "described",
"despite", "did", "didn't", "different", "do", "does", "doesn't", "doing", "don't", "done", "down",
"downwards", "during", "e", "each", "edu", "eg", "eight", "either", "else", "elsewhere", "enough",
"entirely", "especially", "et", "etc", "even", "ever", "every", "everybody", "everyone", "everything", 
"everywhere", "ex", "exactly", "example", "except", "f", "far", "few", "fifth", "first", "five",
"followed", "following", "follows", "for", "former", "formerly", "forth", "four", "from", "further", 
"furthermore", "g", "get", "gets", "getting", "given", "gives", "go", "goes", "going", "gone", "got", 
"gotten", "greetings", "h", "had", "hadn't", "happens", "hardly", "has", "hasn't", "have", "haven't", 
"having", "he", "he's", "hello", "help", "hence", "her", "here", "here's", "hereafter", "hereby",
"herein", "hereupon", "hers", "herself", "hi", "him", "himself", "his", "hither", "hopefully", "how", 
"howbeit", "however", "i", "i'd", "i'll", "i'm", "i've", "ie", "if", "ignored", "immediate", "in",
"inasmuch", "inc", "indeed", "indicate", "indicated", "indicates", "inner", "insofar", "instead",
"into", "inward", "is", "isn't", "it", "it'd", "it'll", "it's", "its", "itself", "j", "just", "k",
"keep", "keeps", "kept", "know", "knows", "known", "l", "last", "lately", "later", "latter", "latterly", 
"least", "less", "lest", "let", "let's", "like", "liked", "likely", "little", "look", "looking",
"looks", "ltd", "m", "mainly", "many", "may", "maybe", "me", "mean", "meanwhile", "merely", "might", 
"more", "moreover", "most", "mostly", "much", "must", "my", "myself", "n", "name", "namely", "nd",
"near", "nearly", "necessary", "need", "needs", "neither", "never", "nevertheless", "new", "next",
"nine", "no", "nobody", "non", "none", "noone", "nor", "normally", "not", "nothing", "novel", "now", 
"nowhere", "o", "obviously", "of", "off", "often", "oh", "ok", "okay", "old", "on", "once", "one",
"ones", "only", "onto", "or", "other", "others", "otherwise", "ought", "our", "ours", "ourselves",
"out", "outside", "over", "overall", "own", "p", "particular", "particularly", "per", "perhaps",
"placed", "please", "plus", "possible", "presumably", "probably", "provides", "q", "que", "quite", "qv", 
"r", "rather", "rd", "re", "really", "reasonably", "regarding", "regardless", "regards", "relatively", 
"respectively", "right", "s", "said", "same", "saw", "say", "saying", "says", "second", "secondly" 
"see", "seeing", "seem", "seemed", "seeming", "seems", "seen", "self", "selves", "sensible", "sent", 
"serious", "seriously", "seven", "several", "shall", "she", "should", "shouldn't", "since", "six", "so", 
"some", "somebody", "somehow", "someone", "something", "sometime", "sometimes", "somewhat", "somewhere", 
"soon", "sorry", "specified", "specify", "specifying", "still", "sub", "such", "sup", "sure", "t", 
"t's", "take", "taken", "tell", "tends", "th", "than", "thank", "thanks", "thanx", "that", "that's", 
"thats", "the", "their", "theirs", "them", "themselves", "then", "thence", "there", "there's",
"thereafter", "thereby", "therefore", "therein", "theres", "thereupon", "these", "they", "they'd", 
"they'll", "they're", "they've", "think", "third", "this", "thorough", "thoroughly", "those", "though", 
"three", "through", "throughout", "thru", "thus", "to", "together", "too", "took", "toward", "towards", 
"tried", "tries", "truly", "try", "trying", "twice", "two", "u", "un", "under", "unfortunately", 
"unless", "unlikely", "until", "unto", "up", "upon", "us", "use", "used", "useful", "uses", "using", 
"usually", "v", "value", "various", "very", "via", "viz", "vs", "w", "want", "wants", "was", "wasn't", 
"way", "we", "we'd", "we'll", "we're", "we've", "welcome", "well", "went", "were", "weren't", "what", 
"what's", "whatever", "when", "whence", "whenever", "where", "where's", "whereafter", "whereas", 
"whereby", "wherein", "whereupon", "wherever", "whether", "which", "while", "whither", "who", "who's", 
"whoever", "whole", "whom", "whose", "why", "will", "willing", "wish", "with", "within", "without", 
"won't", "wonder", "would", "would", "wouldn't", "x", "y", "yes", "yet", "you", "you'd", "you'll", 
"you're", "you've", "your", "yours", "yourself", "yourselves", "z", "zero"
</blockquote>

text;
include('template.php');
