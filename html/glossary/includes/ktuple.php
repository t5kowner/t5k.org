<?php

#t# Locked to English only

#include('../bin/basic.inc');
#include_once('../bin/i18n.inc');

$t_meta['description'] = "A simple <i>k</i>-tuple checker and extender--smallest possible
  sets of residues for primes of set size.  When <i>k</i> is 2, these are twin primes.";
$t_title = "<i>k</i>-tuple checker and extender";
$t_limit_lang = 'en_US';
$t_meta['add_keywords'] = "primes, sequences, twin primes, triplet, quadruplet, javascript";
$t_adjust_path = '../';
$t_text = <<<HERE

<SCRIPT>
<!-- // Hide from non-capable browsers 

function array(n){this.length=n}

// How do I parametrise the size of the form?
function getcookie(name) {
  var start = document.cookie.indexOf("^"+name+"=");
  if(start==-1) { return null; }
  var tail = document.cookie.substring(start+2+name.length, document.cookie.length);
  var end = tail.indexOf(";");
  if(end==-1) { end=tail.length; }
  return unescape(tail.substring(0,end));
}
function setcookie(name, val) { 
  document.cookie = "^"+name+"="+escape(val)+";";
}
function boxchange() {
  if(document.consteltest.boxes.value-40>0) {
    document.consteltest.boxes.value=40;
  }
  setcookie('boxes', document.consteltest.boxes.value);
  window.location.href =window.location.href;
}


theparam=getcookie('boxes');
if(!theparam) { theparam=7; setcookie('boxes', theparam); }

var nump=15
var p=new array(nump);
p[0]=2;p[1]=3;p[2]=5;p[3]=7;p[4]=11;p[5]=13;p[6]=17;p[7]=19;
p[8]=23;p[9]=29;p[10]=31;p[11]=37;p[12]=41;p[13]=43;p[14]=47;
function check(fixdup,fixval,repeat)
{
  var a=new array(theparam);
  var src=new array(theparam);
  var count=0;
  var i,j;
  var dunafix=0;
  with(document.consteltest) {
    for(i=0; i<theparam; ++i) {
      var eiv=elements[i].value;
      if(elements[i].value) { 
        if(fixdup) {
          for(j=0; j<i; ++j) {
            if(elements[j].value == eiv) { 
              if(fixdup>0) {
                elements[i].value=''; 
              }
              res.value = "Duplicate "+elements[j].value+ " found";
              return -i; 
            }
          } // end for
        } // end if
        src[count]=i;
        a[count++]= eiv; 
      } // end if
    } // end for
  } // end with
  for(i=0; i<nump && p[i]<=count; ++i) {
    var mask=(1<<p[i])-1;
    var aj;
    for(j=0; mask && j<count; ++j) {
      aj=1*a[j]+(p[i]<<32);
      mask &= ~(1<<aj%p[i]);
    }
    if(!mask) { 
      document.consteltest.res.value = "fails at prime "+p[i]+" (term "+a[j-1]+")"; 
      if(fixval&&(repeat||!dunafix)) {
        a[j-1]=1*a[j-1]+1+(p[i]&1); 
        document.consteltest.elements[src[j-1]].value=a[j-1];
      // either start at p=3 (not 2), or quit now 
        i=0; 
        dunafix=1; 
      } else { 
        return p[i]; 
      }
    } // end if
  } // end for
  document.consteltest.res.value = (i==nump)
	? "No failures to "+p[7]
      : (i<1) ? "Nothing to check" 
               : "primes<= "+p[i-1]+" prove this admissible";
  return 0;
}
function extend() {
  with(document.consteltest) {
    if(ext.value-theparam > 0) { ext.value=theparam; }
    var extto=ext.value;
    var i;
    for(i=0; i<extto; ++i) {
      if(!elements[i].value) {
        elements[i].value = i?1*document.consteltest.elements[i-1].value+2:0;
      }
      var c=check(-1,1,1);
      while(c>0) {
        elements[i].value = 1*elements[i].value+1+(c&1);
        c=check(-1,1,1);
      }
      if(c<0) {
        i=-c-2;
        break; 
      }
    }
  } // end with
}
function clearall() {
  with(document.consteltest) {
    var i;
    for(i=0; i<theparam; ++i) { elements[i].value=''; }
  }
}
 
// stop hiding the code -->
</SCRIPT>

<p>Recall that a sequence of integers <blockquote>(<i>a</i><sub>0</sub>, <i>a</i><sub>1</sub>,
  ... <i>a</i><sub><i>k</i>-1</sub>)</blockquote> is <b>an admissible <i>k</i>-tuple</b> as long 
  as it does not include a complete set of residues for any prime less than or 
  equal to <i>k</i>. When the tuple is admissible, then it is conjectured that its translates 
  be simultaneously prime infinitely often. In other words, there will be infinitely many
  integers <i>n</i> such that all <i>k</i> of the values 
  <blockquote>(<i>n</i> + <i>a</i><sub>0</sub>, <i>n</i> + <i>a</i><sub>1</sub>,
  ... <i>n</i> + <i>a</i><sub><i>k</i>-1</sub>)</blockquote> will be prime. Applying 
  this conjecture to (0, 2) yields the twin prime conjecture.</p>
<p>Type the terms of the <i>k</i>-tuple into the boxes, then click "Check!" 
  to see if it admissible. (The result will appear in the "result" box.) 
  If the result is not admissible, the "Fix" button will try to make 
  it admissible. Another option is to extend the <i>k</i>-tuple to a longer admissible 
  tuple (this uses a greedy algorithm so may not yield the "shortest" 
  possible <i>k</i>-tuples).</p>
<BLOCKQUOTE>
<FORM name=consteltest onsubmit="return false">
  <SCRIPT>
<!--
for(i=0; i<theparam; ++i) {
  document.writeln("<i>a</i><sub>"+i+"</sub>=<INPUT name='count"+i+"' size=3>");
}
// -->
</SCRIPT>
  <BR><INPUT onclick=check(0,0,0) type=button value=Check!>
    result <INPUT size=30 name=res>
    <INPUT onclick=check(1,1,0) type=button value=Fix!> (if not admissible)<br>
    (More boxes? Sure, how many: <input onChange=boxchange() size=3 name=boxes>) <br><br>
    Extend sequence to = 
    <INPUT size=3 name=ext> terms <INPUT onclick=extend() type=button value=Extend!> <INPUT
onclick=clearall() type=button value=Clear!> <BR>
  </FORM></BLOCKQUOTE>
<P><STRONG>Note</STRONG> - this form uses a cookie in order to remember how many 
  boxes you want. Without cookies it will default to 7. </P>
<P>(An insult to real coding by FatPhil, in the public domain with no restrictions 
  on propagation or use.)</P>
HERE;

include("../template.php");
