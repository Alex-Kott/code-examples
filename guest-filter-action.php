<?php
    public function guestFilterAction(){
        if (!$this->myAcl->isAllowed('База Поиск')) {
            die('Access Denied');
        }
        
        $this->view->headScript()->appendFile('/js3/secondary/moment.js');

        $this->view->layout()->setLayout('admin.2015');
        
        if(isset($_GET['cityId'])){
            $cityId = $_GET['cityId'];
        } else {
            $cityId = 1;
        }
        
        if(isset($_POST['form'])){
            $form = $_POST['form'];
        }
        
        if($form['date'] != ''){
            if(isset($_POST['sDate'])){
                $sDate = mysql_real_escape_string($_POST['sDate']);
                $form['sDate'] = $sDate;
            }

            if(isset($_POST['eDate'])){
                $eDate = mysql_real_escape_string($_POST['eDate']);
                $form['eDate'] = $eDate;
            }
        }
        
        if($form['all'] == '1'){
            $form['sDate'] = '';
            $form['eDate'] = '';
        }
        
        $baseModel = new Application_Model_Base;
        $form_template = $baseModel->getGuestFilterFormOptions($cityId);
        if(isset($form)){
            if(!isset($form['page'])){
                $form['page'] = 0;
            } 
            list($results, $rows_qty, $cart_count) = (new Application_Model_Guest)->guestFilter($form);
            
            if($amount > 0){
                foreach ($results as $rowId => $row) {
                    if (!isset($row['order_id'])) {
                        unset($results[$rowId]);
                    }
                }
            }
        }

        if($sDate && $eDate){
            $s = DateTime::createFromFormat('d.m.Y', $sDate);
            $e = DateTime::createFromFormat('d.m.Y', $eDate);
            $start = $s->format('Y-m-d');
            $end = $e->format('Y-m-d');
        }
        
        $this->view->countOrders = (new Application_Model_Order)->countOrdersByDate($cityId, $start, $end); 
        $this->view->countGuests = (new Application_Model_Order)->countGuestsByDate($cityId, $start, $end);
        $this->view->morses = $form_template['morses'];
        $this->view->mark = $form_template['mark'];
        $this->view->remoteness = $form_template['remoteness'];
        $this->view->companies = $form_template['companies'];
        $this->view->pies = $form_template['pies'];
        $this->view->rows = $form_template['rows'];
        $this->view->type = $form_template['type'];
        $this->view->source = $form_template['source'];
        $this->view->sDate = $sDate;
        $this->view->eDate = $eDate;
        $this->view->form = $form;
        $this->view->results = $results;
        $this->view->rows_qty = $rows_qty;
        $this->view->cityId = $cityId;
        $this->view->cart_count = $cart_count;
    }