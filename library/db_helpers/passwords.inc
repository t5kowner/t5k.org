<?php

namespace DB_Helpers;

use PDO;

/**
 * This file contains all needed functions to interact with the password columns in the database.
 * Nothing outside this function should read or write the `person.pass` or `person.temppass` columns (eventually).
 */
require_once __DIR__."/../constants/passwords.inc";


/**
 * Authenticates a user
 * @param $db PDO Database handle
 * @param $userId int User to validate
 * @param $password string Password user entered
 * @return bool True if password is valid for user and all SQL queries succeeded. False otherwise
 */
function verify_password(PDO $db, int $userId, string $password): bool
{
	$query = $db->prepare("SELECT pass,temppass FROM person WHERE id=?");
	if($query === false) return false;
	if (!$query->execute([$userId])) return false;

	if ($row = $query->fetch(PDO::FETCH_ASSOC)) {

		//no error checking on queries/functions below
		//if something goes wrong, but we know the login is OK,
		//everything else is just maintenance that isn't critical

		if (password_verify($password, $row["pass"])) {

			if (password_needs_rehash($row["pass"], T5K_PASSWORD_ALGO, T5K_PASSWORD_OPTIONS)) {
				update_password($db, $userId, $password);
			} else if ($row["temppass"]) {
				//if they had a temp password, get rid of it
				$query = $db->prepare("UPDATE person SET temppass='' WHERE id=?");
				if($query) $query->execute([$userId]);
			}
			return true;
		}
		if (password_verify($password, $row["temppass"])) {
			//They've used their reset password.
			//Hopefully they'll change it now, but regardless,
			//there's no reason to keep their old password around since they don't know it.
			if (password_needs_rehash($row["temppass"], T5K_PASSWORD_ALGO, T5K_PASSWORD_OPTIONS)) {
				update_password($db, $userId, $password);
			} else {
				$query = $db->prepare("UPDATE person SET pass=temppass, temppass='' WHERE id=?");
				if($query) $query->execute([$userId]);
			}
			return true;
		}
	}
	return false;
}

/**
 * Updates the user's stored password. Assumes the user is already authenticated in some way.
 * @param $db PDO Database handle
 * @param $userId int User to update password for
 * @param $password string Password to use for user
 * @param $isTemp bool True if the temporary password should be updated instead of the main one
 * @return bool True if successfully updated, otherwise false
 */
function update_password(PDO $db, int $userId, string $password, bool $isTemp = false): bool
{
	$queryString = "UPDATE person SET ".($isTemp ? "temppass=?" : "pass=?, temppass=''")." WHERE id=?";
	$query = $db->prepare($queryString);
	if(!$query) return false;
	return $query->execute([password_hash($password, T5K_PASSWORD_ALGO, T5K_PASSWORD_OPTIONS), $userId]);
}
