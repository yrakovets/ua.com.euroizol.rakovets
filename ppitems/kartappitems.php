<?PHP

 error_reporting(E_ALL);
  ini_set("display_errors", 1);

//Rakovets Yurii
//21.11.2013
//#2753

/*
  $id; //число, на форме не отображается.  
  //--------------------
  $week;  //ссылка на словарь 
  $statid; // ссылка на словарь, чтобы с тестовой и рабочей одновременно работать - с PKOD = 'ei_budgetitems' в PROPS 
  $summabaz; //число 
  $val;      //число
  $summanaz; //число
  $summasklad; //число
  $kursnaz;    //число
  $kurssklad;  //число
  $summanazfact //число
*/ 



include_once ("../classes/tforma.php");
include ("../includes/propis.php");
include_once ("../wcl_euroizol/ei_r_lib.php");
include_once ("../classes/tkurs.php");
include_once("../classes/tumolch.php");

$conn = connect_to_db() or die("Не удалось подключиться к базе данных");


check_user(true) or die("Нет прав на эту страницу");

if (@strlen($POSTER) == 0) $POSTER = "Edit";
if (@strlen($EDITID) == 0) $EDITID = 0;
if (@strlen($JYEAR) == 0) $JYEAR = Date('Y');
if (@strlen($JQUARTER) == 0) $JQUARTER = Date('m');

include_once ("../classes/tppitem.php");

$oper = new TPPItem();
$oper->conn = $conn;
$oper->id = $EDITID;

function ppGetKurs(&$VAL, &$KURSNAZ, &$KURSSKL, $force_change = FALSE)
{
	//Берем текущие курсы
	
	GLOBAL $conn;
	
	$voper = new TUmolch();
	$voper->conn = $conn;
	
	$t_kurs = new TKurs();
	$t_kurs->conn = $conn;
	$t_kurs->valuta = $VAL;
	$t_kurs->tip = $voper->Value('sklad_tipkurs_cena_ue');
	$t_kurs->GetKursOnDate();
	if (!$KURSNAZ or $force_change)
		$KURSNAZ = $t_kurs->kurs;
	
	$t_kurs->kursdate = 0;
	$t_kurs->kurs = 0;
	$t_kurs->valuta = $voper->Value('sklad_valuta_ue');;
	$t_kurs->GetKursOnDate();
	if (!$KURSSKL or $force_change)
	{
		if ($t_kurs->kurs)
			$KURSSKL = $KURSNAZ / $t_kurs->kurs;
		else 
			$KURSSKL = 1;
	}
}

function ppCalcSum(&$SUMMABAZ, &$KURSNAZ, &$KURSSKL, &$SUMMANAZ, &$SUMMASKLAD, $force_change = FALSE)
{
	//Пересчитываем суммы по указанному курсу
	
	if (!$SUMMANAZ or $force_change)
		$SUMMANAZ = round($SUMMABAZ * $KURSNAZ, 2);
	if (!$SUMMASKLAD or $force_change)
		$SUMMASKLAD = round($SUMMABAZ * $KURSSKL, 2);

}

