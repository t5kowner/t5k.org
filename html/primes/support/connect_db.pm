package connect_db;

# Connects to the primes database.  Functions:
#
# connect([{database=>$database, user=>$user, password=>$password}])
# connect_admin([{database=>$database}])
#	Connects and returns a database handle (uses old one if previously connected)
#	Note: we can not switch between bases
#
# UpdateRow({where=>$where, set=>$set, table=>$table})
#	Performs "UPDATE $table SET $set WHERE $where"
#	Run $dbh->quote( ) on the strings first for each of these!!
#
# InsertRow({set=>$set, table=>$table})
#	Performs "REPLACE $table SET $set" and returns 0 if fails, 1 if the row
#	was inserted, 2 if the row was already there (deletes old, inserts new)
#
# ReplaceRow({set=>$set, table=>$table})
#	Performs "REPLACE $table SET $set" and returns 0 if fails, 1 if the row
#	was inserted, 2 if the row was already there (deletes old, inserts new)
#
# GetRow({where=>$where, table=>$table, columns=>$columns})
# 	(table defaults to 'prime', columns to 'id'.)
#	Performs "SELECT $fields FROM $table WHERE $where LIMIT 1" and returns either
#	the one field, or an array referrence of the fields.
#
# GetColumn({where=>$where, table=>$table, column=>$column, limit=>$limit})
# 	(table defaults to 'prime', column to 'id'.)
#	Performs "SELECT $field FROM $table WHERE $where LIMIT $limit" and returns
#	an array referrence of the entries.
#
# log_action($db, $who, $what, $where, $notes, $email)
#	See below.  Example:
#	&connect_db::log_action($dbh,'SYSTEM','created',"codes.code=$item",$notes);

use DBI;  			# Database handler
use open ':std', ':encoding(UTF-8)';
use lib '/var/www/library/constants/';
use environment;

$dbh = undef;	# Set if we have already made a connection.

sub connect {
  my $s = shift || {};
  my $database =  $$s{'database'} || 'primes';
  my $user     =  $$s{'user'}     || 'primes_';#admin';
  my $password =  $$s{'password'} || environment::T5K_DB_PRIMES_PASSWORD;
#  unless ($dbh and $dbh->ping) { ### <--- This will be wrong if the database is changed or not connected.
#    # The 'unless' looks to see if the handle exist, and then pings it to see that it has not timed out
#    # could clone the old hande if it has instead of just reconnecting.

    my $tries = 2;
    until ($tries == 0 or $dbh = DBI->connect("DBI:mysql:$database:localhost",$user,$password,{mysql_enable_utf8 => 1,RaiseError => 1}) ) {
       $tries--;
       warn "Can't connect: $DBI::errstr. Pausing before retrying.\n"; 
       sleep 30;
    }

#  }
  return $dbh;
}

sub connect_admin {
  my $s = shift || {};
  $$s{'user'} = 'primes_admin';
  $$s{'password'} = environment::T5K_DB_PRIMES_ADMIN_PASSWORD;
  return &connect_db::connect($s);
}

sub UpdateRow {
  my $s = shift || {};
  my $where = $$s{'where'} or die "UpdateRow must be passed a 'where' string.\n";
  my $set = $$s{'set'} or die "UpdateRow must be passed a 'set' string.\n";
  my $table = $$s{'table'} || 'prime';
  $dbh = &connect();
  my $query = "UPDATE $table\n\tSET $set\n\tWHERE $where";
  #  print "query: $query\n";
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

# GetRow({where=>$where, table=>$table, columns=>$columns})
# Gets indicated columns from the first such row.
# Table defaults to 'prime', $columns to 'id'.

sub GetRow {
  my $s = shift || {};
  my $where = $$s{'where'} or die "GetRow must be passed a 'where' string.\n";
  my $table = $$s{'table'} || 'prime';
  my $columns = $$s{'columns'} || 'id';
  $dbh = &connect();
  my $query = "SELECT $columns FROM $table WHERE $where";
  # print "query: $query\n";
  my $sth = $dbh->prepare($query);
  $sth->execute();  # The time is eaten up by this command!
  my $p = $sth->fetchrow_hashref;
  $sth->finish();

  if ($columns =~ /,/o) {
    return $p;	# Return array reference
  } else {
    # print "Returning $$p{$columns}\n";
    return $$p{$columns};  # Return the one entry in that column
  }
}

# GetColumn({where=>$where, table=>$table, column=>$column, limit=>$limit})
# Gets indicated column (singular) from the matching rows.
# Table defaults to 'prime', $columns to 'id'.

sub GetColumn {
  my $s = shift || {};
  my $where = $$s{'where'} || '';
  my $table = $$s{'table'} || 'prime';
  my $column = $$s{'column'} || 'id';
  $dbh = &connect();
  my $query = "SELECT $column FROM $table ".($where ? "WHERE $where" : '');
  # print "query: $query\n";
  my $sth = $dbh->prepare($query);
  $sth->execute();  # The time is eaten up by this command!

  my @list = ();
  while ($p = $sth->fetchrow_hashref) {
    push(@list, $$p{$column});
  }
  $sth->finish();

  return @list;	# Return array
}

# Make sure this routine is a port of primes/bin/log.inc
#
# This routine is for logging changes to the database.  It has one routine:
#
#      &log_action($db, $who, $what, $where, $notes)
#
# where these variables are as follows
#
#      $who	(person.id) database id for the individual (log.person_id) or
#		'SYSTEM' (for programs) or NULL
#      $what	what type of change or error: that is, one of the strings
#
#			modified|created|deleted|mailed|other|warning|error
#
#		errors need editor attention, warnings do not (duplicate submissions,
#		password errors...) (log.what)
#      $where	where the change was made, e.g., "prime.id=62534"  (log.where)
#		I hope to stick to this table.row=value format
#      $notes	a short text comment ("submitted a new prime" ...) (log.notes)
#      $email   If defined and not empty, email to the administrator
#
#  the routine will try to automatically fill in these fields:
#
#      when_	current timestamp (log.when_)
#      from_	IP address via browser type  (or if it is the system, a program name proceeded by '#')
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
      $who = 254; # My testing account (username=system) has id=254;
    } else {
      # Disallowing NULL until later (need left join in log.php && $who != 'NULL')
      $notes = "Error: who ($who) is not a numerical person.id. $notes";
      $who = 254;
    }
  }
  # 'modified','created','deleted','mailed','other','warning','error'
  if ($what !~ /^(modified|created|deleted|mailed|other|warning|error)$/o) {
    $notes = "Error: what='$what' was not a standard form, using 'error'. $notes";
    $what = 'error';
  }
  $where = $dbh->quote($where);
  $notes = $dbh->quote($notes);

  # For diagnostic use later we add:

  # $<	The real uid of this process
  # $>	The effective uid of this process.
  # $0	Contains the name of
  # $^X	The name that Perl itself was executed as,

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

sub mail {
  $email   = shift || 'admin@t5k.org';
  $subject = shift || 'No subject line defined in conect_db::mail.';
  $message = shift || 'No message defined in conect_db::mail.';

  unless(open (MAIL, "|/usr/sbin/sendmail -t")) {
    print "error.\n";
    warn "Error starting sendmail: $!";
  } else{
    print MAIL "From: PrimePages <admin\@t5k.org>\n";
    print MAIL "To: ".$email."\n";
    print MAIL "Subject: $subject.\n\n";
    print MAIL $message;
    close(MAIL) || warn "Error closing mail: $!";
    # print "sent $message to $email with subject $subject";
  }
}


1;
