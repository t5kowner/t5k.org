Contributing
============
Thanks for your interest in contributing to T5K! We have some guidelines in place to make sure your contributions go smoothly.

Deciding What to Work On
------------------------
Generally speaking, any issue that has had appropriate tags added to it by an admin is something we'd accept a fix for. Typo fixes will also be accepted without an associated issue.

If you want to work on something that doesn't have an issue, you should find or create a GitHub discussion for your idea so that admins (and others) can weigh in on whether we'd even consider accepting it. We *strongly* recommend doing this *before* you start development so you don't waste your time.

Coding Standards
----------------
As a whole, T5K is moving away from Perl and towards PHP. If you want to implement some brand new feature, it should probably be in PHP. Minor fixes/enhancements will still be accepted in Perl scripts, but nothing large.

PHP code is expected to follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards. Using something like [PHP_CodeSniffer](https://github.com/PHPCSStandards/PHP_CodeSniffer/) will make this simple. T5K doesn't currently follow this completely - don't worry about cleaning up existing files if you don't want to. As long as you don't deviate further from the standards, your contribution won't be denied for coding style reasons.

Any function expected to be helpful in at least a few areas across T5K should be implemented as a library function, under the /library directory. These functions are also expected to be documented clearly - see any existing library function for an example.
