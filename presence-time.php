<?php

    public function presenceTimeAction(){
        $this->checkRights("Пребывание на рабочем месте");
        $this->view->layout()->setLayout("admin.2015");
        $this->addSass("components/admin-schedule/presence-time");
       
        $objects = array(
            1   => "Офис",
            2   => "Пекарня",
            3   => "Касса",
            4   => "Склад"
            );
        $objects_r = array_flip($objects);
       
        $_GET['date'] ? $date = DateTime::createFromFormat('Y-m-d', $_GET['date']) : $date = new DateTime;
        $_GET['object'] ? $object = $_GET['object'] : $object = 1; // по дефолту показываем офис
       
        $parameters = array(
            'limit'     => 999999,
            'offset'    => 0,
            'order'     => 'desc',
            'date'      => $date->format('Y-m-d'),
            //'dateFrom'  => $date->format('Y-m-d'),
            //'dateTo'  => $date->format('Y-m-d'),
            'appkey' => 'FHHI82n0BwW1h03gEtdMozYp1o5BMTqx'
        );
        $url = "https://app.guardsaas.com/reports/events/export?". http_build_query($parameters);
       
        $response = file_get_contents($url);
        $data = json_decode($response);
       
        $result = array();
        foreach($data->items as $item){
            if($item->event == "Доступ Разрешен") continue;
            $id = $item->employeeid;
            $obj_id = $objects_r[$item->object];
            $result[$obj_id][$id]['name'] = $item->employee;
            $result[$obj_id][$id]['object'] = $item->object;
            $time = new DateTime($item->time);
            if($item->direction == 'Вход'){ // за время прихода на работу берётся самый ранний проход работника на объект на этот день
                if($result[$obj_id][$id]['arrival'] == null){
                    $result[$obj_id][$id]['arrival'] = $time->format("H:i");
                }elseif($result[$obj_id][$id]['arrival'] > $time->format("H:i")){
                    $result[$obj_id][$id]['arrival'] = $time->format("H:i");
                }
            }
            if($item->direction == 'Выход'){ // за время ухода с работы берётся самый поздний проход работника на объект на этот день
                if($result[$obj_id][$id]['departure'] == null){
                    $result[$obj_id][$id]['departure'] = $time->format("H:i");
                } elseif($result[$obj_id][$id]['departure'] < $time->format("H:i")){
                    $result[$obj_id][$id]['departure'] = $time->format("H:i");
                }
            }
        }
       
        $this->view->objects = $objects;
        $this->view->object = $object;
        $this->view->response = $data;
        $this->view->date = $date;
        $this->view->result = $result;
    }