<?php
/**
 * Created by PhpStorm.
 * User: rakovets
 * Date: 26.10.2018
 * Time: 11:01
 */

namespace organizer\classes;

class TComplaintLead extends TLead
{

    public function __construct()
    {
        $this->leadtype = LEADTYPE_COMPLAINT;
    }


    function IsCorrect()
    {
        // TODO: Implement checkCorrection() method.
    }

    private static function notifyDirsSceduled($conn, $hours = 3)
    {

        // 1. Вибірка
        $sql = "select chief1, listagg(id,',') within group (order by id)
from (
    select l.id, l.chief1
        from leads l
        where l.status = ".PERSTATUS_NOTAPPOINTED."
        and l.manager = 0
		and l.leadtype = ".LEADTYPE_COMPLAINT."
        and l.createdate +interval '".$hours."' hour < SYSDATE
        and chief1 != 0
    union
    select l.id, l.chief2 
        from leads l
        where l.status = ".PERSTATUS_NOTAPPOINTED."
        and l.manager = 0
		and l.leadtype = ".LEADTYPE_COMPLAINT."
        and l.createdate +interval '".$hours."' hour < SYSDATE
        and chief2 != 0
        )
group by chief1";


        $rs = $conn->Execute($sql) or die("SQL error: ".$sql);

        //3. run
        include_once (__DIR__."/../../ecommerce/eclib.php");
        while (!$rs->EOF)
        {
            $subject = "Не назначены лиды №№ ".$rs->fields[1]."!";
            $messageText = 'Не назначены ркламации '
                .implode(',',array_map(function ($x){ return '<a href="https://dp.euroizol.com/wdelo30/organizer/ec_peregovor.php?EDITID='.$x.'">'.$x.'</a>';}, explode(',', $rs->fields[1])))
                .'! <br>'
                .'*Прим: открыть лиды нужно в браузере, <ins>предварительно</ins> залогинившись в ДП.';
            $smsText = "Не назначены рекламации №№ ".$rs->fields[1]."!";
            if($rs->fields[0])
            {
                //#21427 R.Y. также смс
                try {
                    notifyUserWithPars($conn, $messageText, $subject, $rs->fields[0], $smsText, true, true, false);
                } catch (\Throwable $e){

                }
            }
            $rs->MoveNext();
        }

    }

    static function notify($conn, $dateTimeZone = null)
    {
        if($dateTimeZone === null)
        {
            $dateTimeZone = new \DateTimeZone("Europe/Kiev");
        }
        $isWorking = GetFieldFromSQL($conn, "select hours".(int)date("d")." from grafics where god = '".date("Y")."' and mes = '".date("m")."' and grafic = ".GRAFIC_MAIN, 0);
        $hour = (new \DateTime("now", $dateTimeZone))->format("H");
        if($isWorking && $hour == 17)
        {
            self::notifyDirsSceduled($conn, 8);
        }
    }

    function getFormFields()
    {
        $result =  array(
            "name" => "Рекламация",
            "sections" => array(
                0 => array(
                    "name" => "Клиент",
                    "fields" => array(
                        0 => array(
                            "name" => "Контактное лицо",
                            "type" => "reference",
                            "reftype" => S_PERSONS,
                            "field" => "persona",
                            "namefield" => "personaname"
                        ),
                        1 => array(
                            "name" => "Название орг.",
                            "type" => "reference",
                            "reftype" => S_FIRMS,
                            "field" => "firma",
                            "namefield" => "firmaname",
                            "card" => "../firms/kartafirm"
                        ),
                        2 => array(
                            "name" => "Телефон",
                            "type" => "string",
                            "field" => "tel"
                        )
                    )
                ),
                1 => array(
                    "name" => "Содержание",
                    "fields" => array(
                        0 => array(
                            "name" => "Материалы",
                            "type" => "string",
                            "field" => "trademarks"
                        ),
                        1 => array(
                            "name" => "Содержание",
                            "type" => "blob",
                            "field" => "conversationcontent"
                        ),
                        2 => array(
                            "name" => "Статус",
                            "type" => "reference",
                            "reftype" => S_PERSTATUS,
                            "field" => "status",
                            "namefield" => "statusname",
                            "onreset" => "checkStatus();"
                        ),
                        3 => array(
                            "name" => "Оператор",
                            "type" => "label",
                            "field" => "useridname"
                        )
                    )
                ),
                2 => array(
                    "name" => "Переадресации",
                    "fields" => array(
                        0 => array(
                            "name" => "Менеджер",
                            "type" => "reference",
                            "reftype" => S_SYSUSERS,
                            "field" => "manager",
                            "namefield" => "managername"
                        ),
                        1 => array(
                            "name" => "Руководитель1",
                            "type" => "reference",
                            "reftype" => S_SYSUSERS,
                            "field" => "chief1",
                            "namefield" => "chief1name"
                        ),
                        2 => array(
                            "name" => "Руководитель2",
                            "type" => "reference",
                            "reftype" => S_SYSUSERS,
                            "field" => "chief2",
                            "namefield" => "chief2name"
                        ),
                        3 => array(
                            "name" => "Кого уведомить",
                            "type" => "reference",
                            "reftype" => S_SYSUSERS,
                            "field" => "notifyperson1",
                            "namefield" => "notifyperson1name"
                        )
                    )
                )
            ),
            "hiddens" => array(
                0 => array(
                    "name" => "userid",
                    "field" => "userid",
                )
            ),
            "scripts" => array(),
            "finderscripts" => array()
        );
        return $result;
    }

