<?php
/**
 * @author Rakovets Yurii <rakovets@mizol.com>
 * @ticket{22727}
 * @date 25.10.2018
 * @brief “ехническа€ логика
 * @details “олько техническа€ логика. бизнес-логика - в соотв. классах.
 */

namespace organizer\classes;
use Exception;

abstract class TLead
{
    var $conn;
    var $id = 0;
    var $userid = 0; // оператор
    var $useridname = "";
    var $leadtype = 0;
    var $status = 0;
    var $statusname = "";
    var $firma = 0;
    var $firmaname = "";
    var $tel = "";
    var $conversationcontent = "";
    var $persona = 0;
    var $personaname = "";
    var $manager = 0;
    var $managername = "";
    var $chief1 = 0;
    var $chief1name = "";
    var $chief2 = 0;
    var $chief2name = "";
    var $chief3 = 0;
    var $chief3name = "";
    var $notifyperson1 = 0;
    var $notifyperson1name = "";
    var $notifyperson2 = 0;
    var $notifyperson2name = "";
    var $napr = 0;
    var $naprname = 0;
    var $existingclient = 0;
    var $existingclientname = "";
    var $trademarks = "";
    var $createdate = "01.01.1970";
    var $appointdate = "01.01.1970";
    var $resolvedate = "01.01.1970";

    /**
     * @return bool
     * @throws Exception
     */
    function Read()
    {
        if(!$this->id) throw new Exception("Id isn't passed");
        global $ADODB_FETCH_MODE;
        $fetchMode = $ADODB_FETCH_MODE;
        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
        $sql = " select l.id , l.userid, l.leadtype, l.status, l.firma, l.tel, l.conversationcontent, l.persona, 
          l.manager, l.chief1, l.chief2, l.chief3, l.notifyperson1, l.notifyperson2, l.napr, 
          l.existingclient, l.trademarks, l.createdate, l.appointdate, l.resolvedate, 
          f.sokrash as firmanaz, 
          p.fio as personaname,
          m.a_s1 as managername,
          c1.a_s1 as chief1name,
          c2.a_s1 as chief2name,
          c3.a_s1 as chief3name,
          nt1.a_s1 as notifyperson1name,
          nt2.a_s1 as notifyperson2name,
          napr.a_s1 as naprname,
          xc.sokrash as existingclientname,
          u.a_s1 as useridname,
          s.a_s1 as statusname
          from leads l
            inner join firms f 
              on f.firma = l.firma
            inner join persons p
              on p.persona = l.persona
            inner join uniprops m
              on m.propcnt = l.manager
            inner join uniprops c1 
              on c1.propcnt = l.chief1
            inner join uniprops c2 
              on c2.propcnt = l.chief2
            inner join uniprops c3 
              on c3.propcnt = l.chief3
            inner join uniprops nt1 
              on nt1.propcnt = l.notifyperson1
            inner join uniprops nt2 
              on nt2.propcnt = l.notifyperson2
            inner join uniprops napr 
              on napr.propcnt = l.napr
            inner join firms xc 
              on xc.firma = l.existingclient
            inner join uniprops u
              on u.propcnt = l.userid
            inner join uniprops s
              on s.propcnt = l.status
          where id = ".$this->id;


        $fields = $this->conn->Execute($sql)->fields;

        $this->id = $fields["ID"];
        if(!$this->id){
            $ADODB_FETCH_MODE = $fetchMode;
            return false;
        }
        $this->userid = $fields["USERID"];
        $this->useridname = $fields["USERIDNAME"];
        $this->leadtype = $fields["LEADTYPE"];
        $this->status = $fields["STATUS"];
        $this->statusname = $fields["STATUSNAME"];
        $this->firma = $fields["FIRMA"];
        $this->firmaname = $fields["FIRMANAZ"];
        $this->tel = $fields["TEL"];
        $this->conversationcontent = $fields["CONVERSATIONCONTENT"];
        $this->persona = $fields["PERSONA"];
        $this->personaname = $fields["PERSONANAME"];
        $this->manager = $fields["MANAGER"];
        $this->managername = $fields["MANAGERNAME"];
        $this->chief1 = $fields["CHIEF1"];
        $this->chief1name = $fields["CHIEF1NAME"];
        $this->chief2 = $fields["CHIEF2"];
        $this->chief2name = $fields["CHIEF2NAME"];
        $this->chief3 = $fields["CHIEF3"];
        $this->chief3name = $fields["CHIEF3NAME"];
        $this->notifyperson1 = $fields["NOTIFYPERSON1"];
        $this->notifyperson1name = $fields["NOTIFYPERSON1NAME"];
        $this->notifyperson2 = $fields["NOTIFYPERSON2"];
        $this->notifyperson2name = $fields["NOTIFYPERSON2NAME"];
        $this->napr = $fields["NAPR"];
        $this->naprname = $fields["NAPRNAME"];
        $this->existingclient = $fields["EXISTINGCLIENT"];
        $this->existingclientname = $fields["EXISTINGCLIENTNAME"];
        $this->trademarks = $fields["TRADEMARKS"];
        $this->createdate = (new \DateTime($fields["CREATEDATE"]))->format("d.m.Y H:i");
        $this->appointdate = (new \DateTime($fields["APPOINTDATE"]))->format("d.m.Y H:i");
        $this->resolvedate = (new \DateTime($fields["RESOLVEDATE"]))->format("d.m.Y H:i");

        $ADODB_FETCH_MODE = $fetchMode;
        return true;
    }

