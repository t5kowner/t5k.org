<?php

//follows recommendations here: https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html
const T5K_PASSWORD_ALGO = PASSWORD_ARGON2ID;
const T5K_PASSWORD_OPTIONS = ['memory_cost' => 19456, 'time_cost' => 2, 'threads' => 1];
