<?php
chdir(dirname(__FILE__));
ob_flush();
flush();
if(file_exists("../logs/fixcpslog.txt")){
	$cptime = file_get_contents("../logs/fixcpslog.txt");
	$newtime = time() - 30;
	if($cptime > $newtime){
		$remaintime = time() - $cptime;
		$remaintime = 30 - $remaintime;
		$remainmins = floor($remaintime / 60);
		$remainsecs = $remainmins * 60;
		$remainsecs = $remaintime - $remainsecs;
		exit("-1");
	}
}
file_put_contents("../logs/fixcpslog.txt",time());
if(function_exists("set_time_limit")) set_time_limit(0);
$cplog = "";
$people = array();
require "../../incl/lib/connection.php";
require "../../config/misc.php";
//getting users
$query = $db->prepare("UPDATE users
	LEFT JOIN
	(
		SELECT usersTable.userID, (IFNULL(starredTable.starred, 0) + IFNULL(featuredTable.featured, 0) + (IFNULL(epicTable.epic,0))) as CP FROM (
			SELECT userID FROM users
		) AS usersTable
		LEFT JOIN
		(
			SELECT count(*) as starred, userID FROM levels WHERE starStars != 0 AND isCPShared = 0 ".(!$unlistedCreatorPoints ? "AND unlisted = 0 AND unlisted2 = 0" : "")." GROUP BY(userID) 
		) AS starredTable ON usersTable.userID = starredTable.userID
		LEFT JOIN
		(
			SELECT count(*) as featured, userID FROM levels WHERE starFeatured != 0 AND isCPShared = 0 ".(!$unlistedCreatorPoints ? "AND unlisted = 0 AND unlisted2 = 0" : "")." GROUP BY(userID) 
		) AS featuredTable ON usersTable.userID = featuredTable.userID
		LEFT JOIN
		(
			SELECT SUM(starEpic) as epic, userID FROM levels WHERE starEpic != 0 AND isCPShared = 0 ".(!$unlistedCreatorPoints ? "AND unlisted = 0 AND unlisted2 = 0" : "")." GROUP BY(userID) 
		) AS epicTable ON usersTable.userID = epicTable.userID
	) calculated
	ON users.userID = calculated.userID
	SET users.creatorPoints = IFNULL(calculated.CP, 0)");
$query->execute();
/*
	CP SHARING
*/
if ($unlistedCreatorPoints) $query = $db->prepare("SELECT levelID, userID, starStars, starFeatured, starEpic FROM levels WHERE isCPShared = 1");
else $query = $db->prepare("SELECT levelID, userID, starStars, starFeatured, starEpic FROM levels WHERE isCPShared = 1 AND unlisted = 0 AND unlisted2 = 0");
$query->execute();
$result = $query->fetchAll();
foreach($result as $level){
	$deservedcp = 0;
	if($level["starStars"] != 0){
		$deservedcp++;
	}
	if($level["starFeatured"] != 0){
		$deservedcp++;
	}
	if($level["starEpic"] != 0){
		$deservedcp += $level["starEpic"]; // Epic - 1, Legendary - 2, Mythic - 3
	}
	$query = $db->prepare("SELECT userID FROM cpshares WHERE levelID = :levelID");
	$query->execute([':levelID' => $level["levelID"]]);
	$sharecount = $query->rowCount() + 1;
	$addcp = $deservedcp / $sharecount;
	$shares = $query->fetchAll();
	foreach($shares as &$share){
		$people[$share["userID"]] += $addcp;
	}
	$people[$level["userID"]] += $addcp;
}
/*
	NOW to update GAUNTLETS CP
*/
$query = $db->prepare("SELECT level1,level2,level3,level4,level5 FROM gauntlets");
$query->execute();
$result = $query->fetchAll();
//getting gauntlets
foreach($result as $gauntlet) {
	//getting lvls
	for($x = 1; $x < 6; $x++){
		if ($unlistedCreatorPoints) $query = $db->prepare("SELECT userID, levelID FROM levels WHERE levelID = :levelID");
		else $query = $db->prepare("SELECT userID, levelID FROM levels WHERE levelID = :levelID AND unlisted = 0 AND unlisted2 = 0");
		$query->execute([':levelID' => $gauntlet["level".$x]]);
		$result = $query->fetch();
		//getting users
		if($result["userID"] != ""){
			$cplog .= $result["userID"] . " - +1\r\n";
			$people[$result["userID"]] = ($people[$result["userID"]] ?? 0) + 1;
		}
	}
}
/*
	NOW to update DAILY CP
*/
$query = $db->prepare("SELECT levelID FROM dailyfeatures WHERE timestamp < :time");
$query->execute([':time' => time()]);
$result = $query->fetchAll();
//getting gauntlets
foreach($result as $daily) {
	//getting lvls
	if ($unlistedCreatorPoints) $query = $db->prepare("SELECT userID, levelID FROM levels WHERE levelID = :levelID");
	else $query = $db->prepare("SELECT userID, levelID FROM levels WHERE levelID = :levelID AND unlisted = 0 AND unlisted2 = 0");
	$query->execute([':levelID' => $daily["levelID"]]);
	$result = $query->fetch();
	//getting users
	if($result["userID"] != ""){
		$people[$result["userID"]] = ($people[$result["userID"]] ?? 0) + 1;
		$cplog .= $result["userID"] . " - +1\r\n";
	}
}
/*
	DONE
*/
foreach($people as $user => $cp){
	$query4 = $db->prepare("UPDATE users SET creatorPoints = (creatorpoints + :creatorpoints) WHERE userID=:userID");
	$query4->execute([':userID' => $user, ':creatorpoints' => $cp]);
}
file_put_contents("../logs/cplog.txt",$cplog);
?>