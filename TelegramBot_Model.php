<?php

// класс для управления Telegram-ботом. С помощью него осуществляется оповещение о поступивших заказах


class Application_Model_TelegramBot extends Leto_Model
{   
    
    const MOSCOW_CHAT   = -1001139993348;   // ID московского чата (да, у чатов и каналов в TG отрицательные id)
    const KIEV_CHAT     = -1001140686658;   // ID киевского чата 
    const TALLIN_CHAT   = -1001133246694;   // ID таллинского чата 
    const VEIN_CHAT     = -1001132015471;   // ID венского чата
    const TOKEN         = "**********************************";
    const API_URL       = "https://api.telegram.org/bot";


    public function __construct()
    {
        $this->city_chats = array(
            1   =>  self::MOSCOW_CHAT,
            2   =>  self::KIEV_CHAT,
            3   =>  self::TALLIN_CHAT,
            4   =>  self::VEIN_CHAT
        );
        
        $this->time_zone = array(
            1   =>  "Europe/Moscow",
            2   =>  "Europe/Kiev",
            3   =>  "Europe/Tallinn",
            4   =>  "Europe/Vienna"
        );
    }
    
    public function notifyOrder($order){
        $timezone = new DateTimeZone($this->time_zone[$order['order_city_id']]);
        $date = new DateTime($order['order_added'], $timezone);
        
        $text = "Unconfirmed order * i".$order['order_city_no']." * ". PHP_EOL ."Admission time * ".$date->format('d.m H:i')." *";
        
        $this->sendMessage($this->city_chats[$order['order_city_id']], $text);
    }
    
    public function confirmOrder($cityId, $order_no, $name, $order_added){
        $timezone = new DateTimeZone($this->time_zone[$cityId]);
        $added = new DateTime($order_added);
        $confirm = new DateTime;
        $interval = $confirm->diff($added);
        $delay = $interval->format("%i");
        $after = "(after $delay minutes)";
        $by = "by $name";
        $time = new DateTime(null, $timezone);
        $at = "at ".$time->format("H:i");
        $text = "Order * i$order_no * confirmed $by $at $after";
        $this->sendMessage($this->city_chats[$cityId], $text);
    }
    
    public function notConnected($cityId, $order_no, $name, $order_added){
        $timezone = new DateTimeZone($this->time_zone[$cityId]);
        $added = new DateTime($order_added);
        $dialing = new DateTime;
        $interval = $dialing->diff($added);
        $delay = $interval->format("%i");
        $after = "(after $delay minutes)";
        $by = "by $name";
        $text = "Order * i$order_no * is dialing $by $after";
        $this->sendMessage($this->city_chats[$cityId], $text);
    }
    
    private function sendMessage($id, $text){
        $query = self::API_URL.self::TOKEN."/sendMessage";
        
        $params = array(
//            'chat_id'   =>  5844335, // мой id'шник на время разработки
            'chat_id'   =>  $id,
            'text'      =>  $text,
            'parse_mode'=>  'Markdown'
        );
        
        $opts = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded' . PHP_EOL,
                'content' => http_build_query($params),
            )
        );
        
        $context = stream_context_create($opts);
        try{
            file_get_contents($query, false, $context);
        } catch (Exception $e) {
            $errorMailModel = new Application_Model_ErrorMail('', "TG-bot error", $e->getMessage());
            $errorMailModel->send();
        }
    }
}