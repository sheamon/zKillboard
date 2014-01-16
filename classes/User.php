<?php
/* zKillboard
 * Copyright (C) 2012-2013 EVE-KILL Team and EVSCO.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
class User
{
	public static function setLogin($username, $password, $autoLogin)
	{
		global $cookie_name, $cookie_time, $cookie_ssl, $baseAddr, $app;
		$hash = Password::genPassword($password);
		if ($autoLogin) {
			$hash = $username."/".hash("sha256", $username.$hash.time());
			$validTill = date("Y-m-d H:i:s", time() + $cookie_time);
			$userID = Db::queryField("SELECT id FROM zz_users WHERE username = :username", "id", array(":username" => $username), 0);
			$userAgent = $_SERVER["HTTP_USER_AGENT"];
			$ip = IP::get();
			Db::execute("INSERT INTO zz_users_sessions (userID, sessionHash, validTill, userAgent, ip) VALUES (:userID, :sessionHash, :validTill, :userAgent, :ip)", 
				array(":userID" => $userID, ":sessionHash" => $hash, ":validTill" => $validTill, ":userAgent" => $userAgent, ":ip" => $ip));
			$app->setEncryptedCookie($cookie_name, $hash, time() + $cookie_time, "/", $baseAddr, $cookie_ssl, true);
		}
		$_SESSION["loggedin"] = $username;
		return true;
	}

	public static function checkLogin($username, $password)
	{
		$p = Db::query("SELECT username, password FROM zz_users WHERE username = :username", array(":username" => $username), 0);
		if(!empty($p[0]))
		{
			$user = $p[0]["username"];
			$pw = $p[0]["password"];

			if(Password::checkPassword($password, $pw))
				return true;
			return false;
		}
		return false;
	}

	public static function checkLoginHashed($userID)
	{
		return Db::query("SELECT sessionHash FROM zz_users_sessions WHERE userID = :userID AND now() < validTill", array(":userID" => $userID), 0);
	}

	public static function autoLogin()
	{
		global $cookie_name, $cookie_time, $app;
		$sessionCookie = $app->getEncryptedCookie($cookie_name, false);

		if (!empty($sessionCookie)) {
			$cookie = explode("/", $sessionCookie);
			$username = $cookie[0];
			$cookieHash = $cookie[1];
			$userID = Db::queryField("SELECT id FROM zz_users WHERE username = :username", "id", array(":username" => $username), 0);
			$hashes = self::checkLoginHashed($userID, $cookieHash);
			foreach($hashes as $hash)
			{
				$hash = $hash["sessionHash"];
				if ($sessionCookie == $hash) {
					$_SESSION["loggedin"] = $username;
					return true;
				}
			}
			return false;
		}
		return false;
	}

	public static function isLoggedIn()
	{
		return isset($_SESSION["loggedin"]);
	}

	public static function getUserInfo()
	{
		if (isset($_SESSION["loggedin"])) {
			$id = Db::query("SELECT id, username, email, dateCreated, admin, moderator FROM zz_users WHERE username = :username", array(":username" => $_SESSION["loggedin"]), 1);
			return @array("id" => $id[0]["id"], "username" => $id[0]["username"], "admin" => $id[0]["admin"], "moderator" => $id[0]["moderator"], "email" => $id[0]["email"], "dateCreated" => $id[0]["dateCreated"]);
		}
		return null;
	}

	public static function getUserID()
	{
		if (isset($_SESSION["loggedin"])) {
			$id = Db::queryField("SELECT id FROM zz_users WHERE username = :username", "id", array(":username" => $_SESSION["loggedin"]), 1);
			return $id;
		}
		return null;
	}

	public static function isModerator()
	{
		$info = self::getUserInfo();
		return $info["moderator"] == 1;
	}

	public static function isAdmin()
	{
		$info = self::getUserInfo();
		return $info["admin"] == 1;
	}

	public static function getUsername($userID)
	{
		return Db::queryField("SELECT username FROM zz_users WHERE userID = :userID", array(":userID" => $userID));
	}

	public static function getSessions($userID)
	{
		return Db::query("SELECT sessionHash, dateCreated, validTill, userAgent, ip FROM zz_users_sessions WHERE userID = :userID", array(":userID" => $userID), 0);
	}

	public static function deleteSession($userID, $sessionHash)
	{
		Db::execute("DELETE FROM zz_users_sessions WHERE userID = :userID AND sessionHash = :sessionHash", array(":userID" => $userID, ":sessionHash" => $sessionHash));
	}
}
