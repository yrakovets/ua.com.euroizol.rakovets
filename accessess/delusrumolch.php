<?php
/**
 * @author Rakovets Yurii <rakovets@mizol.com>
 * @date 15.10.2018
 * @ticket{21809}
 * @brief для удаления без проверки прав
 * @details Проверяю также пользователя и тип значения.
 * @params EDITID int
 * @params USERID int
 * @params VARID int/string тип значения
 */


include_once("../includes/propis.php");

$EDITID = $_GET["EDITID"];
$USERID = $_GET["USERID"];
$VARID = $_GET["VARID"];

if(strlen($EDITID) === 0)
{
    echo $EDITID."<br>";
    echo "Error: EDITID not defined";
    return;
}
if(@strlen($USERID) == 0)
{
    echo "Error: USERID not defined";
    return;
}
if(@strlen($VARID) == 0)
{
    echo "Error: USERID not defined";
    return;
}

//echo $EDITID."_".$USERID."_".$VARID."<br>";

if(!is_numeric($VARID) )
{
    $VARID = constant($VARID);
}

include_once __DIR__."/../classes/tumolch.php";

$conn = connect_to_db() or die ("Не удалось подключиться к базе данных");

$oper = new TUmolch();
$oper->conn = $conn;
$oper->id = $EDITID;

if (!$oper->ReadUser())
{
    echo "Read error";
    return;
}

if($oper->userid != $USERID)
{
    echo "Wrong user";
    return;
}

if($oper->varid != $VARID)
{
    echo "Wrong var type ".$oper->varid."_".$VARID;
    return;
}


$oper->DeleteUser();

