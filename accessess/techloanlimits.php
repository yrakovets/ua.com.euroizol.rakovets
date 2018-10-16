<?php

/**
 * @author Rakovets Yurii <rakovets@mizol.com>
 * @date 03.10.2018
 * @ticket{21809}
 * @brief Лимиты по Тех.кредиту по менеджерам
 */

error_reporting(E_ALL);
ini_set('display_errors', TRUE);

include_once ( __DIR__."/../includes/propis.php" );

set_new_session();
$conn = connect_to_db() or die("Не удалось подключиться к базе данных");

$res = array();

$naprNetSelling = GetFieldFromSQL($conn, "select (listagg(u.propcnt,',') within group (order by u.propcnt)) as naprs from uniprops u where u.pfather =".NAPR_NETSELLING, "0");


$sql="
    select  u.propcnt as userid, u.a_s1 as managername, d.id as valid, d.varint as personallimit, v.varint as generallimit,
    sum(case when (z.tovsumma > z.monsumma) then round((z.tovsumma - z.monsumma)/z.kurssklad,2) else 0 end) as z_debt,
    sum(case when (z.monsumma > 0) then round(z.monsumma/z.kurssklad,2) else 0 end) as z_sell 
from  uniprops u 
    left join dfltusers d
        on d.userid = u.propcnt
            and d.varid = ".TIP_ZNACH_TECHCREDITLIMIT."
    left join zakazy z
        on z.manager = u.propcnt 
            and z.zakaztip = ".ZAKTIP_TECHCREDIT."
            and z.a_r2 in (".$naprNetSelling.")
            and z.tovsumma > z.monsumma
            and z.zakdate >= ".$conn->DBDate(ReversDateStrPar((new DateTime("-1 year"))->format("d.m.Y")))."
            ,
 dfltvars v, userprof p
        
where u.propnum = ".S_SYSUSERS."
and v.id = ".TIP_ZNACH_TECHCREDITLIMIT."
and p.profkey = u.propcnt
and p.proftip = ".USR_PROF_KOMU_ZADANIE."
and p.userid = ".getdilertip()." 
and u.a_d1 >= ".$conn->DBDate(ReversDateStrPar(Date("d.m.Y")))."
and u.a_d2 >= ".$conn->DBDate(ReversDateStrPar(Date("d.m.Y")))."
group by u.propcnt, u.a_s1, d.id, d.varint, v.varint
order by 2
";

$conn->SetFetchMode(ADODB_FETCH_ASSOC);

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
        $r[$naz] = iconv("windows-1251", "UTF-8", $val);
    }


    $res[] = $r;
    $rs->MoveNext();
}



$r=array();
$r['rows']=$res;

print ( json_encode($r));

