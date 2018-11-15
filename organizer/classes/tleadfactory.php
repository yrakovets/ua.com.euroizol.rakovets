<?php
/**
 * @author Rakovets Yurii <rakovets@mizol.com>
 * @ticket{22727}
 * @date 25.10.2018
 * @brief Бизнес-логика Класса Lead
 */

namespace organizer\classes;
use Exception;


class TLeadFactory
{
    /**
     * @brief 
     * @param $conn
     * @param $leadType int тип лида
     * @return null|TAgroLead|TComplaintLead|TEcommerceLead
     * @throws Exception
     */
    static function createLeadByType($conn, $leadType)
    {
        $lead = null;
        switch ($leadType)
        {
            case LEADTYPE_ECOMMERCE:
                include_once (__DIR__."/tecommercelead.php");
                $lead =  new TEcommerceLead();
                break;
            case LEADTYPE_COMPLAINT:
                include_once (__DIR__."/tcomplaintlead.php");
                $lead = new TComplaintLead();
                break;
            case LEADTYPE_AGRO:
                include_once (__DIR__."/tcomplaintlead.php");
                $lead = new TAgroLead();
                break;
            default:
                throw new Exception("Unknown lead type");
        }
        $lead->conn = $conn;
        return $lead;
    }

    /**
     * @brief Фабрика для чтения лида
     * @param $conn
     * @param $id
     * @return null|TAgroLead|TComplaintLead|TEcommerceLead
     */
    static function readLead($conn, $id)
    {
        $leadType = GetFieldFromSQL($conn, "select leadtype from leads where id = ".$id);
        try {
            $lead = self::createLeadByType($conn, $leadType);
            $lead->id = $id;
            $lead->Read();
        } catch (Exception $e) {
            die($e->getMessage());
        }
        return $lead;
    }

}