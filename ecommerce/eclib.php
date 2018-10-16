<?php

/**
 * @author Rakovets Yurii
 * @ticket{21427}
 * @brief расширение функционала для уведомлений
 */


/**
 * @param $conn
 * @param string $text текст для почты/вайбера и пр.
 * @param string $subject
 * @param int $user
 * @param string $textForSMS коорктий текст без тегов
 * @param bool $senEmail
 * @param bool $sendSMS
 * @param bool $sendViber
 * @throws Exception
 * @todo отправка на Viber
 */
function notifyUserWithPars($conn, $text, $subject, $user, $textForSMS = "", $senEmail = true, $sendSMS = false, $sendViber = false)
{
    if($senEmail)
    {
        sendEmailToUser($conn, $text, $subject, $user);
    }
    if ($sendViber)
    {
        throw new Exception("Отправка на Viber пока не работает");
    }
    if($sendSMS)
    {
        include_once("../classes/ei_Tools.php");
        $number = GetFieldFromSQL($conn, "select pp.a_s1 from persprops pp, uniprops u where pp.a_b1 = '+' and pp.a_b4 = '+' and pp.keymain = u.a_r2 and pp.proptip = ".PERSPROPS_WORKPHONE." and u.propcnt = ".$user, "");
        if($number)
        {
            Tools::sendSMSMessage(substr($number, strpos($number, "380")), Tools::to_utf8($textForSMS), getdilertip());
        } else {
            $userName = GetFieldFromSQL($conn, "select a_s1 from uniprops where propcnt = ".$user, "");
            sendEmailToUser($conn, "Не отправлен СМС пользователю ".$user." ".$userName." <br> ".$textForSMS, "Ошибка отправки СМС", getdilertip());
        }
    }
}

/**
 * @param $conn
 * @param string $text
 * @param string $subject
 * @param int $user
 * @param string $textForSMS
 * @param int $messageType
 * @throws Exception
 * @brief заготовка, для отправки по установленным параметрам
 * @todo собсно реализовать чтение параметров
 */
function notifyUser($conn, $text, $subject, $user, $textForSMS = "", $messageType = 0)
{
    notifyUserWithPars($conn, $text, $subject, $user, $textForSMS, false,false, false);
}

/**
 * @param $conn
 * @param string $text
 * @param string $subject
 * @param $user
 */
function sendEmailToUser($conn, $text, $subject, $user)
{
    $crm = new TMessage();
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
