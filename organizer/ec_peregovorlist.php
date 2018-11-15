<?php

/**
 * @author Rakovets Yurii <rakovets@mizol.com>
 * @date 25.07.2018
 * @ticket{}
 * @brief Список лидов для ес
 */

error_reporting(E_ALL);
ini_set('display_errors', TRUE);

if(@strlen($MODE) == 0) $MODE='TEAM';
if(@strlen($VIEWSIDE) == 0) $VIEWSIDE='MANAGER';
if(@strlen($STATUSES) == 0) $STATUSES='ALL';
if(@strlen($DATE1) == 0) $DATE1 = (new DateTime("first day of this month"))->format("d.m.Y");
if(@strlen($DATE2) == 0) $DATE2 = date("d.m.Y");
if(@strlen($TYPES) == 0) $TYPES = "ALL";


include_once ( "../includes/propis.php" );
set_new_session();
$conn = connect_to_db() or die("Не удалось подключиться к базе данных");
$conn->SetFetchMode(ADODB_FETCH_ASSOC);
$user_id = getdilertip();
$usl='';
if($user_id != C_ADMIN)
{
    if($MODE == 'MINE')
        $sql_usr = " (".getdilertip().")";
    if($MODE == 'TEAM')
        $sql_usr = " (select profkey from userprof where proftip = ".USR_PROF_KOMANDA." and userid = ".getdilertip().") ";
}

$dopfilter = "";
if($STATUSES == 'ACTUAL')
{
    $dopfilter .= " and l.status in (".PERSTATUS_NOTPROCESSED.", ".PERSTATUS_NOTAPPOINTED.")";
}

//R.Y. #22727
if($TYPES != "ALL")
{
    $dopfilter .= " and l.leadtype in (".$$TYPES.")";
}


$res = array();

//R.Y. #23162 fix - формат даты по
$sql="
    select l.ID, l.createdate, pr.FIO, f.SOKRASH, l.TEL, us.a_s1 as PERSTATUSNAZ, np.a_s1 as NAPR,
      man.a_s1 as MAN, ch1.a_s1 as DIR1, ch2.a_s1 as DIR2, uu.a_s1 as USR, lt.a_s1 as LEADTYPENAME
    from leads l, persons pr, firms f, uniprops us, uniprops np, uniprops man, uniprops ch1, uniprops ch2, uniprops uu, uniprops lt
    where  pr.persona = l.persona
    and f.firma = l.firma
    and us.propcnt = l.status
    and np.propcnt = l.napr
    and man.propcnt = l.manager
    and ch1.propcnt = l.chief1
    and ch2.propcnt = l.chief2
    and uu.propcnt = l.userid 
    and lt.propcnt = l.leadtype
    and l.createdate between ".$conn->DBDate(ReversDateStrPar($DATE1))." 
        and ".$conn->DBTimeStamp(ReversDateStrPar($DATE2)." 23:59:59")."
".$dopfilter;

if($user_id != C_ADMIN)
{
    //R.Y. #22698 Добавил режим колл-центра
    if($VIEWSIDE == 'MANAGER')
    {
        $sql .= "
    and ((l.manager in $sql_usr)
      or (l.chief1 in $sql_usr)
      or (l.chief2 in $sql_usr)      
    )";
    } else {
        $sql .= "
        and l.userid in $sql_usr
        ";
    }

}



//die ($sql);
$rs = $conn->Execute($sql);
if ($conn->ErrorNo())
{
    throw new Exception($conn->ErrorMsg() . "\n" . $sql);
}

while (!$rs->EOF)
{
    $r = array();
    foreach ($rs->fields as $naz => $val)
    {
        //if (preg_match('/^(\d{1,})$/', trim($naz)))
          //  continue;
        $r[$naz] = iconv("windows-1251", "UTF-8", $val);
    }
    

    $res[] = $r;
    $rs->MoveNext();
}



$r=array();
$r['rows']=$res;

print ( json_encode($r));

