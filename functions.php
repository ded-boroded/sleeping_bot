<?php
$DB_NAME='';
$DB_USER='';
$DB_PASSWORD='';
$BOT_ID='';

$dbh = new PDO('mysql:host=localhost;dbname='.$DB_NAME, $DB_USER, $DB_PASSWORD);
$bot_id=$BOT_ID;


function between($val, $min, $max) {
  return ($val >= $min && $val <= $max);
}

function db_select($query,$array)
{
global $dbh;
try {
$stmt = $dbh->prepare($query);
if ($stmt->execute($array)) {
  while ($row = $stmt->fetch()) {
	$r[]=$row;
}
}
}
catch (PDOException $e) {
return false;
  }
return $r;
}

function db_exec($query,$array)
{
global $dbh;
try {
  $dbh->beginTransaction();
 $stmt_1 = $dbh->prepare($query);
 $stmt_1->execute($array);
 $dbh->commit();
}
catch (PDOException $e) {
return false;
  }
}

function simple_send($bot,$send_array)
{
$Send2tele='https://api.telegram.org/'.$bot.'/sendMessage?';
file_get_contents($Send2tele.http_build_query($send_array));
  
}
?>