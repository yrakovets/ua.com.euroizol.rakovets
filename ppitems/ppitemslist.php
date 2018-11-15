<?php
include_once("../classes/tppitem.php");
include_once("../includes/propis.php");
include_once("../classes/tlist.php");
include_once("../classes/tumolch.php");

//Rakovets Yurii
//21.11.2013
//#2753

set_new_session();
check_user() or die(ShowMes("��� ���� �� ��� ��������"));

$conn = connect_to_db()
                or die (ShowMes("�� ������� ������������ � ���� ������"));

$voper = new TUmolch();
$voper->conn = $conn;

if (@strlen($POSTER) == 0) $POSTER = "";
if (@strlen($EDITID) == 0) $EDITID = "";

if (@strlen($JYEAR) == 0) $JYEAR = Date('Y');
if (@strlen($JQUARTER) == 0) $JQUARTER = floor((Date('m')-1) / 3) + 1;
if (@strlen($JWEEK) == 0) $JWEEK = 0;
$JWEEKNAZ = GetFieldFromSQL($conn, "select a_s1 from uniprops where propcnt = $JWEEK");
if (@strlen($STATID) == 0) $STATID = 0;

if (@strlen($CLOSED) == 0) $CLOSED = 0;


$mess = '';

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


        $sql = "SELECT PP.ID, PW.A_F1, PW.A_F2, PW.A_F3, ST.A_S1, PP.SUMMABAZ, V.A_S2 
                FROM EI_STATJURPL PP, UNIPROPS PW, UNIPROPS ST, UNIPROPS V
                WHERE (PP.JWEEK = PW.PROPCNT) 
                  AND (PP.STATID = ST.PROPCNT) 
                  AND (PP.VAL = V.PROPCNT)  
                  AND (ST.CHILDS = 0)
                  AND (ST.A_B1 = '-')
                  $s4";

  $pager = new TList();
  $pager->conn = $conn;



  $pager->caption = '������';
  $pager->cols = '���, ���, �������, ������, ������, �����, ������ ';
  $pager->sql = $sql;

  $pager->AddKarta(0,0,'../ppitems/kartappitems');
 
                               
  $pager->draw_finder = true;

  $pager->AddHidden('JWEEK',$JWEEK);
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
   ><input type=\"button\" value=\"...\" style=\"padding:0;width:15px\"  onClick=\"Finder('����� ������','DATER.JWEEK', 1, 100670, ''); \">
  </nobr></td>");
  
  $pager->AddCaption("<td align=\"right\">
  <select name=\"JYEAR\" onchange=\" renewyear(); return false;\">
    <option value=\"0\" ".(($JYEAR==0)?'selected':'').">".'��� ����'."</option>
    <option value=\"2013\" ".(($JYEAR==2013)?'selected':'').">".'��� 2013'."</option>
    <option value=\"2014\" ".(($JYEAR==2014)?'selected':'').">".'��� 2014'."</option>
    <option value=\"2015\" ".(($JYEAR==2015)?'selected':'').">".'��� 2015'."</option>
    <option value=\"2016\" ".(($JYEAR==2016)?'selected':'').">".'��� 2016'."</option>
  </select>
  </td>\n");  
  
  $pager->AddCaption("<td align=\"right\">
  <select name=\"JQUARTER\" onchange=\" renewquarter(); return false;\">
    <option value=\"0\" ".(($JQUARTER==0)?'selected':'').">".'��� ��������'."</option>
    <option value=\"1\" ".(($JQUARTER==1)?'selected':'').">".'1� �������'."</option>
    <option value=\"2\" ".(($JQUARTER==2)?'selected':'').">".'2� �������'."</option>
    <option value=\"3\" ".(($JQUARTER==3)?'selected':'').">".'3� �������'."</option>
    <option value=\"4\" ".(($JQUARTER==4)?'selected':'').">".'4� �������'."</option>
  </select>
  </td>\n");                    

 $pager->adder = true;
 $pager->killer = true;
 $pager->Show();
?>