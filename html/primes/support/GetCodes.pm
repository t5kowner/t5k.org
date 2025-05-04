package GetCodes;

# This package sits between the database and the other routines to process the
# codes, especially to allow wild cards in the codes.
# Assumes the database ???

# Exportable routines:
#
# &LoadCodes('omit wildcodes')
#   Loads the codes, adjusts for wild cards, then returns a reference to
#   a hash array (keys = codes, value = comma deliminated list of id's
#   of provers with that code).
#   (Note that the order name appear, set in a query below, should match
#   what we find in buildcodes and the web routine submit2.php.
#
#   For given name (well, prover db id), list codes plus any wild codes

# open database
use DBI;
use connect_db;
my $dbh = &connect_db::connect();

# First get the codes, and wildcodes (codes with wildcards) and combine
# into the hash %Codes.  &LoadCodes fills these two global arrays
# (used in &GetCodesForName(index)):

my %Codes;	# keys = codes, values = comma delimited list of id's
		# except semi-colon separates humans from non
my %WildNames;	# ?????
my %WildCodes;	# keys = codes, values = comma delimited id list

sub LoadCodes {

  # load the codes into global $Codes

  # Important: The sort order here must match the sort order in bios/newcode.inc
  # because it is used in ./buildcodes to create the display strings (and they
  # should not change).  Using type+0 would allow the order to be set in the
  # database for both routines.
  
  my $query = "SELECT id, codes, wild_codes, type, name FROM person
        ORDER BY IF(wild_codes IS NULL,0,1) ASC, type+0 ASC, created DESC, id ASC";

  $sth = $dbh->prepare($query) || die $sth->errstr;
  $sth->execute() || die $sth->errstr;

  while ($x = $sth->fetchrow_hashref) {
    # print "$$x{name} $$x{type} xxx\n";
    if (defined ($$x{'codes'})) {
      foreach my $code (split(/,\s*/o,$$x{'codes'})) {
        my $first_non_human = ($$x{'type'} ne 'person' and
		not defined $NonPersons{$code});
	$NonPersons{$code} = 1 if $first_non_human;
	if (defined $Codes{$code}) {  # Not the first with this code
 	  $Codes{$code} .= ($first_non_human ? ';' : ',').$$x{'id'};
        } else {
	  warn("Non-person $$x{name} in $code; missing humans in this code?\n")
	  	if ($first_non_human);
	  $Codes{$code} = $$x{'id'};
	}
      }
    }
    if (defined ($$x{'wild_codes'}) and $$x{'wild_codes'} ne '') {
      warn("Can not handle human ($$x{name}) wildcodes right now!")
		if ($$x{'type'} eq 'person');
      foreach (split(/,\s*/o,$$x{'wild_codes'})) {
 	$WildCodes{$_} = (defined($WildCodes{$_}) ?
		$WildCodes{$_}.",$$x{id}" : $$x{id});
      }
    }
  }

  return %Codes if shift;

  foreach my $wild_code (keys %WildCodes) {     # compare each wild code
    foreach my $code (keys %Codes) {            # against each code
      next unless $code =~ /$wild_code/;        # match?

      foreach my $wild_item (reverse split(/,/,$WildCodes{$wild_code})) {
        $Codes{$code} = $Codes{$code}.(defined $NonPersons{$code} ? ',' : ';').
		$wild_item;
        # Develop a list of groups which wild_code matched (used in
	# the next functions)

        if (exists $WildNames{$wild_item}) {
          @{$WildNames{$wild_item}} = (@{$WildNames{$wild_item}},$code);
        } else {
          @{$WildNames{$wild_item}} = ($code);
        }
      }

    }
  }
  return %Codes;
}

# For each name, want to list codes plus any wild codes.  Requires that
# &LoadCodes be called first to fill @WildNames.

sub GetCodesForName {
  my $i=shift or return undef;

  # Start list with the list of proof-codes for item #i

  my $list = &db::Value($i,'codes') || '';
  $list .= ',' if $list;

  # Now add any wildcodes

  foreach (@{$WildNames{$i}}) {
    $list .= "$_,";
  }

  # Remove last comma and return

  chop $list;
  return $list;
}

1;
