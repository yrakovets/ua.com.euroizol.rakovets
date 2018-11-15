<?php
/**
 * @author Rakovets Yurii <rakovets@mizol.com>
 * @ticket{22727}
 * @brief Бизнес-логика
 * @details Собств. здесь вся бизнес-логика работы с лидами
 */

namespace organizer\classes;


use Exception;

class TEcommerceLead extends TLead
{
    //Источник клиента. В самом лиде не хранится. Добавляется в организацию при ее создании
    var $clientsrc = 0;
    var $clientsrcname = "";


    public function __construct()
    {
        $this->leadtype = LEADTYPE_ECOMMERCE;
    }

    /**
     * @param $conn
     * @param $hours
     * @throws Exception
     * @ticket{19556}
     * @brief Уведомляем менеджеров об их заявках
     * @ticket{22072}
     * @brief одно письмо на человека за рассылку на человека
     */
    private static function notifyManagers($conn, $hours)
    {
        // 1. Вибірка менеджерів
        $sql = "select l.manager, man.a_s1 as mannaz, listagg(l.id,',') within group (order by l.id asc)
        from leads l, uniprops man
        where l.manager = man.propcnt 
        and l.status = ".PERSTATUS_NOTPROCESSED."
        and l.leadtype = ".LEADTYPE_ECOMMERCE."
        and l.manager > 0
        and l.appointdate +interval '".$hours."' hour < SYSDATE
        group by l.manager, man.a_s1
        ";

        $rs = $conn->Execute($sql) or die("SQL error: ".$sql);

        //3. run
        include_once (__DIR__."/../../ecommerce/eclib.php");
        while (!$rs->EOF)
        {
            $subject = "Не обработаны лиды №№ ".$rs->fields[2]."!";
            $messageText = 'Не обработаны лиды №№ '
                .implode(',',array_map(function ($x){ return '<a href="https://dp.euroizol.com/wdelo30/organizer/ec_peregovor.php?EDITID='.$x.'">'.$x.'</a>';}, explode(',', $rs->fields[2])))
                .'! <br>'
                .' Просьба приступить к обработке лидов сейчас. Компания может потерять продажу  <br>'
                .'*Прим: открыть лиды нужно в браузере, <ins>предварительно</ins> залогинившись в ДП.';
            $smsText = "Не обработаны лиды №№ ".$rs->fields[2]."!";
            if($rs->fields[0])
            {
                //#21427 R.Y. также смс
                notifyUserWithPars($conn, $messageText, $subject, $rs->fields[0], $smsText, true, true, false);
            }
            $rs->MoveNext();
        }


    }

    /**
     * @param $conn
     * @param int $hours
     * @throws Exception
     * @ticket{19556}
     * @brief Уведомляем руководителей об их неназначенных заявках
     * @ticket{22072}
     * @brief одно письмо на человека за рассылку на человека
     */
    private static function notifyDirsSceduled($conn, $hours = 3)
    {

        // 1. Вибірка
        $sql = "select chief1, listagg(id,',') within group (order by id)
from (
    select l.id, l.chief1
        from leads l
        where l.status = ".PERSTATUS_NOTAPPOINTED."
        and l.manager = 0
		and l.leadtype = ".LEADTYPE_ECOMMERCE."
        and l.createdate +interval '".$hours."' hour < SYSDATE
        and chief1 != 0
    union
    select l.id, l.chief2 
        from leads l
        where l.status = ".PERSTATUS_NOTAPPOINTED."
        and l.manager = 0
		and l.leadtype = ".LEADTYPE_ECOMMERCE."
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
            $messageText = 'Не назначены лиды '
                .implode(',',array_map(function ($x){ return '<a href="https://dp.euroizol.com/wdelo30/organizer/ec_peregovor.php?EDITID='.$x.'">'.$x.'</a>';}, explode(',', $rs->fields[1])))
                .'! <br>'
                .' Просьба приступить к обработке лидов сейчас. Компания может потерять продажу  <br>'
                .'*Прим: открыть лиды нужно в браузере, <ins>предварительно</ins> залогинившись в ДП.';
            $smsText = "Не назначены лиды №№ ".$rs->fields[1]."!";
            if($rs->fields[0])
            {
                //#21427 R.Y. также смс
                notifyUserWithPars($conn, $messageText, $subject, $rs->fields[0], $smsText, true, true, false);
            }
            $rs->MoveNext();
        }

    }

