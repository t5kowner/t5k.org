package connect_db;

# Connects to the database.  Functions:
#
# connect
#	Connects and returns a database handle (uses old one if previously connected)
#	Example:
#		use connect_db;
#		my $dbh = &connect_db::connect();
#		$opt_s or print "Connected to database.\n";
#
# UpdateRow({where=>$where, set=>$set, table=>$table})
#	Performs "UPDATE $table SET $set WHERE $where"
#
# InsertRow({set=>$set, table=>$table})
#	Performs "REPLACE $table SET $set" and returns 0 if fails, 1 if the row
#	was inserted, 2 if the row was already there (deletes old, inserts new)
#
# ReplaceRow({set=>$set, table=>$table})
#	Performs "REPLACE $table SET $set" and returns 0 if fails, 1 if the row
#	was inserted, 2 if the row was already there (deletes old, inserts new)
#
# GetRow({where=>$where, [table=>$table, columns=>$columns]})
#	Performs "SELECT $columns FROM $table WHERE $where" and returns either
#	the value of the single column (or undef); or, if $columns is a list,
#	returns either an array reference to the list of columns (or again 
#	undef if there is not match).
# 	Table defaults to 'prime', $columnss to 'id'.
#
# log_action($db, $who, $what, $where, $notes, $email)
#       See below.  Example:
#       &connect_db::log_action($dbh,'SYSTEM','created',"codes.code=$item",$notes);

use DBI;  # Database handler
use lib '/var/www/library/constants/';
use environment;

$dbh = undef;	# Set if we have already made a connection.

sub connect {
  unless ($dbh) {
    $dbh = DBI->connect("DBI:mysql:curios:localhost",'primes_admin',environment::T5K_DB_PRIMES_ADMIN_PASSWORD,{mysql_enable_utf8 => 1, RaiseError => 1});
  }
  return $dbh;
}

sub UpdateRow {
  my $s = shift || {};
  my $where = $$s{'where'} or die "UpdateRow must be passed a 'where' string.\n";
  my $set = $$s{'set'} or die "UpdateRow must be passed a 'set' string.\n";
  my $table = $$s{'table'} || 'prime';
  $dbh = &connect();
  my $query = "UPDATE $table\n\tSET $set\n\tWHERE $where";
  # print "query: $query\n";
  my $sth = $dbh->prepare($query);
  $sth->execute();
}
    
sub InsertRow {
  my $s = shift || {};
  my $set = $$s{'set'} or die "ReplaceRow must be passed a 'set' string.\n";
  my $table = $$s{'table'} || 'prime';
  $dbh = &connect(); 
  my $query = "INSERT $table\n\tSET $set";
  # print "query: $query\n";
  my $sth = $dbh->prepare($query);
  $sth->execute();
  return $sth->rows;
}

sub ReplaceRow {
  my $s = shift || {};
  my $set = $$s{'set'} or die "ReplaceRow must be passed a 'set' string.\n";
  my $table = $$s{'table'} || 'prime';
  $dbh = &connect(); 
  my $query = "REPLACE $table\n\tSET $set";
  # print "query: $query\n";
  my $sth = $dbh->prepare($query);
  $sth->execute();
  return $sth->rows;
}

# GetRow({where=>$where, table=>$table, $fields=>$fields})
# Table defaults to 'prime', $fields to 'id'.
# Returns only the first match unless the field 'key' is set.

sub GetRow {
  my $s = shift || {};
  my $where = $$s{'where'} or die "GetRow must be passed a 'where' string.\n";
  my $table = $$s{'table'} || 'prime';
  my $columns = $$s{'columns'} || 'id';
  my $key = $$s{'key'} || '';

  $dbh = &connect();
  my $query = "SELECT $columns FROM $table WHERE $where";
  # print "query: $query\n";
  my $sth = $dbh->prepare($query);
  $sth->execute();  # The time is eaten up by this command!
  my $p = ($key eq '' ? $sth->fetchrow_hashref : $sth->fetchall_hashref($key));
  $sth->finish();

  if ($key ne '' or $columns =~ /,/o) {
    return $p;  # Return array reference
  } else {
    # print "Returning $$p{$columns}\n";
    return $$p{$columns};  # Return the one column
  }
}

# Make sure this routine is a port of primes/bin/log.inc
#
# This routine is for logging changes to the database.  It has one routine:
#
#      &log_action($db, $who, $what, $where, $notes)
#
# where these variables are as follows
# 
#      $who     (person.id) database id for the individual (log.person_id) or 
#               'SYSTEM' (for programs) or NULL or (temprorarily) a lastname
#      $what    what type of change or error: that is, one of the strings
#
#                       modified|created|deleted|mailed|other|warning|error
#                      
#               errors need editor attention, warnings do not (duplicate submissions, 
#               password errors...) (log.what)
#      $where   where the change was made, e.g., "prime.id=62534"  (log.where)
#               I hope to stick to this table.row=value format
#      $notes   a short text comment ("submitted a new prime" ...) (log.notes)
#      $email   If defined and not empty, email to the administrator
#
#  the routine will try to automatically fill in these fields:
#
#      when_    current timestamp (log.when_)
#      from_    IP address via browser type  (or if it is the system, a program name proceeded by '#')
#
#  MAKE SURE THIS ROUTINE CAN NOT FAIL (OR FAILS SILENTLY!) other than it returns 'FALSE' if fails
#  and the new log entry id if succeeds. 

sub log_action {
  my $dbh   = shift || &connect();
  my $who   = shift || '';
  my $what  = shift || '';
  my $where = shift || '';
  my $notes = shift || '';
  my $email = shift || '';

  if ($who !~ /^\d+$/o) {
    if ($who eq 'SYSTEM') { 
      $who = 144; # My root account (username=system) has id=144;
    } else {
      $who = $dbh->quote($who);
    }
  }
  # 'modified','created','deleted','mailed','other','warning','error'
  if ($what !~ /^(modified|created|deleted|mailed|visibility|rated|other|warning|password|error)$/o) {
    $notes = "Error: what='$what' was not a standard form, using 'error'. $notes";
    $what = 'error';
  }
  $where = $dbh->quote($where);
  $notes = $dbh->quote($notes);

  # For diagnostic use later we add:

  # $<  The real uid of this process
  # $>  The effective uid of this process.
  # $0  Contains the name of 
  # $^X The name that Perl itself was executed as, 

  my $from = $0;
  $from =~ s#/^.*?(\w+)$#$1#;
  $from = $dbh->quote("$0");

  # Should we wmail this error?  Making the where a link.
  if ($email) {
     $where2 = $where;
     if ($where2 =~ /'prime.id=(\d+)'/) {
       $where2 = 'https://t5k.org/primes/page.php?id='.$1.'&edit=1';
     }
     mail('admin@t5k.org',"relint $what","who: $who\n\nwhere: $where2\n\nnotes: $notes\n\nfrom: $from");
  }

  my $query = "INSERT log (person_id, where_, what, notes, from_) 
        VALUES ($who, $where, '$what', $notes, $from)";
  # print "query: $query\n";
  # Do not want to fail!  Don't die even if we do!
  my $sth = $dbh->prepare($query);
  $sth->execute();
  return $sth->{mysql_insertid};
}

1;
