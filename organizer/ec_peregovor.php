<?php

header ("Location: /wdelo30/organizer/lead.php?".$_SERVER["QUERY_STRING"]);
/*
include_once("../classes/tperegovor.php");
include_once("../classes/tforma.php");
include_once("../includes/propis.php");

check_user(true) or die(ShowMes('<script language="JavaScript">
if(!navigator.cookieEnabled)
    {if (confirm(\'В вашем браузере отключены куки. Без них невозможно отображать карточку. Включите их для dp.euroizol.com. Перейти на сайт инструкции?\'))
        window.location.href = "https://support.google.com/chrome/answer/95647?hl=ru";}
else window.location.href = "https://dp.euroizol.com";
</script>'));
$conn = connect_to_db() or die (ShowMes('Не удалось подключиться к базе данных'));

if (@strlen($POSTER) == 0) {$POSTER = 'Editing'; $opername = 'Изменение';}
if (@strlen($OLDPOSTER) == 0) $OLDPOSTER = $POSTER;
if (@strlen($EDITID) == 0) $EDITID = '';
if (@strlen($NEEDRENEW) == 0) $NEEDRENEW = '';

// Параметры общие


if (@strlen($TIP) == 0) $TIP = 0;
if (@strlen($SUMMA) == 0) $SUMMA = 0;
if (@strlen($TIPNAZ) == 0) $TIPNAZ = '';
if (@strlen($PERSTATUS) == 0) $PERSTATUS = 0;
if (@strlen($PERSTATUSNAZ) == 0) $PERSTATUSNAZ = '';
if (@strlen($CVET) == 0) $CVET = 0;
if (@strlen($CVETNAZ) == 0) $CVETNAZ = '';
if (@strlen($FIRMA) == 0) $FIRMA = 0;
if (@strlen($FIRMANAZ) == 0) $FIRMANAZ = '';
if (@strlen($ONDATE) == 0) $ONDATE = date('d.m.Y');
if (@strlen($REVISION) == 0) $REVISION = date('d.m.Y');
if (@strlen($NAZ) == 0) $NAZ = '';
if (@strlen($PRIM) == 0) $PRIM = '';
if (@strlen($TEL) == 0) $TEL = '';
if ($TEL) $TEL = filter_var($TEL, FILTER_SANITIZE_NUMBER_INT);
if (@strlen($CLOSED) == 0) $CLOSED = '';
if (@strlen($SODERV) == 0) $SODERV = '';
if (@strlen($PERSONA) == 0) $PERSONA = 0;
if (@strlen($PERSONANAZ) == 0) $PERSONANAZ = '';

if (@strlen($USERID) == 0) $USERID = getdilertip();
if (@strlen($USERIDNAZ) == 0) $USERIDNAZ = '';
if (@strlen($CLIENTSRC) == 0) $CLIENTSRC = 0;
if (@strlen($CLIENTSRCNAZ) == 0) $CLIENTSRCNAZ = "";
if ($FIRMA)
{
    list($CLIENTSRC, $CLIENSRCNAZ, $FIRMANAZ) = GetFieldsFromSQL($conn, "select s.propcnt, s.a_s1, f.sokrash from uniprops s, firms f where f.clientsrc = s.propcnt and f.firma = ".$FIRMA , array(0,0));
    $NAZ = $FIRMANAZ;
}
if (@strlen($A_R5) == 0) $A_R5 = 0;
if (@strlen($A_R6) == 0) $A_R6 = 0;
if (@strlen($A_R7) == 0) $A_R7 = 0;
if (@strlen($A_R8) == 0) $A_R8 = 0;
if (@strlen($A_R9) == 0) $A_R9 = 0;
if (@strlen($A_R10) == 0) $A_R10 = 0;
if (@strlen($A_D1) == 0) $A_D1 = Date("d.m.Y H:i");
if (@strlen($A_D2) == 0) $A_D2 = NULL_DATETIME;
if (@strlen($A_D3) == 0) $A_D3 = NULL_DATETIME;

//include("../includes/flashfld.php");

$CLIENSRCNAZ = GetFieldFromSQL($conn, "select a_s1 from uniprops where propcnt = ".$CLIENTSRC,"");

$mess = '';

const NULL_DATETIME = '01.01.1900 00:00';

get_pravo($conn, 90443);
//if(!$pravo_any) die(ShowMes('Нет прав'));

include_once("../classes/tumolch.php");
$voper = new TUmolch();
$voper->conn = $conn;

function sendMessToUser($conn, $text, $subject, $user)
{
    $crm = new TMessage();
    $crm->conn = $conn;
    $crm->reciever = $user;
    $crm->sender = C_ADMIN;
    $crm->soderv = $text;
    $crm->subject = $subject;
    $s = $crm->Add();
    if ($s) {
        //throw new Exception('Ошибка отправки сообщения пользователю');
        AddToErrorLog($conn, " Ошибка при отправке письма о новом переговоре: ".$s);
    }
}

$forma = new TForma();
$forma->conn = $conn;

function notifyAllMans($conn, $linktext, $manager, $managernaz, $chiefs, $otherPerson, $wideDescription = ""){

    $prim = "<br>*Прим: открыть лид нужно в браузере, <ins>предварительно</ins> залогинившись в ДП";
    global $EDITID;
    if(!$EDITID)
    {
        global $oper;
        $EDITID = $oper->id;
    }
    include_once (__DIR__."/../ecommerce/eclib.php");

    if($manager) //менеджер
    {
        //sendMessToUser($conn,"Создан лид ".$linktext." и назначен на вас. ".$prim.$wideDescription, "Новый лид № ".$EDITID, $manager);
        $text = "Создан лид ".$linktext." и назначен на вас. ".$prim.$wideDescription;
        $subject = "Новый лид № ".$EDITID;
        $textForSMS = "На вас назначен новый лид №".$EDITID." в почте";
        try {
            notifyUserWithPars($conn, $text, $subject, $manager, $textForSMS, true, true, false);
        } catch (Exception $e)
        {
            //AddToLog($conn, $event, $zaktip, $zakaz, $dokkey, $tovar, $nkompl, $prim = '')
        }
    }
    foreach($chiefs as $chief)
    {
        global $POSTER;
        if($chief) //Руководитель1
        {
            if($POSTER = "Add") {
                $text = "Создан лид ".$linktext.".".(($manager)?" Назначен на $managernaz":" Пока не назначен. ").$prim.$wideDescription;
                $subject = "Новый лид № ".$EDITID;
                if (!$manager) $textForSMS = "Новый лид № ".$EDITID." в почте";
                else $textForSMS = "Лид № ".$EDITID." назначен на менеджера";

                //sendMessToUser($conn,"Создан лид ".$linktext.".".(($manager)?" Назначен на $managernaz":" Пока не назначен. ").$prim.$wideDescription, "Новый лид № ".$EDITID, $chief);
            } else {
                //sendMessToUser($conn, "Лид " . $linktext . "." . (($manager) ? " Назначен на $managernaz" : " Пока не назначен. ") . $prim . $wideDescription, "Лид № ".$EDITID." назначен", $chief);
                $text =  "Лид " . $linktext . "." . (($manager) ? " Назначен на $managernaz" : " Пока не назначен. ") . $prim . $wideDescription;
                $subject = "Лид № ".$EDITID." назначен";
                $textForSMS = "Лид № ".$EDITID." назначен на менеджера";
            }
            try {
                notifyUserWithPars($conn, $text, $subject, $chief, $textForSMS, true, true, false);
            } catch (Exception $e)
            {
                //
            }
        }
    }
    if($otherPerson) //Кого уведомлять
    {
        //sendMessToUser($conn,"Созданный лид ".$linktext.$prim.$wideDescription, "Новый лид ", $otherPerson);
        try {
            notifyUserWithPars($conn, "Созданный лид " . $linktext . $prim . $wideDescription, "Новый лид ", $otherPerson, "", true, false, false);
        } catch (Exception $e)
        {
            //
        }
    }
}

$forma->AddHeader("<style>
   w {
    color: red;
    font-weight: bold;
   }
</style>
<script src=\"https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js\"></script>
");

$opername = '';
$w = 40;

switch($POSTER) {
    case 'Cancel':
        die(ShowMes("<SCRIPT language=\"JavaScript\">window.close();</SCRIPT>"));
        break;
    case 'Add':
        if(!$pravo_adding) break;
        if(!$A_R5 && !$A_R6 && !$A_R7){
            $forma->AddCaption("<w>Вкажіть менеджера або керівника!</w>");
            $opername = 'Добавить';
            break;
        }
        if($A_R5)
        {
            $A_D2 = date("d.m.Y H:i");
        }

        if(!$FIRMA && $NAZ)
        {
            include_once (__DIR__."/../classes/tfirma.php");
            $firm = new TFirma();
            $firm->conn = $conn;
            $firm->tip = FIRMTIP_POTENTIAL;
            $firm->sokrash = $NAZ;
            $firm->polnoe = $NAZ;
            $firm->manager = getdilertip();
            $firm->tel = $TEL;
            $firm->clientsrc = $CLIENTSRC;
            $firm->sekret = SEKRET_EUROIZOL;
            $s = $firm->Add();
            if($s) $forma->AddCaption("<w>$s</w>");
            $firm->Read();
            $FIRMA = $firm->firma;
            $FIRMANAZ = $firm->sokrash;
        }


        $oper = new TPeregovor();
        $oper->conn = $conn;
        $oper->tip = $TIP;
        if($A_R5){
            $PERSTATUS = PERSTATUS_NOTPROCESSED;
        } else
        {
            $PERSTATUS = PERSTATUS_NOTAPPOINTED;
        }
        if($CONTACTER){
            include_once (__DIR__."/../classes/tpersona.php");
            $contact = new TPersona();
            $contact->conn = $conn;
            $contact->familia = $CONTACTER;
            $contact->tel = $TEL;
            $contact->filial = $FIRMA;
            $contact->Add();
            $oper->persona = $contact->persona;
            $oper->personanaz = $CONTACTER;
            $PERSONA = $contact->persona;
            $PERSONANAZ = $CONTACTER;
        }
        $oper->perstatus = $PERSTATUS;
        $oper->cvet = $CVET;
        $oper->firma = $FIRMA;
        $oper->ondate = $ONDATE;
        $oper->naz = $NAZ;
        $oper->prim = $PRIM;
        $oper->tel = $TEL;
        $oper->closed = $CLOSED;
        $oper->soderv = $SODERV;
        //$oper->persona = $PERSONA;
        $oper->summa = $SUMMA;
        $oper->userid = $USERID;
        $oper->a_r5 = $A_R5;
        $oper->a_r6 = $A_R6;
        $oper->a_r7 = $A_R7;
        $oper->a_r8 = $A_R8;
        $oper->a_r9 = $A_R9;
        $oper->a_r10 = $A_R10;
        $oper->a_s2 = $A_S2;
        $oper->a_d1 = $A_D1;
        $oper->a_d2 = $A_D2;
        $oper->a_d3 = $A_D3;
        $oper->closed = '-';
        $mess .= $oper->Add();
        if ($mess){
            $forma->AddCaption("<w>$mess</w>");
            break;
        }
        $str_peregovor = "<a href=\"https://dp.euroizol.com/wdelo30/organizer/ec_peregovor.php?EDITID=".$oper->id."\">$oper->id</a>";
        $wideDescription = " <br> Информация по лиду: <br>Контактное лицо: ".$oper->personanaz
            ."<br>Название организации: ".$oper->firmanaz
            ."<br>Телефоны: ".$oper->tel
            ."<br>Интересующие материалы: ".$oper->a_s2
            ."<br>Содержание разговора:<br>".$oper->soderv."<br>";
        notifyAllMans($conn, $str_peregovor, $A_R5, $A_R5NAZ, array($A_R6, $A_R7), $A_R8, $wideDescription);
        $EDITID = $oper->id;
        $opername = 'Изменить';
        $POSTER = 'Editing';
        break;
    case 'CheckNumber':
        $FIRMA = GetFieldFromSQL($conn, "select org_id from ei_tel_num_data where tel_num = '".substr("+380000", 0, 13 - strlen($TEL)).$TEL."'", 0);
        if($FIRMA){
            list($CLIENTSRC, $CLIENSRCNAZ, $FIRMANAZ, $NAZ) =
                GetFieldsFromSQL($conn, "select s.propcnt, s.a_s1, f.sokrash, f.sokrash from uniprops s, firms f where f.clientsrc = s.propcnt and f.firma = ".$FIRMA , array(0,0));
        }
    case 'Adding':
        if (!$pravo_adding) break;
        $POSTER = 'Add';
        $opername = 'Добавить';
        $PERSTATUS = PERSTATUS_NOTPROCESSED;
        break;
    case 'Edit':
        if($PERSTATUS == PERSTATUS_CLIENTEXISTS && !$A_R10)
        {
            $forma->AddCaption("<w>Укажите существующего клиента!</w>");
            $opername = 'Изменить';
            break;
        }
        $oper = new TPeregovor();
        $oper->conn = $conn;
        $oper->id = $EDITID;
        if(!$oper->Read()) die(ShowMes('Нет такой записи'));
        $oper->tip = $TIP;
        $oper->cvet = $CVET;
        $oper->firma = $FIRMA;
        $oper->ondate = $ONDATE;
        $oper->revision = AddTimeToDate('REVISION');
        $oper->naz = $NAZ;
        $oper->prim = $PRIM;
        $oper->tel = $TEL;
        $oper->closed = $CLOSED;
        $oper->soderv = $SODERV;
        $oper->persona = $PERSONA;
        $oper->summa = $SUMMA;
        $notifyAgain = false; //Если проблема с отправкой сообщения чтобы это не мешало работать с карточкой
        if($oper->a_r5 != $A_R5)
        {
            $notifyAgain = true;
            $A_D2 = Date("d.m.Y H:i");
        }
        if($A_R5)
        {
            if($PERSTATUS == PERSTATUS_NOTAPPOINTED) $PERSTATUS = PERSTATUS_NOTPROCESSED;
        } else
        {
            $A_D2 = NULL_DATETIME;
            $PERSTATUS = PERSTATUS_NOTAPPOINTED;
        }
        if ($PERSTATUS == PERSTATUS_RESOLVED && $oper->perstatus != PERSTATUS_RESOLVED)
        {
            $A_D3 = Date("d.m.Y H:i");
        }
        $oper->perstatus = $PERSTATUS;
        $oper->a_r5 = $A_R5;
        $oper->a_r6 = $A_R6;
        $oper->a_r7 = $A_R7;
        $oper->a_r8 = $A_R8;
        $oper->a_r9 = $A_R9;
        $oper->a_r10 = $A_R10;
        $oper->a_s2 = $A_S2;
        $oper->a_d1 = $A_D1;
        $oper->a_d2 = $A_D2;
        $oper->a_d3 = $A_D3;
        $mess .= $oper->Update();
        if($mess) $forma->AddCaption("<w>$mess</w>");
        if($notifyAgain)
        {
            $str_peregovor = "<a href=\"https://dp.euroizol.com/wdelo30/organizer/ec_peregovor.php?EDITID=".$oper->id."\">$oper->id</a>";
            $wideDescription = " <br> Информация по лиду: <br>Контактное лицо: ".$PERSONANAZ
                ."<br>Название организации: ".$oper->firmanaz
                ."<br>Телефоны: ".$oper->tel
                ."<br>Интересующие материалы: ".$oper->a_s2
                ."<br>Содержание разговора:<br>".$oper->soderv."<br>";
            notifyAllMans($conn, $str_peregovor, $A_R5, $A_R5NAZ, array($A_R6, $A_R7), $A_R8, $wideDescription);
        }

        $oper->Read();

    case 'Editing':
        if(!$EDITID) die("Нет переговора");
        $oper = new TPeregovor();
        $oper->conn = $conn;
        $oper->id = $EDITID;
        if(!$oper->Read()) die("Не удалось прочитать запись");
        $TIP = $oper->tip;
        $PERSTATUS = $oper->perstatus;
        $CVET = $oper->cvet;
        $FIRMA = $oper->firma;
        $FIRMANAZ = $oper->firmanaz;
        $ONDATE = $oper->ondate;
        $NAZ = $oper->naz;
        $PRIM = $oper->prim;
        $TEL = $oper->tel;
        $CLOSED = $oper->closed;
        $SODERV = $oper->soderv;
        $PERSONA = $oper->persona;
        $PERSONANAZ = $oper->personanaz;
        $SUMMA = $oper->summa;
        $CLIENTSRC = 0;
        $CLIENSRCNAZ = "";
        if($FIRMA)
        {
            list($CLIENTSRC, $CLIENSRCNAZ) = GetFieldsFromSQL($conn,"select f.clientsrc, u.a_s1 from firms f, uniprops u where f.clientsrc = u.propcnt and f.firma = ".$FIRMA, array(0,""));
        }
        $A_R5 = $oper->a_r5;
        $A_R5NAZ = $oper->a_r5naz;
        $A_R6 = $oper->a_r6;
        $A_R6NAZ = $oper->a_r6naz;
        $A_R7 = $oper->a_r7;
        $A_R7NAZ = $oper->a_r7naz;
        $A_R8 = $oper->a_r8;
        $A_R8NAZ = $oper->a_r8naz;
        $A_R9 = $oper->a_r9;
        $A_R9NAZ = $oper->a_r9naz;
        $A_R10 = $oper->a_r10;
        $A_R10NAZ = $oper->a_r10naz;
        $A_S2 = $oper->a_s2;
        $A_D1 = $oper->a_d1;
        $A_D2 = $oper->a_d2;
        $A_D3 = $oper->a_d3;

        $POSTER = 'Edit';
        $opername = 'Изменить';
        break;
}
$PERSTATUSNAZ = GetFieldFromSQL($conn,"select a_s1 from uniprops where propcnt = $PERSTATUS","");

$USERIDNAZ = GetFieldFromTab($conn, $USERID,'UNIPROPS', 'A_S1', 'PROPCNT');

$forma->title = '';
$forma->pagenaz = '';
$forma->submit_name = $opername;


$forma->AddHeader("<script language=\"JavaScript\" src=\"../js/finder.js\"></script>");
$r = $forma->AddRazdel('Клиент',1,false);

if($POSTER == "Add"){
    $forma->AddField($r,1,MakeTagSingl('Контактное лицо', 'CONTACTER', $CONTACTER, $w * 2));
    if(isBootstrap()){
        $dopBtns = "<div class=\"input-group-btn group-btn-delopro\">
            <button class=\"btn btn-default input-sm input-delopro btn-delopro\" type=\"button\" onclick=\"document.DATER.FIRMA.value=''; document.DATER.NAZ.value='-';\"><i class=\"fa fa-times\" aria-hidden=\"true\"></i></button>
            </div>
            <div class=\"input-group-btn group-btn-delopro\">
            <button class=\"btn btn-default input-sm input-delopro btn-delopro\" type=\"button\" onclick=\"SelectFirma();\" title=\"Все\"><i class=\"fa fa-search\" aria-hidden=\"true\"></i></button>
            </div>";

    } else {
        $dopBtns = "<input onclick=\"document.DATER.FIRMA.value=''; document.DATER.FIRMANAZ.value='-';document.DATER.NAZ.value='';\" style=\"width:15px;min-width:20px;padding-left:2px;padding-right:2px;\" value=\"x\" type=\"button\">
                    <input onclick=\"SelectFirma();\" title=\"Все орг.\" style=\"width:15px;min-width:20px;padding-left:2px;padding-right:2px;\" value=\"...\" type=\"button\">
                    ";
    }
    $forma->AddField($r,1,MakeTagSingl('Название орг.', 'NAZ', $NAZ,  $w * 2, false, $dopBtns));
    $forma->AddHidden("FIRMA", $FIRMA);
    $forma->AddHidden("FIRMANAZ", $FIRMANAZ);
    if(isBootstrap())
    {
        $dopBtns = "<div class=\"input-group-btn group-btn-delopro\">
            <button class=\"btn btn-default input-sm input-delopro btn-delopro\" type=\"button\" onclick=\"CheckNumber();\" title=\"Все\"><i class=\"fa fa-binoculars\" aria-hidden=\"true\"></i></button>
            </div>";
    } else
    {
        $dopBtns = "<input onclick=\"CheckNumber();\" title=\"Найти по номеру\" style=\"width:15px;min-width:20px;padding-left:2px;padding-right:2px;\" value=\"00\" type=\"button\">";
    }
    $forma->AddField($r,1,MakeTagSingl('Телефоны', 'TEL', $TEL, $w * 2, false, $dopBtns));
    $forma->AddField($r,1,MakeTagRefGen('Источник клиента', 'CLIENTSRC', $CLIENTSRC, $CLIENSRCNAZ, 809, $w*2));
}else
{
    if ($FIRMA)
        $firmsLink = "<a href=\"../firms/kartafirm.php?EDITID=".$FIRMA."\" target=\"_blank\">Название орг.</a>";
    else
        $firmsLink = "Название орг.";
    $forma->AddField($r,1,MakeTagRefGen('Контактное лицо', 'PERSONA', $PERSONA, $PERSONANAZ, S_PERSONS, $w * 2));
    $forma->AddField($r,1,MakeTagRefGen($firmsLink, 'FIRMA', $FIRMA, $FIRMANAZ, S_FIRMS, $w * 2 ));
    $forma->AddField($r,1,MakeTagSingl('Телефоны', 'TEL', $TEL, $w * 2));
    $forma->AddField($r,1,MakeTagLabel('Источник клиента', $CLIENSRCNAZ));
    $forma->AddHidden("CLIENTSRC",$CLIENTSRC);
    $forma->AddHidden("CLIENTSRCNAZ",$CLIENTSRCNAZ);
}

$r = $forma->AddRazdel('Содержание',1,false);
$forma->AddField($r,1,MakeTagSingl('Материалы', 'A_S2', $A_S2, $w * 2));
//$forma->AddField($r,1,MakeTagSingl('Содержание', 'PRIM', $PRIM, $w * 2));
$forma->AddField($r,1,MakeTagTextarea('Содержание', 'SODERV', $SODERV, 4, false, false));
if($POSTER == 'Add')
{
    $forma->AddField($r, 1, MakeTagLabel("Статус", $PERSTATUSNAZ));
    $forma->AddHidden("PERSTATUS",$PERSTATUS);
    $forma->AddHidden("PERSTATUSNAZ",$PERSTATUSNAZ);
} else
{
    $forma->AddField($r, 1, MakeTagRefGen("Статус", "PERSTATUS", $PERSTATUS, $PERSTATUSNAZ, 722, $w*2,
        '', false, '','',true, "checkStatus();"));
}
$forma->AddField($r,1,MakeTagRefGen("Существующий клиент", "A_R10", $A_R10, $A_R10NAZ, S_FIRMS, 30, ""));

if($POSTER == 'Add')
    $forma->AddField($r, 1, MakeTagLabel("Оператор", GetFieldFromSQL($conn,"select a_s1 from uniprops where propcnt = ".getdilertip(), "")));
else
    $forma->AddField($r, 1, MakeTagLabel("Оператор", $oper->managernaz));


$r = $forma->AddRazdel('Переадресации',1,false);
$forma->AddField($r,1,MakeTagRefGen("Менеджер", "A_R5", $A_R5, $A_R5NAZ, S_SYSUSERS, 30, ""));
$forma->AddField($r,1,MakeTagRefGen("Руководитель1", "A_R6", $A_R6, $A_R6NAZ, S_SYSUSERS, 30, ""));
$forma->AddField($r,1,MakeTagRefGen("Руководитель2", "A_R7", $A_R7, $A_R7NAZ, S_SYSUSERS, 30, ""));
$forma->AddField($r,1,MakeTagRefGen("Кого уведомить", "A_R8", $A_R8, $A_R8NAZ, S_SYSUSERS, 30, ""));
$forma->AddField($r,1,MakeTagRefGen("Направление продаж", "A_R9", $A_R9, $A_R9NAZ, 100619, 30, ""));
if($POSTER != "Add")
{
    $forma->AddField($r,1,MakeTagLabel("Дата создания", $A_D1));
    $forma->AddField($r,1,MakeTagLabel("Дата назначения", in_array($A_D3,  array(" ",NULL_DATETIME, "", NULL_DATE))?$A_D2:"--"));
    $forma->AddField($r,1,MakeTagLabel("Дата решения", in_array($A_D3,  array(" ",NULL_DATETIME, "", NULL_DATE))?$A_D3:"--"));
}

$forma->AddFinderScript("
if(tip==".S_PERSONS.") dop = ' AND( FILIAL = ".$FIRMA.") ';   
");

$forma->AddScript('
function SelectFirma()
{
     var a = new tfinder();
     a.sql = "  select f.firma, f.sokrash, f.polnoe, f.okpo, m.a_s1 as managernaz, r.a_s1 as regionnaz ";
     a.sql += " from firms f, uniprops m, uniprops r ";
     a.sql += " where f.manager = m.propcnt  ";
     a.sql += " and f.gorod = r.propcnt ";
     if(document.DATER.NAZ.value.length > 0){
        a.sql += " and (f.sokrash like \'%" + document.DATER.NAZ.value + "%\' or  f.polnoe like \'%" + document.DATER.NAZ.value + "%\')";
     }
     a.sql += " order by f.sokrash asc ";
     a.cap = "'.ttt('Выбор организации').'";
     a.cols = "'.ttt('Орг.,Сокращ.наз.,Название,ЕДРПОУ,Менеджер,Регион').'";
     a.el = "setFirma";
     ListFinder = a.show();
     return;
}
function setFirma(firmaValue, firmaName)
{
	document.DATER.FIRMA.value = firmaValue;
	document.DATER.FIRMANAZ.value = firmaName;
	document.DATER.NAZ.value = document.DATER.FIRMANAZ.value;
    DoPost(document.DATER.POSTER.value + \'ing\');
    //document.DATER.NAZ.value = document.DATER.FIRMANAZ.value;
    //return;
}
function CheckNumber()
{
    DoPost("CheckNumber");
}

function checkStatus()
{
    if($("input[name=PERSTATUS]").val() == '.PERSTATUS_CLIENTEXISTS.')
  {
    $("#A_R10NAZ").closest("tr").show();  
  }  else {
    $("#A_R10NAZ").closest("tr").hide();  
  }
}
');

$forma->AddHidden('EDITID',$EDITID);
$forma->AddHidden('POSTER',$POSTER);
$forma->AddHidden('A_D1',$A_D1);
$forma->AddHidden('A_D2',$A_D2);
$forma->AddHidden('A_D3',$A_D3);

$forma->AddFinderScript("
if(propnum == 722) s += '&TFUNCTION=checkStatus';   
");

$forma->_onload.="
document.getElementById('TEL').onkeydown =  function(e){
  if(e.key.length == 1 && e.key.match(/[^0-9\'.]/)){
    return false;
  };
};
";
if($POSTER != "Add")
{
    $forma->AddScript('$(document).ready(function(){        
    if($("input[name=PERSTATUS]").val() != '.PERSTATUS_CLIENTEXISTS.'){                
       $("#A_R10NAZ").closest("tr").hide();            
    }        
    $(\'#PERSTATUSNAZ\')[0].onchange =checkStatus;
});');
}

$forma->Show();

*/