    function IsCorrect()
    {
        if(!$this->manager && !$this->chief1 && !$this->chief2){ return "Не указан ни один ответственный";}
        return "";
    }

    /**
     * @param $linktext string строка с линком для почты
     * @param $manager int
     * @param $managername string
     * @param $chiefs array
     * @param $otherPerson int
     * @param $mode string
     * @param string $wideDescription
     * @fixed #23657
     */
    function notifyAllMans($linktext, $manager, $managername, $chiefs, $otherPerson, $mode, $wideDescription = ""){

        $prim = "<br>*Прим: открыть лид нужно в браузере, <ins>предварительно</ins> залогинившись в ДП";
        include_once (__DIR__."/../../ecommerce/eclib.php");

        if($manager) //менеджер
        {
            $text = "Создан лид ".$linktext." и назначен на вас. ".$prim.$wideDescription;
            $subject = "Новый лид № ".$this->id;
            $textForSMS = "На вас назначен новый лид №".$this->id." в почте";
            try {
                notifyUserWithPars($this->conn, $text, $subject, $manager, $textForSMS, true, true, false);
            } catch (Exception $e)
            {
                //AddToLog($conn, $event, $zaktip, $zakaz, $dokkey, $tovar, $nkompl, $prim = '')
            }
        }
        foreach($chiefs as $chief)
        {
            if($chief) //Руководитель1
            {
                if($mode == "adding") {
                    $text = "Создан лид ".$linktext.".".(($manager)?" Назначен на $managername":" Пока не назначен. ").$prim.$wideDescription;
                    $subject = "Новый лид № ".$this->id;
                    if (!$manager) $textForSMS = "Новый лид № ".$this->id." в почте";
                    else $textForSMS = "Лид № ".$this->id." назначен на менеджера";
                } else {
                    $text =  "Лид " . $linktext . "." . (($manager) ? " Назначен на $managername" : " Пока не назначен. ") . $prim . $wideDescription;
                    $subject = "Лид № ".$this->id." назначен";
                    $textForSMS = "Лид № ".$this->id." назначен на менеджера";
                }
                try {
                    notifyUserWithPars($this->conn, $text, $subject, $chief, $textForSMS, true, true, false);
                } catch (\Exception $e)
                {
                    //
                }
            }
        }
        if($otherPerson) //Кого уведомлять
        {
            try {
                notifyUserWithPars($this->conn, "Созданный лид " . $linktext . $prim . $wideDescription, "Новый лид ", $otherPerson, "", true, false, false);
            } catch (\Exception $e)
            {
                //
            }
        }
    }

