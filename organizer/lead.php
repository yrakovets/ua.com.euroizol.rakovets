<?php
/**
 * @author Rakovets Yurii <rakovets@mizol.com>
 * @ticket{22727}
 * @date 25.10.2018
 * @brief Форма для обектов наследников класса TLead
 */

include_once(__DIR__."/../classes/tforma.php");
include_once(__DIR__."/../includes/propis.php");
include_once(__DIR__."/classes/tlead.php");
include_once(__DIR__."/classes/tleadfactory.php");


check_user(true) or die(ShowMes('<script language="JavaScript">
if(!navigator.cookieEnabled)
    {if (confirm(\'В вашем браузере отключены куки. Без них невозможно отображать карточку. Включите их для dp.euroizol.com. Перейти на сайт инструкции?\'))
        window.location.href = "https://support.google.com/chrome/answer/95647?hl=ru";}
else window.location.href = "https://dp.euroizol.com";
</script>'));
$conn = connect_to_db() or die (ShowMes('Не удалось подключиться к базе данных'));

if (@strlen($POSTER) == 0) $POSTER = 'Editing';
if (@strlen($LEADTYPE) == 0) $LEADTYPE = 0;
if (@strlen($EDITID) == 0) {$EDITID = 0; $opername = 'Добавить';}

if (!$LEADTYPE && !$EDITID) die("Не передан ни ID, ни тип ");

if(!$EDITID && strpos($POSTER, "Add") === false) die("Нет ID и это не добавление. POSTER=".$POSTER.".");


$forma = new TForma();
$forma->conn = $conn;

if ($EDITID)
{
    $lead = \organizer\classes\TLeadFactory::readLead($conn, $EDITID);
}else
{
    try {
        $lead = \organizer\classes\TLeadFactory::createLeadByType($conn, $LEADTYPE);
    } catch (Exception $e)
    {
        die($e->getMessage());
    }
}



function readFormData(&$leadFormFields, &$lead)
{
    foreach ($leadFormFields["sections"] as $section) {
        foreach ($section["fields"] as $field) {
            global $$field["field"];
            $lead->$field["field"] = $$field["field"];
        }
    }
    foreach ($leadFormFields["hiddens"] as $hidden)
    {
        global $$hidden["field"];
        $lead->$hidden["name"] = $$hidden["field"];
    }

}

$leadFormFields = $lead->getFormFields();

switch ($POSTER)
{
    case "Edit":
        readFormData($leadFormFields, $lead);
        try
        {
            $s =  $lead->Update();
        } catch (Exception $e)
        {
            $forma->AddCaption("<w>".$e->getMessage()."</w>");
            //die($e->getMessage());
            //$forma->AddCaption("<p style='color: red; font-weight: bold'>".$e->getMessage()."</p>");
        }
        try {
            $lead->Read();
        } catch (Exception $e)
        {
            $forma->AddCaption("<w>".$e->getMessage()."</w>");
            //die($e->getMessage());
        }
        $opername = 'Изменить';
        break;
    case "Editing":
        $opername = 'Изменить';
        $POSTER = "Edit";
        break;
    case "Add":
        readFormData($leadFormFields, $lead);
        $lead->userid = getdilertip();
        try {
            $lead->Add();
            $leadFormFields = $lead->getFormFields();
            $lead->Read();
            $EDITID = $lead->id;
            $POSTER = "Edit";
            $opername = "Изменить";
        } catch (Exception $e){
            $forma->AddCaption("<w>".$e->getMessage()."</w>");
        }
        break;
    case "Adding":
        $opername = 'Добавить';
        $POSTER = "Add";
        break;
}


$forma->pagenaz = $leadFormFields["name"];
foreach ($leadFormFields["sections"] as $section)
{
    $r = $forma->AddRazdel($section["name"]);
    foreach ($section["fields"] as $field)
    {
        switch ($field["type"])
        {
            case "string":
                $forma->AddField($r, 1, MakeTagSingl($field["name"], $field["field"], $lead->$field["field"], 30, false, $field["dop"]));
                break;
            case "reference":
                $nazfield = $field["field"]."NAZ";
                $$nazfield = $lead->$field["namefield"];
                if($field["card"] && $lead->$field["field"])
                {
                    $nametext = "<a href=\"#\" onclick=\"ShowKarta('".$field["card"]."', document.DATER.firma.value);\">".$field["name"]."</a>";
                } else
                {
                    $nametext = $field["name"];
                }
                $sz = 30;
                if($field["sz"]) $sz = $field["sz"];
                $dop = '';
                if($field["dop"]) $dop = $field["dop"];
                $ro = false;
                if($field["ro"]) $ro = $field["ro"];
                $in_notin = '';
                if($field["in_notin"]) $in_notin = $field["in_notin"];
                $extra_text = "";
                if($field["extra_text"]) $extra_text = $field["extra_text"];
                $draw_tr = true;
                if($field["draw_tr"]) $draw_tr = $field["draw_tr"];
                $onreset = '';
                if($field["onreset"]) $onreset = $field["onreset"];
                $forma->AddField($r, 1, MakeTagRefGen($nametext, $field["field"], $lead->$field["field"], $lead->$field["namefield"], $field["reftype"] ,
                    $sz , $dop, $ro, $in_notin, $extra_text, $draw_tr, $onreset ));
                break;
            case "label":
                $forma->AddField($r, 1, MakeTagLabel($field["name"], $lead->$field["field"]));
                break;
            case "blob":
                $forma->AddField($r, 1, MakeTagTextarea($field["name"], $field["field"], $lead->$field["field"]));
                $forma->tiny_fields =  $field["name"];
                break;
        }

    }
}

foreach ($leadFormFields["hiddens"] as $hidden)
{
    $forma->AddHidden($hidden["name"], $lead->$hidden["field"]);
}
foreach ($leadFormFields["scripts"] as $script)
{
    $forma->AddScript($script);
}

foreach ($leadFormFields["finderscripts"] as $finderscript)
{
    $forma->AddFinderScript($finderscript);
}


$forma->AddHeader("<script language=\"JavaScript\" src=\"../js/finder.js\"></script>");

$forma->AddHidden('LEADTYPE', $LEADTYPE);
$forma->AddHidden('POSTER', $POSTER);
$forma->AddHidden('EDITID', $EDITID);

$forma->submit_name = $opername;

//$forma->AddCaption("<pre>".print_r($lead, true)."</pre>");

$forma->AddHeader("<style>
   w {
    color: red;
    font-weight: bold;
   }
</style>
");


$forma->Show();

