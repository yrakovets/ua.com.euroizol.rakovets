<?php
 
include_once("../includes/propis.php");
include_once("../classes/tlist.php");
include_once("../classes/tumolch.php");
include_once("../classes/tppitem.php");

//Rakovets Yurii
//21.11.2013
//#2753

set_new_session();
check_user() or die(ShowMes("Нет прав на эту страницу"));

$conn = connect_to_db()
                or die (ShowMes("Не удалось подключиться к базе данных"));

error_reporting(E_ALL);
ini_set("display_errors", 1);


$voper = new TUmolch();
$voper->conn = $conn;

if (@strlen($POSTER) == 0) $POSTER = "";
//if (@strlen($EDITID) == 0) $EDITID = "";

if (@strlen($JYEAR) == 0) $JYEAR = Date('Y');
if (@strlen($JQUARTER) == 0) $JQUARTER = floor((Date('m')-1) / 3) + 1;
if (@strlen($JWEEK) == 0) $JWEEK = 0;
$JWEEKNAZ = GetFieldFromSQL($conn, "select a_s1 from uniprops where propcnt = $JWEEK");
//if (@strlen($OSTATOK) == 0) $OSTATOK = 0; 
if (@strlen($TYPE) == 0) $TYPE = 0;
if (@strlen($TIPSUM) == 0) $TIPSUM = 0;

if (@strlen($VAL) == 0) $VAL = 0;
if (@strlen($VALNAZ) == 0) $VALNAZ = 'Все';
if ($VAL)
{
	if(strripos($VAL, ','))
		$VALNAZ = $VAL;
	else 
		$VALNAZ = GetFieldFromSQL($conn, "select a_s1 from uniprops where propcnt =".$VAL, '');
}

/*
//Если тип План/Факт, инициализируем массив цветов раскраски
if ($TYPE == 2)
{
	$arr_colour = array();
	$sql = "select a_f1, a_r1 from uniprops where propnum = 100779 order by a_f1 desc";
	$rs = $conn->Execute($sql) or die("Ошибка ".$sql);
	while(!$rs->EOF)
	{
		$arr_colour[] = array($rs->fields[0], $rs->fields[1]);
		$rs->MoveNext();
	}
}
*/

$sql_where_val = '';
if ($VAL) $sql_where_val = ' and (pp.val in ('.$VAL.'))';

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
		die("Нипанаятна, насяника, со такое TIPSUM");
}

if ($TYPE == 1)
  $sum_field .= "fact";
if ($TYPE == 2)
  $sum_field = "(".$sum_field."fact - ".$sum_field.")";
  
$mess = '';
$slov_w = 100670;
$slov_st = 100669;

