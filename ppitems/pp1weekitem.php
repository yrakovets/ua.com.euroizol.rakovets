<?php
include_once("../classes/tppitem.php");
include_once("../includes/propis.php");
include_once("../classes/tlist.php");
include_once("../classes/tumolch.php");

//Rakovets Yurii
//11.11.2013
//#2753

set_new_session();
check_user() or die(ShowMes("Нет прав на эту страницу"));

$conn = connect_to_db()
                or die (ShowMes("Не удалось подключиться к базе данных"));

$voper = new TUmolch();
$voper->conn = $conn;

//if (@strlen($JYEAR) == 0) $JYEAR = Date('Y');
//if (@strlen($JQUARTER) == 0) $JQUARTER = floor((Date('m')-1) / 3) + 1;
if ((@strlen($JWEEK) == 0) or ($JWEEK == 0)) die("Не указана неделя!");
if (@strlen($JYEAR) == 0) $JYEAR = GetFieldFromSQL($conn, "select a_f1 from uniprops where propcnt = ".$JWEEK);
if (@strlen($JQUARTER) == 0) $JQUARTER = GetFieldFromSQL($conn, "select a_f2 from uniprops where propcnt = ".$JWEEK);
$JWEEKNAZ = GetFieldFromSQL($conn, "select a_s1 from uniprops where propcnt = $JWEEK");
if ((@strlen($STATID) == 0) or ($STATID == 0)) die("Не указана статья");

if (@strlen($VAL) == 0) $VAL = 0;
if (@strlen($TIPSUM) == 0) $TIPSUM = 0;

$mess = '';

switch ($TIPSUM) 
{
	case 0:
		$sum_field = "summasklad";
		break;
	case 1:
		$sum_field = "summanaz";
		break;
	case 2:
		$sum_field = "summabaz";
		break;
	default:
		die("Нипанаятна насяника, со такое TIPSUM");
}

switch($POSTER)
{
 case "Delete":
   if(!$EDITID) break;
   $oper = new TPPItem;
   $oper->conn = $conn;
   $oper->id = $EDITID;
   $mess = $oper->Delete();
 break;
}

   $s4 = "";       

   if($JYEAR)
   {
    $s4 = " AND (PW.A_F1=$JYEAR) ";
   } 
   
   if ($JQUARTER)
   {
     $s4 .= " AND (PW.A_F2=$JQUARTER) ";
   }
   if ($JWEEK)
   {
     $s4 .= " AND (PW.PROPCNT=$JWEEK) ";
   }
	if ($STATID)
	{
		$s4 .= " AND (ST.PROPCNT=$STATID) ";
	}
   


        $sql = "SELECT PP.ID, PW.A_F1, PW.A_F2, PW.A_F3, ST.A_S1, PP.".$sum_field.", ".$sum_field."fact, V.A_S2 
                FROM EI_STATJURPL PP, UNIPROPS PW, UNIPROPS ST, UNIPROPS V
                WHERE (PP.JWEEK = PW.PROPCNT) 
                  AND (PP.STATID = ST.PROPCNT) 
                  AND (PP.VAL = V.PROPCNT)  
                  AND (ST.CHILDS = 0)
                  $s4";

  $pager = new TList();
  $pager->conn = $conn;

  $pager->caption = 'Список';
  $pager->cols = 'Код, Год, Квартал, Неделя, СТатья, Сумма план, Сумма факт, Валюта ';
  $pager->sql = $sql;

	$pager->AddKarta(0,0,'../ppitems/kartappitems', '', '&STATID='.$STATID.'&JWEEK='.$JWEEK);
 
	$pager->draw_finder = true;

	$pager->AddHidden('JWEEK',$JWEEK);
	$pager->AddHidden('STATID',$STATID);
	$pager->AddHidden('JYEAR',$JYEAR);
	
	$pager->AddFinderScript("s += '&TFUNCTION=renew1';\n"); 
 
	$pager->AddScript("
 function renew1()
 {   
    DoPost('',0);
 }   
 
 function renewyear()
 {   
    var select = $('select[name=\"JYEAR\"] option:selected').val();
        $('input[type=\"hidden\"][name=\"JYEAR\"]').val(select);

    renew2('JWEEK');
 }
 
 function renewquarter()
 {   
    var select = $('select[name=\"JQUARTER\"] option:selected').val();
        $('input[type=\"hidden\"][name=\"JQUARTER\"]').val(select);

    renew2('JWEEK');
 }
 
 function renew2(pole)
 {  
    $('input[type=\"hidden\"][name=\"'+pole+'\"]').val(0);
    
    renew1();
 }
 \n");

  $pager->AddCaption("<td align=\"right\"><nobr>\n
  <input type=\"text\" name=\"JWEEKNAZ\" style=\"width:130px\"
   value=\"".$JWEEKNAZ."\"
   ><input type=\"button\" value=\"x\" style=\"padding:0;width:15px\" onclick=\"renew2('JWEEK');\"
   ><input type=\"button\" value=\"...\" style=\"padding:0;width:15px\"  onClick=\"Finder('Выбор недели','DATER.JWEEK', 1, 100670, ''); \">
  </nobr></td>");
  
  $pager->AddCaption("<td align=\"right\">
  <select name=\"JYEAR\" onchange=\" renewyear(); return false;\">
    <option value=\"0\" ".(($JYEAR==0)?'selected':'').">".'Все года'."</option>
    <option value=\"2013\" ".(($JYEAR==2013)?'selected':'').">".'Год 2013'."</option>
    <option value=\"2014\" ".(($JYEAR==2014)?'selected':'').">".'Год 2014'."</option>
    <option value=\"2015\" ".(($JYEAR==2015)?'selected':'').">".'Год 2015'."</option>
    <option value=\"2016\" ".(($JYEAR==2016)?'selected':'').">".'Год 2016'."</option>
  </select>
  </td>\n");  
  
  $pager->AddCaption("<td align=\"right\">
  <select name=\"JQUARTER\" onchange=\" renewquarter(); return false;\">
    <option value=\"0\" ".(($JQUARTER==0)?'selected':'').">".'Все кварталы'."</option>
    <option value=\"1\" ".(($JQUARTER==1)?'selected':'').">".'1й квартал'."</option>
    <option value=\"2\" ".(($JQUARTER==2)?'selected':'').">".'2й квартал'."</option>
    <option value=\"3\" ".(($JQUARTER==3)?'selected':'').">".'3й квартал'."</option>
    <option value=\"4\" ".(($JQUARTER==4)?'selected':'').">".'4й квартал'."</option>
  </select>
  </td>\n");                    

	$pager->adder = true;
	$pager->killer = true;
	$pager->ItogCol('5,6');
	$pager->en_col_totals = true;
	$pager->Show();
?>