    /**
     * @return bool
     */
    function Add()
    {
        $this->id = 0;
        $maxid = GetFieldFromSQL($this->conn, "select max(id) from leads");

        for($i = 1; $i < 100;  $i++)
        {
            $sql = "INSERT INTO LEADS (ID, USERID, LEADTYPE, STATUS, FIRMA, TEL, CONVERSATIONCONTENT, PERSONA, 
                MANAGER, CHIEF1, CHIEF2, CHIEF3, NOTIFYPERSON1, NOTIFYPERSON2, NAPR, EXISTINGCLIENT, TRADEMARKS, CREATEDATE, APPOINTDATE, RESOLVEDATE)
              VALUES (
              ".((int)$maxid + $i).",
              ".(int)$this->userid.",
              ".(int)$this->leadtype.",
              ".(int)$this->status.",
              ".(int)$this->firma.",
              q'[".$this->tel."]',
              q'[".$this->conversationcontent."]',
              ".(int)$this->persona.",
              ".(int)$this->manager.",
              ".(int)$this->chief1.",
              ".(int)$this->chief2.",
              ".(int)$this->chief3.",
              ".(int)$this->notifyperson1.",
              ".(int)$this->notifyperson2.",
              ".(int)$this->napr.",
              ".(int)$this->existingclient.",
              q'[".$this->trademarks."]',
              ".DateTimeFormat($this->conn, $this->createdate).",
              ".DateTimeFormat($this->conn, $this->appointdate).",
              ".DateTimeFormat($this->conn, $this->resolvedate)."
              )
            ";
            if ($this->conn->Execute($sql))
            {
                $this->id = (int)$maxid + $i;
                break;
            }
        }
        return $this->id > 0;
    }

    /**
     * @return bool
     * @throws Exception
     */
    function Update()
    {
        if ($this->id == 0) throw new Exception("ID isn't passed");

        $sql = "UPDATE LEADS 
        SET 
          USERID = ".(int)$this->userid.",
          LEADTYPE = ".(int)$this->leadtype.",
          STATUS =  ".(int)$this->status.",
          FIRMA = ".(int)$this->firma.",
          TEL = q'[".$this->tel."]',
          CONVERSATIONCONTENT = q'[".$this->conversationcontent."]',
          PERSONA = ".(int)$this->persona.",
          MANAGER = ".(int)$this->manager.",
          CHIEF1 = ".(int)$this->chief1.",
          CHIEF2 = ".(int)$this->chief2.",
          CHIEF3 = ".(int)$this->chief3.",
          NOTIFYPERSON1 = ".(int)$this->notifyperson1.",
          NOTIFYPERSON2 = ".(int)$this->notifyperson2.",
          NAPR = ".(int)$this->napr.",
          EXISTINGCLIENT = ".(int)$this->existingclient.",
          TRADEMARKS = q'[".$this->trademarks."]',
          CREATEDATE = ".DateTimeFormat($this->conn, $this->createdate).",
          APPOINTDATE = ".DateTimeFormat($this->conn, $this->appointdate).",
          RESOLVEDATE = ".DateTimeFormat($this->conn, $this->resolvedate)."
        WHERE ID = ".$this->id."  
          
        ";

        $this->conn->Execute($sql) or die("sql error: ".$sql);

        return true;
    }

    /**
     * @return bool
     * @throws Exception
     */
    function Delete()
    {
        if ($this->id == 0) throw new Exception("ID isn't passed");

        $sql = "delete from leads where id = ".$this->id;

        return $this->conn->Execute($sql);
    }


    abstract function IsCorrect();

    static function notify($conn, $dateTimeZone = null){
        if($dateTimeZone == null)
        {
            $dateTimeZone = new \DateTimeZone("Europe/Kiev");
        }
        TEcommerceLead::notify($conn, $dateTimeZone);
        TAgroLead::notify($conn, $dateTimeZone);
        TComplaintLead::notify($conn,$dateTimeZone);
    }

    abstract function getFormFields();


}