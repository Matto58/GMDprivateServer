<?php
require "../incl/lib/Captcha.php";
require "../incl/lib/connection.php";
require_once "../incl/lib/exploitPatch.php";
require "../config/security.php";
require "../config/mail.php";
if(!$preactivateAccounts) {
	if($mailEnabled) {
		if(isset($_GET["mail"])) {
			$mail = ExploitPatch::remove(explode('/', $_GET["mail"])[count(explode('/', $_GET["mail"]))-1]);
			$check = $db->prepare("SELECT accountID FROM accounts WHERE mail = :mail");
			$check->execute([':mail' => $mail]);
			$check = $check->fetch();
			if(empty($check)) {
				die("Nothing found!");
			} else {
				$query = $db->prepare("UPDATE accounts SET isActive = '1', mail = 'activated' WHERE accountID = :acc");
				$query->execute([':acc' => $check["accountID"]]);
				die("Account was successfully activated!");
			}
		}
	}
}