switch ($POSTER) {
    case "Editing":
      $oper->jweek = $JWEEK;
      $oper->statid = $STATID;
      $oper->summabaz = $SUMMABAZ;
	  $oper->summabazfact = $SUMMABAZFACT;
      $oper->summanaz = $SUMMANAZ;     
      $oper->summanazfact = $SUMMANAZFACT;     
      $oper->summasklad = $SUMMASKLAD;     
      $oper->summaskladfact = $SUMMASKLADFACT;     
	  $oper->val = $VAL;
      $oper->Update();
      break;
    case "Edit":    
      if(!$oper->id) die("Нету записи");
      $oper->Read();
      $JWEEK = $oper->jweek;    
      $JWEEKNAZ = GetFieldFromSQL($conn, 'select a_s1 from uniprops where propcnt = '.$JWEEK);
      $STATID = $oper->statid;    
      $STATIDNAZ = GetFieldFromSQL($conn, 'select a_s1 from uniprops where propcnt = '.$STATID);
      $SUMMABAZ = $oper->summabaz;
      $SUMMABAZFACT = $oper->summabazfact;        
	  $SUMMANAZFACT = $oper->summanazfact;
	  $SUMMASKLADFACT = $oper->summaskladfact;
	  $VAL = $oper->val;   
	  $VALNAZ = GetFieldFromSQL($conn, 'select a_s1 from uniprops where propcnt = '.$VAL);
	  ppGetKurs($VAL, $KURSNAZ, $KURSSKL);
	  ppCalcSum($SUMMABAZ, $KURSNAZ, $KURSSKL, $SUMMANAZ, $SUMMASKLAD);
	  $POSTER = "Editing";   
      $CAPTION = "Изменить";
      break;
	case "ChangeVal":
		ppGetKurs($VAL, $KURSNAZ, $KURSSKL, TRUE);
		ppCalcSum($SUMMABAZ, $KURSNAZ, $KURSSKL, $SUMMANAZ, $SUMMASKLAD, TRUE);
		if($EDITID) 
			$POSTER = "Editing";
		else 
			$POSTER = "Add";
		break;
    case "Add":
	ppCalcSum($SUMMABAZ, $KURSNAZ, $KURSSKL, $SUMMANAZ, $SUMMASKLAD);
      $oper->jweek = $JWEEK;
      $oper->statid = $STATID;
	  $oper->val = $VAL;
      $oper->summabaz = $SUMMABAZ;
	  $oper->summabazfact = $SUMMABAZFACT;
	  $oper->summasklad = $SUMMASKLAD;
	  $oper->summaskladfact = $SUMMASKLADFACT;
	  $oper->summanaz = $SUMMANAZ;
      $oper->summanazfact = $SUMMANAZFACT;
      $oper->Add();
      $EDITID = $oper->id;
      $POSTER = "Editing";
      $CAPTION = "Изменить"; 
      break;
    case "Adding":        
      if (!$JYEAR) $JYEAR = date('Y');
      if (!$JQUARTER) $JQUARTER = ceil(date('m')/3);
      if (!$JWEEK)
        $JWEEK = GetFieldFromSQL($conn, '
            select min(propcnt) from uniprops 
            where propnum = 100670 and a_f1 = '.$JYEAR.'
            and a_f2 = '.$JQUARTER,0);
      $JWEEKNAZ = GetFieldFromSQL($conn, 'select a_s1 from uniprops where propcnt = '.$JWEEK);
      
      if (!$STATID) $STATID = 0;
      $STATIDNAZ = GetFieldFromSQL($conn, 'select a_s1 from uniprops where propcnt = '.$STATID);    
      $SUMMABAZ = 0;           
      $SUMMABAZFACT = 0;
      $VAL = 11;
      $VALNAZ = GetFieldFromSQL($conn, 'select a_s1 from uniprops where propcnt = '.$VAL);
      $SUMMANAZ = 0;
      $SUMMASKLAD = 0;
	  $SUMMANAZFACT = 0;
      $SUMMASKLADFACT = 0;
		
		ppGetKurs($VAL, $KURSNAZ, $KURSSKL);
		
      $POSTER = "Add";   
      $CAPTION = "Добавить";
      break;
    case "Delete":        
      $oper->Delete();
      break;
}


$forma = new TForma();

$w = 30;

$r = $forma->AddRazdel('Основные', 1, false);
$forma->AddField($r, 1, MakeTagRef("Неделя", "JWEEK", $JWEEK, $JWEEKNAZ, 100670, $w));

$stat_sl = GetFieldFromSQL($conn, "select propnum from props where pkod = 'ei_budgetitems'");

$forma->AddField($r, 1, MakeTagRef("Статья", "STATID", $STATID, $STATIDNAZ, $stat_sl, $w, " AND (A_B1 = '-') "));

$forma->AddField($r, 1, MakeTagSingl("Сумма", "SUMMABAZ", $SUMMABAZ, $w));

$forma->AddField($r, 1, MakeTagRef("Валюта", "VAL", $VAL, $VALNAZ, 1, $w));

$forma->AddField($r, 1, MakeTagSingl("Сумма нац.", "SUMMANAZ", $SUMMANAZ, $w));
$forma->AddField($r, 1, MakeTagSingl("Сумма скл.", "SUMMASKLAD", $SUMMASKLAD, $w));
$forma->AddField($r, 1, MakeTagSingl("Курс нац.", "KURSNAZ", $KURSNAZ, $w));
$forma->AddField($r, 1, MakeTagSingl("Курс скл.", "KURSSKL", $KURSSKL, $w));

$date = GetFieldFromSQL($conn, "select a_d2 from uniprops where propcnt = $JWEEK");
//if($date < date('Y-m-d')) 
//  $forma->AddField($r, 1, MakeTagSingl("Сумма факт", "SUMMANAZFACT", $SUMMANAZFACT, $w));
$forma->AddField($r, 1, MakeTagSingl("Сумма факт", "SUMMABAZFACT", $SUMMABAZFACT, $w));
$forma->AddField($r, 1, MakeTagSingl("Сумма нац.факт", "SUMMANAZFACT", $SUMMANAZFACT, $w));
$forma->AddField($r, 1, MakeTagSingl("Сумма скл.факт", "SUMMASKLADFACT", $SUMMASKLADFACT, $w));

//04.08.2014
//Rakovets Yurii
//Добавляем просмотр операций факта
if(($JWEEK) and ($STATID))
{
	$DATS = GetFieldFromSQL($conn, "select to_char(a_d1, 'DD.MM.YYYY') from uniprops where propcnt = ".$JWEEK);
	$DATPO = GetFieldFromSQL($conn, "select to_char(a_d2, 'DD.MM.YYYY') from uniprops where propcnt = ".$JWEEK);
	$A = GetFieldsFromSQL($conn, 'select a_s2, a_s3, a_s4 from uniprops where propcnt = '.$STATID, array('','','') );
	$opertip = $A[0];
	$operres = ei_GetTree($conn, $A[1]);
	$opercat = ei_GetTree($conn, $A[2]);
	$forma->AddField($r, 1, "<td>
				<a  title=\"Детализация факта \" href=\"#\" 
					onclick=\"ShowWindow('../oper/operlistpr',0,'&DATS=".$DATS."&DATPO=".$DATPO."&COLOR=1106&OPERTIP=".$opertip."&OPERCATEGORY=".$opercat."&OPERRESURS=".$operres."&OPERVALUTA=".$VAL."',0,0); 
					return false;\" onmouseover=\"status=''; return true;\">Детализация факта
                </a> 
				</td>");
}


//Скрипт для пересчета сумм склада и нац., если меняется сумма в валюте операции.

$forma->AddHeader('
<script language="JavaScript" src="../js/jquery.js"></script>
<script type="text/javascript" >
    $(document).ready(function(){ 
    
        var SUMMABAZ = $("input[type=\"text\"][name=\"SUMMABAZ\"]"),
            SUMMANAZ = $("input[type=\"text\"][name=\"SUMMANAZ\"]"),
			SUMMASKLAD = $("input[type=\"text\"][name=\"SUMMASKLAD\"]"),
			KURSNAZ = $("input[type=\"text\"][name=\"KURSNAZ\"]"),
			KURSSKL = $("input[type=\"text\"][name=\"KURSSKL\"]");
           
         
        $(SUMMABAZ).on("change paste keyup", function(){
            SUMMANAZ.val("");
            SUMMANAZ.val( (parseFloat(SUMMABAZ.val()) * parseFloat(KURSNAZ.val()) ).toFixed(2));
        });
        $(SUMMABAZ).on("change paste keyup", function(){
            SUMMASKLAD.val("");
            SUMMASKLAD.val( (parseFloat(SUMMABAZ.val()) * parseFloat(KURSSKL.val())).toFixed(2)) ;
        });
        
    });
    
</script>');


$forma->AddHidden('EDITID', $EDITID);
$forma->AddHidden('POSTER', $POSTER);
$forma->AddHidden('CAPTION', $CAPTION);

$forma->AddHidden('JYEAR', $JYEAR);
$forma->AddHidden('JQUARTER', $JQUARTER);

$forma->submit_button = true;
$forma->submit_name = $CAPTION;

$forma->AddButton('Отмена', " window.close();");
$forma->AddButton('Удалить', " DoPost('Delete');");
$forma->focus = "SUMMABAZ";

$text = " s += '&TFUNCTION=ch_val' ";
$forma->AddFinderScript($text);


$forma->AddScript('
	function ch_val()
	{
		DoPost(\'ChangeVal\');
	}
');


$forma->Show();