    /**
     * @param $conn
     * @param null $dateTimeZone
     * @ticket{19556}
     * @brief Уведомляем руководителей об их неназначенных заявках
     */
    private static function notifyDirsPromtly($conn, $dateTimeZone = null){
        // 1. Вибірка
        if($dateTimeZone == null)
        {
            $dateTimeZone = new \DateTimeZone("Europe/Kiev");
        }

        $dateTo = new \DateTime(" -1 hour", $dateTimeZone);
        if($dateTo->format("H") == 9 )
        {
            include_once (__DIR__."/../../classes/tgrafic.php");
            $grafic = new \TGrafic();
            $grafic->conn = $conn;
            $grafic->grafic = 1088;
            $dateFrom = new \DateTime($grafic->DayPlusRab(date("d.m.Y H:i"), -1)." 17:00", $dateTimeZone);
            $grafic->DayPlusRab(date("d.m.Y H:i"), -1);
        } else {
            $dateFrom = new \DateTime(" -2 hour", $dateTimeZone);
        }

        $sql = "select l.id, l.chief1, ruk1.a_s1, ruk1.a_s4, l.chief2, ruk2.a_s1, ruk2.a_s4, l.createdate, l.appointdate
from leads l, uniprops ruk1, uniprops ruk2 
where l.chief1 = ruk1.propcnt 
and l.chief1 = ruk2.propcnt
and l.status = ".PERSTATUS_NOTAPPOINTED." 
and l.manager = 0
and l.leadtype = ".LEADTYPE_ECOMMERCE."
and l.createdate > to_date('".$dateFrom->format("Y-m-d H:00")."','YYYY-MM-DD HH24-MI')
and l.createdate <= to_date('".$dateTo->format("Y-m-d H:00")."','YYYY-MM-DD HH24-MI')";

        //2. Функція
        if(!function_exists("sendMessToUser"))
        {
            function sendMessToUser($conn, $text, $subject, $user)
            {
                $crm = new \TMessage();
                $crm->conn = $conn;
                $crm->reciever = $user;
                $crm->sender = C_ADMIN;
                $crm->soderv = $text;
                $crm->subject = $subject;
                $s = $crm->Add();
                if ($s) {
                    AddToErrorLog($conn, " Ошибка при отправке письма о новом переговоре: ".$s);
                }
            }
        }

        //3. run
        $rs = $conn->Execute($sql) or die("SQL error: ".$sql);
        while (!$rs->EOF)
        {
            $messageText = 'Лид <a href="https://dp.euroizol.com/wdelo30/organizer/ec_peregovor.php?EDITID='.$rs->fields[0].'" target="_blank">'
                .$rs->fields[0].'</a> созданный '.date("d.m.Y H:i", strtotime($rs->fields[7]))
                .'не был назначен Вами на ответственного менеджера. Просьба назначить ответственного за лид сейчас. Компания может потерять продажу<br>'
                .'*Прим: открыть ссылку нужно в браузере ,где сзалогинились в ДП';

            if($rs->fields[1]) //Руководитель1
            {
                sendMessToUser($conn, $messageText, "Неназначенный лид ", $rs->fields[1]);
            }
            if($rs->fields[4]) //Руководитель2
            {
                sendMessToUser($conn, $messageText, "Неназначенный лид ", $rs->fields[4]);
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
        if ($hour >= 10 and $hour <= 18 and $isWorking)
        {
            self::notifyDirsPromtly($conn, $dateTimeZone);
        }
        if($hour == 17 and $isWorking)
        {
            try {
                self::notifyManagers($conn, 8);
            } catch (Exception $e) {
                return $e->getMessage();
            }
        }
        if(in_array($hour, array(13,17)) && $isWorking)
        {
            try {
                self::notifyDirsSceduled($conn, 3);
            } catch (Exception $e) {
                return $e->getMessage();
            }
        }

        return "";
    }

    /**
     * @return bool
     * @throws Exception
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
        if(!in_array($this->status, array(PERSTATUS_NOTAPPOINTED, PERSTATUS_NOTPROCESSED)) && $this->resolvedate == "01.01.1970")
        {
            $this->resolvedate = Date("d.m.Y H:i");
        }
        if(parent::Update())
        {
            if($notifyAgain)
            {
                $str_peregovor = "<a href=\"https://dp.euroizol.com/wdelo30/organizer/lead.php?EDITID=".$this->id."\">$this->id</a>";
                $wideDescription = " <br> Информация по лиду: <br>Контактное лицо: ".$this->personaname
                    ."<br>Название организации: ".$this->firmaname
                    ."<br>Телефоны: ".$this->tel
                    ."<br>Интересующие материалы: ".$this->trademarks
                    ."<br>Содержание разговора:<br>".$this->conversationcontent."<br>";
                $this->notifyAllMans($str_peregovor, $this->manager, $this->managername, array($this->chief1, $this->chief2), $this->notifyperson1, "editing", $wideDescription);
            }
            return true;
        }
        return false;
    }

    /**
     * @return bool
     * @throws Exception
     */
    function Read()
    {
        if(parent::Read())
        {
            list($this->clientsrc, $this->clientsrcname) = GetFieldsFromSQL($this->conn,
                "select u.propcnt, u.a_s1 from firms f inner join uniprops u on f.clientsrc = u.propcnt 
                  where f.firma = ".$this->firma, array(0, ""));

            return true;
        }
        return false;
    }

    /**
     * @return bool
     * @throws Exception
     */
    function Add()
    {
        //Для логики создания организации прямо с формы лида
        //собсно, если не накосячили, поле названия без выбранной орг. доступное для ручного ввода - тогда нужно добавлять
        //или выбраннное значение из словаря - тогда ридонли и есть значение в firma
        $res= $this->isCorrect();
        if($res) {
            throw new Exception("Ошибка добавления: ".$res);
        }

        $this->userid = getdilertip();
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

        if(!$this->firma && $this->firmaname)
        {
            include_once (__DIR__."/../../classes/tpersona.php");
            $firm = new \TFirma();
            $firm->conn = $this->conn;
            $firm->tip = FIRMTIP_POTENTIAL;
            $firm->sokrash = $this->firmaname;
            $firm->polnoe = $this->firmaname;
            $firm->manager = getdilertip();
            $firm->tel = $this->tel;
            $firm->clientsrc = $this->clientsrc;
            $firm->sekret = SEKRET_EUROIZOL;
            $s = $firm->Add();
            if($s) {
                throw new Exception("Firm adding error: ".$s);
            }
            if (!$firm->Read()) throw new Exception("Firm reading error");
            $this->firma = $firm->firma;
            $this->firmaname = $firm->sokrash;
        }
        // и контактного лица аналогично
        if(!$this->persona && $this->personaname)
        {
            include_once (__DIR__."/../../classes/tpersona.php");
            $contact = new \TPersona();
            $contact->conn = $this->conn;
            $contact->familia = $this->personaname;
            $contact->tel = $this->tel;
            $contact->filial = $this->firma;
            $s = $contact->Add();
            if($s) {
                throw new Exception("Contacter adding error: ".$s);
            }
            $this->persona = $contact->persona;
        }

        if( parent::Add()) {
            $str_peregovor = "<a href=\"https://dp.euroizol.com/wdelo30/organizer/lead.php?EDITID=" . $this->id . "\">$this->id</a>";
            $wideDescription = " <br> Информация по лиду: <br>Контактное лицо: " . $this->personaname
                . "<br>Название организации: " . $this->firmaname
                . "<br>Телефоны: " . $this->tel
                . "<br>Интересующие материалы: " . $this->trademarks
                . "<br>Содержание разговора:<br>" . $this->conversationcontent . "<br>";
            $this->notifyAllMans($str_peregovor, $this->manager, $this->managername, array($this->chief1, $this->chief2), $this->notifyperson1, "adding", $wideDescription);
            return true;
        }
        return false;
    }

    /**
     * @brief Здесь формируются данные для єлементов формы.
     * @details Набор полей, хидденов, скриптов - все здесь. обработка в lead.php
     * @return array
     *
     */
    function getFormFields()
    {
        //Если добавление
        if(!$this->id){
            $fieldClientSrc = array(
                "name" => "Источник клиента",
                "type" => "reference",
                "reftype" => S_FIRMSOURCE,
                "field" => "clientsrc",
                "namefield" => "clientsrcname"
            );
            $fieldContact = array(
                "name" => "Контактное лицо",
                "type" => "string",
                "field" => "personaname"
            );
            if(isBootstrap()){
                $dopBtnFirma = "<div class=\"input-group-btn group-btn-delopro\">
            <button class=\"btn btn-default input-sm input-delopro btn-delopro\" type=\"button\" onclick=\"clearFirma();\"><i class=\"fa fa-times\" aria-hidden=\"true\"></i></button>
            </div>
            <div class=\"input-group-btn group-btn-delopro\">
            <button class=\"btn btn-default input-sm input-delopro btn-delopro\" type=\"button\" onclick=\"selectFirma();\" title=\"Все\"><i class=\"fa fa-search\" aria-hidden=\"true\"></i></button>
            </div>";

                $dopBtnTel = "<div class=\"input-group-btn group-btn-delopro\">
            <button class=\"btn btn-default input-sm input-delopro btn-delopro\" type=\"button\" onclick=\"checkNumber();\" title=\"Все\"><i class=\"fa fa-binoculars\" aria-hidden=\"true\"></i></button>
            </div>";
            } else {
                $dopBtnFirma = "<input onclick=\"document.DATER.firma.value=''; document.DATER.firmaNAZ.value='-';document.DATER.firmaname.value='';\" style=\"width:15px;min-width:20px;padding-left:2px;padding-right:2px;\" value=\"x\" type=\"button\">
                    <input onclick=\"SelectFirma();\" title=\"Все орг.\" style=\"width:15px;min-width:20px;padding-left:2px;padding-right:2px;\" value=\"...\" type=\"button\">
                    ";
                $dopBtnTel = "<input onclick=\"CheckNumber();\" title=\"Найти по номеру\" style=\"width:15px;min-width:20px;padding-left:2px;padding-right:2px;\" value=\"00\" type=\"button\">";
            }
            $fieldFirma = array(
                "name" => "Организация",
                "type" => "string",
                "field" => "firmaname",
                "dop" => $dopBtnFirma
            );
            $fieldTel = array(
                "name" => "Телефон",
                "type" => "string",
                "field" => "tel",
                "dop" => $dopBtnTel
            );
        } else
        {
            $fieldClientSrc = array(
                "name" => "Источник клиента",
                "type" => "label",
                "field" => "clientsrcname"
            );
            $fieldContact = array(
                "name" => "Контактное лицо",
                "type" => "reference",
                "reftype" => S_PERSONS,
                "field" => "persona",
                "namefield" => "personaname"
            );
            $fieldFirma = array(
                "name" => "Название орг.",
                "type" => "reference",
                "reftype" => S_FIRMS,
                "field" => "firma",
                "namefield" => "firmaname",
                "card" => "../firms/kartafirm"
            );
            $fieldTel = array(
                "name" => "Телефон",
                "type" => "string",
                "field" => "tel"
            );
        }

        $result =  array(
            "name" => "Лид",
            "sections" => array(
                0 => array(
                    "name" => "Клиент",
                    "fields" => array(
                        0 => $fieldContact,
                        1 => $fieldFirma,
                        2 => $fieldTel,
                        3 => $fieldClientSrc
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
                )/*,
                3 => array(
                    "name" => "Даты",
                    "fields" => array(
                        0 => array(
                            "name" => "",
                            "type" => "label"
                        )
                    )
                )*/
            ),
            "hiddens" => array(
                0 => array(
                    "name" => "userid",
                    "field" => "userid",
                )
            ),
            "scripts" => array()
        );

        if(!$this->id)
        {
            $result["hiddens"][] = array(
                "name" => "firma",
                "field" => "firma"
            );
        } else
        {
            $result["sections"][1]["fields"][] = array(
                "name" => "Существующий клиент",
                "type" => "reference",
                "reftype" => S_FIRMS,
                "field" => "existingclient",
                "namefield" => "existingclientname"
            );
        }
        $result["scripts"][] = '
function selectFirma()
{
     var a = new tfinder();
     a.sql = "  select f.firma, f.sokrash, f.polnoe, f.okpo, m.a_s1 as managernaz, r.a_s1 as regionnaz ";
     a.sql += " from firms f, uniprops m, uniprops r ";
     a.sql += " where f.manager = m.propcnt  ";
     a.sql += " and f.gorod = r.propcnt ";
     if(document.DATER.firmaname.value.length > 0){
        a.sql += " and (f.sokrash like \'%" + document.DATER.firmaname.value + "%\' or  f.polnoe like \'%" + document.DATER.firmaname.value + "%\')";
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
	document.DATER.firma.value = firmaValue;
	document.DATER.firmaname.value = firmaName;
	$("#firmaname")[0].setAttribute("readonly", true);
}
function clearFirma()
{
    document.DATER.firma.value=\'\'; 
    document.DATER.firmaname.value=\'\'; 
    $("#firmaname")[0].removeAttribute("readonly");
}
function checkNumber()
{
    $.get("classes/getorgbynumber.php?TEL=" + document.DATER.tel.value, onAjaxSuccess);
    function onAjaxSuccess(inputData){
        var jsonData = JSON.parse(inputData);
        setFirma(jsonData[\'firma\'], jsonData[\'firmaname\'])
        document.DATER.clientsrc.value = jsonData[\'clientsrc\'];
        document.DATER.clientsrcNAZ.value = jsonData[\'clientsrcname\'];
    }
}
function changeStatus(val, valname)
{
    document.DATER.status.value = val;
    document.DATER.statusNAZ.value = valname;
    checkStatus();
}
function checkStatus()
{
    if($("input[name=status]").val() == '.PERSTATUS_CLIENTEXISTS.')
  {
    $("#existingclientNAZ").closest("tr").show();  
  }  else {
    $("#existingclientNAZ").closest("tr").hide();  
  }
}

';
        if($this->id){
            $result["scripts"][] = '
$(document).ready(function(){        
    if($("input[name=status]").val() != '.PERSTATUS_CLIENTEXISTS.'){                
       $("#existingclientNAZ").closest("tr").hide();            
    }        
    $(\'#statusNAZ\')[0].onchange =checkStatus;
});
        ';
        }

        $result["finderscripts"][] = '
if(propnum == 722) s += \'&TFUNCTION=checkStatus\';   ';
        return $result;
    }

}