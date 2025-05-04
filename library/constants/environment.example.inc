<?php

const T5K_VERIFY_PASSWORDS = [
    "enter password for verify.php",
    "multiple allowed"
];

const T5K_DB_PRIMES_PASSWORD = "SQL password for primes_ user";
const T5K_DB_PRIMES_ADMIN_PASSWORD = "SQL password for primes_admin user";

const T5K_RECAPTCHA_SECRET = "ReCaptcha secret key";

#If an exception isn't listed here, the email address used will be admin@t5k.org
#The only exception used currently is "curios content editor",
#though "curios technical editor" would also work with no changes outside this file
const T5K_EMAIL_EXCEPTIONS = [
    'use of email exception' => 'email@example.com'
];
