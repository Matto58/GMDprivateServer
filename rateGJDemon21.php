<?php
//error_reporting(0);
include "connection.php";
require_once "incl/GJPCheck.php";
$gjp = htmlspecialchars($_POST["gjp"],ENT_QUOTES);
$rating = htmlspecialchars($_POST["rating"],ENT_QUOTES);
$feature = htmlspecialchars($_POST["feature"],ENT_QUOTES);
$levelID = htmlspecialchars($_POST["levelID"],ENT_QUOTES);
$id = htmlspecialchars($_POST["accountID"],ENT_QUOTES);
$query2 = $db->prepare("SELECT * FROM users WHERE extID = :id");
$query2->execute([':id' => $id]);
$result = $query2->fetchAll();

$query = $db->prepare("SELECT * FROM accounts WHERE accountID = :id");
if($id != "" AND $gjp != ""){
	$GJPCheck = new GJPCheck();
	$gjpresult = $GJPCheck->check($gjp,$id);
	if($gjpresult == 1){
		$query->execute([':id' => $id]);
		$result = $query->fetchAll();
		$accinfo = $result[0];
		if($accinfo["isAdmin"]==1){
			$auto = 0;
			$demon = 0;
			switch($rating){
				case 1:
					$dmn = 3;
					break;
				case 2:
					$dmn = 4;
					break;
				case 3:
					$dmn = 0;
					break;
				case 4:
					$dmn = 5;
					break;
				case 5:
					$dmn = 6;
					break;
			}
			$query = $db->prepare("SELECT * FROM levels WHERE levelID = :levelID");
			$query->execute([':levelID' => $levelID]);
			$result = $query->fetchAll();
			$lvlinfo = $result[0];
			$query = "UPDATE levels SET starDemonDiff=:demon WHERE levelID=:levelID";
			$query = $db->prepare($query);	
			$query->execute([':demon' => $dmn, ':levelID'=>$levelID]);
			echo $levelID;
		}else{
			echo -1;
		}
	}else{echo -1;}
}else{echo -1;}
?>