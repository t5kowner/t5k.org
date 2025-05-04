package SlurpFile;

# These succeed or die--except the first which will not die if two parameters are passed
#
#  &SlurpFile(FilePath[,NoDie[,Flock]])                 
#    Returns the files as one string.  Will suceed or die unless  the second 
#    parameter is not false.  Will use file locking if the third variable is
#    set.
#
#  &WriteFile(FilePath[,Text[,Chmod[,Flock]]])
#    Writes text at given location with the given chmod value (default 644) and
#    will seek a lock if flock is set.  Succeeds or dies.
#
#  &BackupAndSave(FilePath[,Text[,Extention,[,Flock]]]) 
#    Assume the file exits, renames it to FilePath.Extention (default extention
#    is '.bak') and then saves the new text using &WriteFile. Succeeds or dies.  
#
# Two more for file locking
#
#  &LockFile(FileHandle) 
#     Seeks a non-blocking exclusive lock.  Returns false if fails, else true.
#
#  &UnlockFile(FileHandle) 
#     Should do just that.  (Closing the file should too!)

sub SlurpFile {
  my $file = shift or die "No file specified.";
  my $NoDie = shift || FALSE;
  if (not -e $file) {
    warn "File does not exist: $file.\n";
  } elsif (not -r $file) {
    warn "File not readable: $file.\n";
  } elsif (not open(FILE,$file)) {
    warn "Could not open: $file.\n";
  } else {       # yes--we have succesfully opened a file !
    my $Old = $/;                   # Undefine record separator
    undef $/;                   
    my $out = <FILE>;               # slurp file
    close(FILE);
    $/ = $Old;                      # Redefine record separator
    return $out;
  }
  $NoDie or die "SlurpFile failed!";
  undef;
}

# &WriteFile(FilePath,Text[,Chmod]);  writes or dies

sub WriteFile {
  (my $file = shift) or die "No file name passed to WriteFile.";
  open(FILE,">$file") or die "Could not open $file.";
  (print FILE shift()) or die "Could not extend $file";
  close (FILE) or die "Could not close $file.";
  my $mode = shift() || 0644;
  (chmod $mode, $file) or warn("Failed to chmod $mode $file.");
}

# &BackupAndSave($FileName,$text,$bak)
# Assumes the file exists.  Renames it to FileName.Ext (.bak is the default
# backup extension if none provided).  Creates a new FileName and write the
# data into it.  The new file has the same mode as the old one.  If any step
# fails, it dies.

sub BackupAndSave {
  my $file = shift or die "No file name given";
  my $text = shift || ''; # can write empty file
  my $mode = (stat($file))[2] or die "Could not read file mode for $file";
  my $bak = shift || 'bak';
  rename($file,"$file.$bak") or die "Could not rename $file to $file.$bak";
  &WriteFile($file,$text,$mode);
}

# &LockFile(FileHandle) seeks a non-blocking exclusive lock.  
# Returns false if fails

sub LockFile {
    local(*FILE) = shift;
    $TrysLeft = 5;
                
    # Try to get a lock on the file
    while ($TrysLeft--) {
      # Try to use locking, if it doesn't use locking, the eval would
      # die.  Catch that, and don't use locking.
      # Try to grab the lock with a non-blocking (4) exclusive (2) lock.
      # (4 | 2 = 6)
      my $lockresult = eval("flock(COUNT,6)");
      if ($@) {
        $UseLocking = 0;
        warn('File Locking failed',$@);
        last;
      }   
    
      if (!$lockresult) {
         select(undef,undef,undef,0.1); # Wait for 1/10 sec.
      } else {
         last;           # We have gotten the lock.
      }
    }

    if ($TrysLeft >= 0) {
        # Success!
        return 1;
    } else {
        return 0;   
    }
}

# &UnlockFile(FileHandle) should do just that
             
sub UnlockFile {
  local(*FILE) = shift;
  flock(FILE,8);
}


1;