$prof_def = GetFieldFromSQL($conn, "
      select propcnt 
      from uniprops 
      where propnum = $slov_st
      and a_s1 = '3. Профицит/Дефицит'");
$ost_st = GetFieldFromSQL($conn, "
      select propcnt 
      from uniprops 
      where propnum = $slov_st
      and a_s1 = '4. Остаток'");

class TReplist extends TList
{
	private $cveta;// [ 'cvet' => 'red', 'ot'=>,  'do'=> ] 
	private $type_move; // [ '777' =>  '+' ];
	private $maxcolour; 

	function load_cvet_type_move(){
        
        $sql="SELECT PROPCNT, A_F1, A_S2 FROM UNIPROPS WHERE PROPNUM=100779 order by A_F1 asc";
        $rs=$this->conn->Execute($sql);
        $prev=-1000000000000;
        while(!$rs->EOF){
            $this->cveta[$rs->fields[0]]=array("cvet"=>$rs->fields[2],
                                               "ot" =>$prev,
                                               "do" => $rs->fields[1]
                                               );
            $prev=$rs->fields[1];
			$this->maxcolour = $rs->fields[2];
            $rs->MoveNext();
        }
        
        $sql="select PROPCNT, A_B2 from uniprops where PROPNUM=100669";
        $rs=$this->conn->Execute($sql);
        
        while(!$rs->EOF){
            $this->type_move[$rs->fields[0]]=$rs->fields[1];            
            $rs->MoveNext();
        }  
        // echo '<pre>'.print_r($this->cveta,1).'</pre>';
        // echo '<pre>'.print_r($this->type_move,1).'</pre>';
		
	}
	
	private function make_color($plan,$fact, $st)
	{
		$cvet="";  
	  
		if (!$plan) $plan = 1;
		if (!$fact) $fact = 1;
		
		$val = $fact/$plan * 100;
		
		if( $this->type_move[$st]=='-' )  
		{
			if (($val < 0) and ($plan > $fact)) return $this->maxcolour;
			$val = 10000/ $val;
		}
		else
		{
			if (($val < 0) and ($plan < $fact)) return $this->maxcolour;
		}

		foreach( $this->cveta as &$va ){
			if ( $va["ot"] <= $val and
					$va["do"] > $val )
			{
				$cvet=$va['cvet'];
				break;
			}   
		}
		return $cvet;
	}

	
	
  function MakeCol($rs,$i)
  {
    if ($i<=3)
    {
      return parent::MakeCol($rs,$i); 
    }   
    global $prof_def, $ost_st;
    $arr_sys_st = array($prof_def, $ost_st); 
    if (in_array($rs->fields[0], $arr_sys_st))
    {
      // return parent::MakeCol($rs,$i);
    } 
    global $arr_ttl, $conn, $TIPSUM, $VAL, $TYPE;
    $jweek = substr($arr_ttl[$i-4], 4);
	
	//Если сравнение плана/факта, то добавляем раскраску
	$colour = "";
	if($TYPE == 2)
	{
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
				die("Не понят тип суммы");
		}
		$sql_where_val = "";
		if($VAL) $sql_where_val = " and val in (".$VAL.")" ;
		$sql = "select sum(".$sum_field."fact), sum(".$sum_field.") from ei_statjurpl where jweek = $jweek and statid = ".$rs->fields[0].$sql_where_val ;
		list($fact, $plan) = GetFieldsFromSQL($conn, $sql, array(0,0));
		
		$colour=" bgcolor='".$this->make_color($plan, $fact, $rs->fields[0])."'";
		
	}
	
	$outstr = "<a href=\"#\" onclick=\"ShowWindow('../ppitems/pp1weekitem', '', '&JWEEK=$jweek&STATID=".$rs->fields[0]."&TIPSUM=$TIPSUM&VAL=".$VAL."', '0', '0');return false;\" onmouseover=\"status=''; return true;\">";
	$outstr .= number_format($rs->fields[$i], 0, '.', ' ')."</a>";

    return "<td valign=\"top\" align=\"right\" nowrap=\"\" $colour>".$outstr."</td>" ;
  }
  
  function MakeSubtotals($grupid)
  {
		global $weeks_kol;
		for ($i = 1 ; $i <= $weeks_kol; $i++)
			$this->_subtotals[$i + 3] = number_format($this->_subtotals[$i + 3], 0, '.', ' ');
  }
  
  function MakeSubtotals1($grupid)
  {
		global $weeks_kol;
		for ($i = 1 ; $i <= $weeks_kol; $i++)
			$this->_subtotals1[$i + 3] = number_format($this->_subtotals1[$i + 3], 0, '.', ' ');
  }

}


if ((@strlen($arr_ost) == 0) or ($POSTER == 'Recalc'))
{
	$arr_ost = array();
	if ($JQUARTER) $qu = $JQUARTER;
	else $qu = 1;
	if ($JWEEK)
	{
		$prew_date = date('Y-m-d', strtotime(''.(string)$JYEAR.'-'.(string)(($qu-1)*3+1).'-1')-86400);
		$ost_week = GetFieldFromSQL($conn, "select propcnt from uniprops where propnum = $slov_w and a_d2 = '$prew_date'", 0);
	}
	else
	{
		$sql_ost_week = "select propcnt from uniprops where propnum = $slov_w and a_f1 = $JYEAR and a_f2 = $qu and a_f3 = 0";
		$ost_week = GetFieldFromSQL($conn, $sql_ost_week, 0);
	}
	if ($ost_week)   
	{
		$sql_ost = "select val, summabaz, summabazfact, summanaz, summanazfact, summasklad, summaskladfact from ei_statjurpl where statid = $ost_st and jweek = $ost_week";
		$sts = $conn->Execute($sql_ost) or die("Плохо дело: ".$sql_ost) ;
		while (!$sts->EOF)
		{
			$temp_ar = array();
			$temp_ar['summabaz'] = $sts->fields[1];
			$temp_ar['summabazfact'] = $sts->fields[2];
			$temp_ar['summanaz'] = $sts->fields[3];
			$temp_ar['summanazfact'] = $sts->fields[4];
			$temp_ar['summasklad'] = $sts->fields[5];
			$temp_ar['summaskladfact'] = $sts->fields[6];
			$arr_ost[$sts->fields[0]] = $temp_ar;
			$sts->MoveNext();
		}
	}
}

//spis of weeks
$sql_weeks = "
  select propcnt, a_f3 
  from uniprops 
  where propnum = $slov_w
  and a_f1 = $JYEAR
  and a_f3 > 0";

if ($JQUARTER) 
  $sql_weeks .= " and a_f2 = $JQUARTER";
if ($JWEEK) 
  $sql_weeks .= " and propcnt = $JWEEK";   
  
$sql_weeks .= " 
        order by a_s1";


$ws = $conn->Execute($sql_weeks,0) or die("Упс! ".$sql_weeks);
$sql = " 
 from (
   select pp.statid, ps.a_s1,
	psf.a_s1 as psfa_s1, psg.a_s1 as psga_s1";

$weeks_kol = 0;
$cols = 'Статья, Название, Группа, Раздел, ';
$itogcols = '';



if ($POSTER == 'Recalc')
{
  $root_doh = GetFieldFromSQL($conn, "
      select propcnt 
      from uniprops 
      where propnum = $slov_st
      and a_s1 = '1. Поступления'");
  $root_rash = GetFieldFromSQL($conn, "
      select propcnt 
      from uniprops 
      where propnum = $slov_st
      and a_s1 = '2. Выплаты'");
  $doh = ''.$root_doh;
  $rash = ''.$root_rash;

  $rec_add = $doh;
  $recs = $rec_add;

  while ($rec_add)
  {
    $sql_r = "select ' ' || LISTAGG(TO_CHAR(PROPCNT), ', ') WITHIN GROUP(ORDER BY TO_CHAR(PROPCNT))
                            from uniprops
                            where propnum = $slov_st  
                            and pfather in (" . $rec_add . ")
                            and propcnt not in (" . $recs . ")";
    $rs = GetFieldFromSQL($conn, $sql_r, '');
    $recs .= ', ' . $rs;
    $rec_add = $rs;
  }              
  
  $doh = rtrim($recs, ', ');

  $rec_add = $rash;
  $recs = $rec_add;

  while ($rec_add)
  {
    $sql_r = "select ' ' || LISTAGG(TO_CHAR(PROPCNT), ', ') WITHIN GROUP(ORDER BY TO_CHAR(PROPCNT))
                            from uniprops
                            where propnum = $slov_st  
                            and pfather in (" . $rec_add . ")
                            and propcnt not in (" . $recs . ")";
    $rs = GetFieldFromSQL($conn, $sql_r, '');
    $recs .= ', ' . $rs;
    $rec_add = $rs;
  }              
  
  $rash = rtrim($recs, ', ');
     
}

//$ost = $OSTATOK[$sum_field];
$spis4sel = '';
$spis4sel2 = '';

while(!$ws->EOF)
{
	$spis4sel .= ", 
		sum(decode(pp.jweek, ".$ws->fields[0].", $sum_field, 0)) as sum_".$ws->fields[0];
	$spis4sel2 .= ", sum_".$ws->fields[0];
	$weeks_kol ++;       
	$cols .= $ws->fields[1].', ';      
	$itogcols .= ($weeks_kol+3).',';     
	if($POSTER == 'Recalc')
	{
		//Если по факту, нужно еще пересчитать факт по каждой статье
		if ($TYPE) 
		{
			//список статей
			//можно было бы по отсутствию дочерних записей и флагу Системный- Нет,
			//но, имхо, по пустым полям типов операций, ресурсов и категорий расходов лучше
			$sql_st = "	select propcnt as stat_id 
						from uniprops 
						where propnum = 100669
							and ((a_s2 is not null)
								or (a_s3 is not null)
								or (a_s4 is not null)
								)";
			$ss = $conn->Execute($sql_st) or die("Дас ист фехлер: ".$sql_st); // fehler = ошибка (нем.). хз чего на нем. потянуло. прекращаю бухать на работе 
			
			while(!$ss->EOF)
			{
				//Добавляем цикл по валютам
				$sql_val = "select propcnt
					from uniprops 
					where propnum = 1";
				
				$val_s = $conn->Execute($sql_val);
				
				while (!$val_s->EOF)
				{
					
					//Проверяем, есть ли на эту неделю по этой статье запись
					$pp = GetFieldFromSQL($conn, "	select id 
													from ei_statjurpl
													where jweek = ".$ws->fields[0]."
													and statid = ".$ss->fields[0]."
													and val = ".$val_s->fields[0], 0);
					$pp_ed = new TPPItem();
					$pp_ed->conn = $conn;
					if($pp)
					{
						$pp_ed->id = $pp;
						$pp_ed->Read();
						$pp_ed->CalcFact();
						$pp_ed->Update();
					}
					else
					{
						$pp_ed->jweek = $ws->fields[0];
						$pp_ed->statid = $ss->fields[0];
						$pp_ed->val = $val_s->fields[0];
						$pp_ed->Add();
						$pp_ed->CalcFact();
						$pp_ed->Update();
					}
					
					
					if((!$pp_ed->summabaz) and (!$pp_ed->summabazfact))
						$pp_ed->Delete();
					
					$val_s->MoveNext();
				}
				$ss->MoveNext();
			}
		}
		
		$sql_os = "	select val, 	sum(summabaz) as summabaz, sum(summabazfact) as summabazfact, 
									sum(summanaz) as summanaz, sum(summanazfact) as summanazfact, 
									sum(summasklad) as summasklad, sum(summaskladfact) as summaskladfact 
					from(
							select pl.val, pl.summabaz, pl.summabazfact, pl.summanaz, pl.summanazfact, pl.summasklad, pl.summaskladfact
							from ei_statjurpl pl, uniprops st
							where pl.jweek = ".$ws->fields[0]."
								and pl.statid = st.propcnt
								and st.a_b1 = '-'
								and st.a_b2 = '+'
								and ((pl.summabaz != 0)
										or (pl.summabazfact != 0))
							union all
							select pl.val, - pl.summabaz, - pl.summabazfact, - pl.summanaz, - pl.summanazfact, - pl.summasklad, - pl.summaskladfact
							from ei_statjurpl pl, uniprops st
							where pl.jweek = ".$ws->fields[0]."
								and pl.statid = st.propcnt
								and st.a_b1 = '-'
								and st.a_b2 = '-'
								and ((pl.summabaz != 0)
										or (pl.summabazfact != 0))
						)
					group by val";

		$os = $conn->Execute($sql_os);

		$oper = new TPPItem();
		$oper->conn = $conn;   
		$oper->jweek = $ws->fields[0];
		$oper->statid = $prof_def;
		
		//Чистим существующие профицит-дефицит и остатки.
		$s = $conn->Execute("delete from ei_statjurpl where jweek = ".$ws->fields[0]." and statid in ($prof_def, $ost_st)");
		
		while (!$os->EOF)
		{
			$oper->val = $os->fields[0];
			$oper->summabaz = $os->fields[1];
			$oper->summabazfact = $os->fields[2];
			$oper->summanaz = $os->fields[3];
			$oper->summanazfact = $os->fields[4];
			$oper->summasklad = $os->fields[5];
			$oper->summaskladfact = $os->fields[6];
			$oper->Add();
			
			@$arr_ost[$oper->val]['summabaz'] += $oper->summabaz;
			@$arr_ost[$oper->val]['summabazfact'] += $oper->summabazfact;
			@$arr_ost[$oper->val]['summanaz'] += $oper->summanaz;
			@$arr_ost[$oper->val]['summanazfact'] += $oper->summanazfact;
			@$arr_ost[$oper->val]['summasklad'] += $oper->summasklad;
			@$arr_ost[$oper->val]['summaskladfact'] += $oper->summaskladfact;
			
			$os->MoveNext();
		}
		
		$sql_val = "select propcnt from uniprops where propnum = 1";
		$vs = $conn->Execute($sql_val);
		while (!$vs->EOF)
		{
			if ((@$arr_ost[$vs->fields[0]]['summabaz']) or (@$arr_ost[$vs->fields[0]]['summabazfact']))
			{
				$operost = new TPPItem();
				$operost->conn = $conn;   
				$operost->jweek = $ws->fields[0];
				$operost->statid = $ost_st;
				$operost->val = $vs->fields[0];
				
				foreach ($arr_ost[$vs->fields[0]] as $key => $value)
				{
					$operost->$key = $value;
				}
				$operost->Add();
			}
			$vs->MoveNext();
		}
		
	 
	}
	$ws->MoveNext();  
}

$arr_ttl = str_word_count($spis4sel2, 1, 'sum_1234567890');

$sql = "select statid, a_s1, psfa_s1, psga_s1".$spis4sel2.$sql; 

$sql .= $spis4sel;
$sql .= "  
FROM EI_STATJURPL PP, uniprops pw, uniprops ps, uniprops psf, uniprops psg 
	where pw.propnum = $slov_w 
	and pw.propcnt = pp.jweek 
	and pw.a_f3 >0
	and pp.statid = ps.propcnt
	and ps.pfather = psf.propcnt
	and psf.pfather = psg.propcnt            
	$sql_where_val
  and ps.a_b1 = '-'
group by pp.statid, ps.a_s1, psf.a_s1, psg.a_s1  
";
$sql .= "union all
	
select pp.statid, ps.a_s1,
	ps.a_s1 as psfa_s1, ps.a_s1 as psga_s1 
";
$sql .= $spis4sel;
$sql .= "
FROM EI_STATJURPL PP, uniprops pw, uniprops ps 
	where pw.propnum = 100670 
	and pw.propcnt = pp.jweek 
	and pp.statid = ps.propcnt
	and pw.a_f3 >0
	$sql_where_val
  and ps.a_b1 = '+'
group by pp.statid, ps.a_s1 
	) A
order by psga_s1, psfa_s1, a_s1";

  $pager = new TReplist();
  $pager->conn = $conn;
  
  $pager->load_cvet_type_move();

  $pager->cols = $cols;    
  $pager->ItogCol(rtrim($itogcols,','));   
  

  $pager->caption = 'Список';
  $pager->sql = $sql;

  $pager->AddHidden('JWEEK',$JWEEK);
  $pager->AddHidden('JYEAR',$JYEAR);
  $pager->AddHidden('POSTER',$POSTER);
  $pager->AddHidden('TYPE',$TYPE);     
  $pager->AddHidden('VAL',$VAL);     
   
                                                           
  $pager->grupfield = 2;
  $pager->grupfieldnaz = 2;
  $pager->grupfield1 = 3;
  $pager->grupfieldnaz1 = 3;
  $pager->HideCol('2,3');  
  
  $pager->draw_finder = true;
  $pager->AddFinderScript("s += '&ENSPISOK=1';\n s += '&TFUNCTION=renew1';\n s += '&KOSHEK=".$VAL."';\n"); 
  
  $pager->AddScript("
  
 function renew1()
 {   
    DoPost('');
 }
  
 function renewyear()
 {   
    var select = $('select[name=\"JYEAR\"] option:selected').val();
        $('input[type=\"hidden\"][name=\"JYEAR\"]').val(select);
    
    document.DATER.OSTATOK.value=0;
    
    renew2('JWEEK');
 }
 
 function renewquarter()
 {   
    var select = $('select[name=\"JQUARTER\"] option:selected').val();
        $('input[type=\"hidden\"][name=\"JQUARTER\"]').val(select);    
        
    document.DATER.OSTATOK.value=0;

     renew2('JWEEK');
 }    
 
  function renewtype()
 {   
    var select = $('select[name=\"TYPE\"] option:selected').val();
        $('input[type=\"hidden\"][name=\"TYPE\"]').val(select);    
        
     renew1();
 }
 
 function renew2(pole)
 {  
    $('input[type=\"hidden\"][name=\"'+pole+'\"]').val(0);
    
     renew1();
 }
 \n");
 
  $pager->AddCaption("<td align=\"right\"><nobr>\n
  <input type=\"text\" name=\"VALNAZ\" style=\"width:130px\"value=\"".$VALNAZ."\">
  <input type=\"button\" value=\"x\" style=\"padding:0;width:15px\" onclick=\" document.DATER.VAL.value=''; document.DATER.VALNAZ.value='Все'; renew1();\">
  <input type=\"button\" title=\"Выбрать\" value=\"...\" style=\"padding:0;width:15px\"  onClick=\"Finder('Выбор недели','DATER.VAL', 1, 1, '', '1'); \">
  </nobr></td>");

  $pager->AddCaption("<td align=\"right\">
  <select name=\"TIPSUM\" onchange=\" renew1(); return false;\">
    <option value=\"0\" ".(($TIPSUM==0)?'selected':'').">".'Суммма склада'."</option>
    <option value=\"1\" ".(($TIPSUM==1)?'selected':'').">".'Сумма нац.'."</option>
    <option value=\"2\" ".(($TIPSUM==2)?'selected':'').">".'Сумма операции'."</option>
  </select>
  </td>\n");
 
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
  
    $pager->AddCaption("<td align=\"right\">
  <select name=\"TYPE\" onchange=\" renewtype(); return false;\">
    <option value=\"0\" ".(($TYPE==0)?'selected':'').">".'План'."</option>
    <option value=\"1\" ".(($TYPE==1)?'selected':'').">".'Факт'."</option>
	<option value=\"2\" ".(($TYPE==2)?'selected':'').">".'Выполнение'."</option>
  </select>
  </td>\n");    
  
  if ($ost_week)
  {
	$pager->AddCaption("<td align=\"right\">
		<a href=\"#\" onclick=\"ShowWindow('../ppitems/pp1weekitem', '', '&JWEEK=$ost_week&STATID=$ost_st&TIPSUM=0&VAL=0', '0', '0');return false;\" onmouseover=\"status=''; return true;\">Остаток</a>
	</td>\n");
  }
  
  $pager->AddButton('Пересчитать', "DoPost('Recalc');");   
  // $pager->AddCaptionCell("<input type=\"text\" name=\"OSTATOK\" style=\"width:100px\" value=\"".$OSTATOK."\"onchange=\" DoPost('Recalc');\">");             

  $pager->Show();
?>