    function notifyAllMans($linktext, $manager, $managername, $chiefs, $otherPerson, $mode, $wideDescription = ""){

        $prim = "<br>*Прим: открыть рекламацию нужно в браузере, <ins>предварительно</ins> залогинившись в ДП";
        include_once (__DIR__."/../../ecommerce/eclib.php");

        if($manager) //менеджер
        {
            $text = "Создана рекламация ".$linktext." и назначена на вас. ".$prim.$wideDescription;
            $subject = "Новая рекламация № ".$this->id;
            $textForSMS = "На вас назначена новая рекламация №".$this->id." в почте";
            try {
                notifyUserWithPars($this->conn, $text, $subject, $manager, $textForSMS, true, false, false);
            } catch (\Exception $e)
            {
                //AddToLog($conn, $event, $zaktip, $zakaz, $dokkey, $tovar, $nkompl, $prim = '')
            }
        }
        foreach($chiefs as $chief)
        {
            if($chief) //Руководитель1
            {
                if($mode == "adding") {
                    $text = "Создана рекламация ".$linktext.".".(($manager)?" Назначена на $managername":" Пока не назначена. ").$prim.$wideDescription;
                    $subject = "Новая рекламация № ".$this->id;
                    if (!$manager) $textForSMS = "Новая рекламация № ".$this->id." в почте";
                    else $textForSMS = "Рекламация № ".$this->id." назначен на менеджера";
                } else {
                    $text =  "Рекламация " . $linktext . "." . (($manager) ? " Назначена на $managername" : " Пока не назначена. ") . $prim . $wideDescription;
                    $subject = "Рекламация № ".$this->id." назначена";
                    $textForSMS = "Рекламация № ".$this->id." назначена на менеджера";
                }
                try {
                    notifyUserWithPars($this->conn, $text, $subject, $chief, $textForSMS, true, false, false);
                } catch (\Exception $e)
                {
                    //
                }
            }
        }
        if($otherPerson) //Кого уведомлять
        {
            //sendMessToUser($conn,"Созданный лид ".$linktext.$prim.$wideDescription, "Новый лид ", $otherPerson);
            try {
                notifyUserWithPars($this->conn, "Созданна рекламация " . $linktext . $prim . $wideDescription, "Новая рекламация ", $otherPerson, "", true, false, false);
            } catch (\Exception $e)
            {
                //
            }
        }
    }

    function Add(){
        $this->createdate = Date("d.m.Y H:i");
        if(!$this->status)
        {
            $this->status = PERSTATUS_NOTAPPOINTED;
        }

        if($this->manager)
        {
            $this->appointdate = Date("d.m.Y H:i");
            if($this->status == PERSTATUS_NOTAPPOINTED)
            {
                $this->status = PERSTATUS_NOTPROCESSED;
            }
        }

        if(parent::Add()){
            $str_peregovor = "<a href=\"https://dp.euroizol.com/wdelo30/organizer/lead.php?EDITID=".$this->id."\">$this->id</a>";
            $wideDescription = " <br> Информация по рекламации: <br>Контактное лицо: ".$this->personaname
                ."<br>Название организации: ".$this->firmaname
                ."<br>Телефоны: ".$this->tel
                ."<br>Материалы: ".$this->trademarks
                ."<br>Содержание разговора:<br>".$this->conversationcontent."<br>";
            $this->notifyAllMans($str_peregovor, $this->manager, $this->managername, array($this->chief1, $this->chief2), $this->notifyperson1, "adding", $wideDescription);
        }
    }

    /**
     * @return bool
     * @throws \Exception
     */
    function Update()
    {
        $notifyAgain = false;
        $oldManager = GetFieldFromSQL($this->conn, "select manager from leads where id = ".$this->id, 0);
        if(!$oldManager && $this->manager)
        {
            $this->appointdate = Date("d.m.Y H:i");
            if($this->status == PERSTATUS_NOTAPPOINTED) $this->status = PERSTATUS_NOTPROCESSED;
            $notifyAgain = true;
        }

        try {
            parent::Update();
            if($notifyAgain) {
                $str_peregovor = "<a href=\"https://dp.euroizol.com/wdelo30/organizer/lead.php?EDITID=" . $this->id . "\">$this->id</a>";
                $wideDescription = " <br> Информация по рекламации: <br>Контактное лицо: " . $this->personaname
                    . "<br>Название организации: " . $this->firmaname
                    . "<br>Телефоны: " . $this->tel
                    . "<br>Материалы: " . $this->trademarks
                    . "<br>Содержание разговора:<br>" . $this->conversationcontent . "<br>";
                $this->notifyAllMans($str_peregovor, $this->manager, $this->managername, array($this->chief1, $this->chief2), $this->notifyperson1, "editing", $wideDescription);
            }
            return true;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}