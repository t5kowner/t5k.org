package defaults;

# key defaults for the list of largest known primes

# Maximum relative error expected in the log parser (full digit should be more accurate)
$eps = 0.000000001;

# What entry in the table (person.id) corresponds to the anonymous entry?  In
# reweight this person automatically is on the bottom of the ranked lists to
# keep the entry from climbing up too high and messing up the top 20.
$anonymous_person_id = 433;

# What entry in the table (person.id) corresponds to the System entry?  In
# routines like the log some entry must be cited for system changes to the database.
# But this entry must not be removed for lack of primes (by remove_unused_persons).
$system_account_id = 254;

# print "defaults loaded\n";

1;
