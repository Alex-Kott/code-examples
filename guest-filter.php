<?php
    public function guestFilter($form){
        $where = [];
        $sDate = $form['sDate'];
        $eDate = $form['eDate'];
        $rows = $form['rows'];
        
        $concat = '';
        $group_by = 'GROUP BY o.order_phone1';
        
        if($form['sDate'] != ""){
            $sDate = mysql_real_escape_string($form['sDate']);
            $date_range[] = "o.order_date >= STR_TO_DATE('$sDate', '%d.%m.%Y')";
        }
        
        if($form['eDate'] != ''){
            $eDate = mysql_real_escape_string($form['eDate']);
            $date_range[] = "o.order_date <= STR_TO_DATE('$eDate', '%d.%m.%Y')";
        }
        
        if($form['street'] != '' || $form['dom'] || $form['korpus'] || $form['podezd'] || $form['etag'] || $form['kvartira']){
            $street = $form['street']; // я понимаю, что за транслит нужно убивать, но колонки в nikolay_order именно так и называются
            $dom = $form['dom'];
            $korpus = $form['korpus'];
            $podezd = $form['podezd'];
            $etag = $form['etag'];
            $kvartira = $form['kvartira'];
            $where[] = "(o.order_street LIKE '%$street%' AND 
                         o.order_dom LIKE '%$dom%' AND 
                         o.order_korpus LIKE '%$korpus%' AND 
                         o.order_podezd LIKE '%$podezd%' AND
                         o.order_etag LIKE '%$etag%' AND
                         o.order_kvartira LIKE '%$kvartira%')";
        }
        
        if($form['name'] != ''){
            $name = $form['name'];
            $where[] = "(o.order_first_name LIKE '%$name%' OR o.order_first_name2 LIKE '%$name%')";
        }
        
        if($form['surname'] != ''){
            $surname = $form['surname'];
            $where[] = "(o.order_last_name LIKE '%$surname%' OR o.order_last_name2 LIKE '%$surname%')";
        }
        
        if($form['phone'] != ''){
            $phone = $form['phone'];
            $where[] = "(o.order_phone1 LIKE '%$phone%' OR o.order_phone2 LIKE '%$phone%')";
        }
        
        if($form['email'] != ''){
            $email = $form['email'];
            $where[] = "o.order_email LIKE '%$email%'";
        }
        
        if($form['comment'] != ''){
            $comment = $form['comment'];
            $where[] = "o.order_comment LIKE '%$comment%'";
        }
        
        if($form["important"] == 1){ // доделать
            $where[] = "o.order_phone1 = g.guest_phone ";
        }
        
        if($form["black_list"] == 1){
            $where[] = "o.order_damned = 1";
        }
        
        if($form['sex']['f'] == 1 || $form['sex']['m'] == 1){
            $sexes = array();
            if($form['sex']['f'] == 1){
                $sexes[] = "F";
            }
            if($form['sex']['m'] == 1){
                $sexes[] = "M";
            }
            $sex = "('".join("', '", $sexes)."')";
            $where[] = "o.order_sex IN $sex";
        }
        
        if($form['sms']['acception'] == 1 || $form['sms']['rejection'] == 1 || $form['sms']['unknown'] == 1){
            $sms = array();
            if($form['sms']['acception'] == 1){
                $sms[] = 1;
            }
            if($form['sms']['rejection'] == 1){
                $sms[] = 0;
            }
            
            if(count($sms) == 0){
                $sms[] = 2; // костыль. с order_sms1 == 2 заказов точно нет (ну по крайней мере не должно)
            }
            if($form['sms']['unknown'] == 1){
                $unknown = ' OR (o.order_sms IS NULL OR o.order_sms1 IS NULL) ';
            }
            $where[] = "o.order_sms1 IN (".join(",", $sms).")$unknown";
        }
        
        if(!empty($form['morses'])){
            $morses = "('".join("', '", $form['morses'])."')";
            $where[] = "p.product_name IN  $morses";
        }
        
        if($form["pie_assortment"] != ''){ 
            $where[] = "c.cart_pie_id IN (".join(',', $form['pie_assortment']).")";
        }
        
        if(count($form["remoteness"]) != 0){
            $remotenesses = "('".join("', '", $form['remoteness'])."')";
            $where[] = "m.metro_remoteness IN  $remotenesses";
        }
        
        if($form['source'] != 'default' && $form['source'] != ''){
            $source = $form['source'];
            $where[] = "o.order_source = '$source'";
        }
        
        if($form['type'] != 'default' && $form['type'] != ''){
            $type = $form['type'];
            $where[] = "o.order_delivery_type = '$type'";
        }
        
        if(count($form["mark"]) != 0){
            if(in_array("4", array_values($form['mark']))){
                $mark_is_null = " OR o.order_phoned_mark IS NULL";
            } else {
                $mark_is_null = "";
            }
            $marks = "IN (".join(", ", $form['mark']).")";
            $where[] = "o.order_phoned_mark $marks $mark_is_null";
        }
        
        if($form['early_delivery'] == 1){
            $delivery[] = " o.exp_delivery_time < o.order_interval_start ";
        }
        
        if($form['late_delivery'] == 1){
            $delivery[] = " o.exp_delivery_time > o.order_interval_start ";
        }
        
        if($form['in_company'] == '1'){
            $where[] = "o.order_guest_company IS NOT NULL AND o.order_guest_company != ''";
        } else {
            if ($form["companies"] != "") {
                $companies = "('" . join("', '", $form['companies']) . "')";
                $where[] = "o.order_guest_company IN  $companies";
            }
        }
        
        
        if($form['birthday'] == '1'){
            if(($sDate == '') || ($eDate == '')){
                $where[] = "o.order_guest_dr IS NOT NULL AND o.order_guest_dr != 0000-00-00";
            }else{
                $where[] = "(DAYOFYEAR(o.order_guest_dr) 
                    BETWEEN DAYOFYEAR(STR_TO_DATE('$sDate', '%d.%m.%Y')) 
                        AND DAYOFYEAR(STR_TO_DATE('$eDate', '%d.%m.%Y')))"; //*/
            }
            $group_by = "GROUP BY o.order_phone1";
        }
        
        if($form['cancelled'] == '0'){
            $where[] = "o.order_is_cancelled = 0";
        } 
        
        if($form["city"] != ""){
            $city_id = $form['city'];
            $where[] = "o.order_city_id = $city_id";
        }
        
        if($form['address_amount'] != ''){
            $qty = $form['address_amount'];
            $concat = "
                COUNT(  CONCAT(order_street,
                    order_dom,
                    order_korpus,
                    order_kvartira)) AS qty,
            ";
            $group_by = "GROUP BY o.order_phone1
                HAVING qty = $qty AND o.order_delivery_type = 'exp'
             ";
        }
        
        if($form['pie_amount_from'] != '' || $form['pie_amount_to'] != ''){
            $qty_from = (int)$form['pie_amount_from'];
            $qty_to = (int)$form['pie_amount_to'];
            ($qty_to == 0) ? $qty_to = 9999 : '';
            $concat = "
                SUM(c.pie_count) AS qty,
            ";
            $group_by = "GROUP BY c.cart_order_id
                HAVING qty BETWEEN  $qty_from AND $qty_to";
        }
        
        if($form['cart_sum_from'] != ''){
            $sum_from = (int)$form['cart_sum_from'];
            $where[] = "o.order_cart_sum >= $sum_from";
        }
        
        if($form['cart_sum_to'] != ''){
            $sum_to = (int)$form['cart_sum_to'];
            $sum_to == 0 ? $sum_to = 999999 : ''    ;
            $where[] = "o.order_cart_sum <= $sum_to";
        }
        
        if($form['page'] && $form['rows']){
            $offset = $form['page'] * $rows;
        } else {
            $offset = 0; // ЕСЛИ ВДРУГ каким-то образом эти переменные не пришли 
            $rows = 500; // к нам из формы -- ставим дефолтные   
        }
        
        if($form['sort'] != ''){
            switch($form['sort']){
                case 'order_date': 
                    $column = 'order_date DESC';
                    break;
                case 'guest_name': 
                    $column = 'order_last_name ASC, order_first_name ASC';
                    break;
                case 'dr_asc': 
                    $where[] = 'order_guest_dr IS NOT NULL AND order_guest_dr != 0000-00-00';
                    $column = 'DAYOFYEAR(order_guest_dr) ASC';
                    break;
                case 'dr_desc': 
                    $where[] = 'order_guest_dr IS NOT NULL AND order_guest_dr != 0000-00-00';
                    $column = 'DAYOFYEAR(order_guest_dr) DESC';
                    break;
                case 'city': 
                    $column = 'order_city_id';
                    break;
                default:
                    $column = 'order_id DESC';
                    break;
            }
            $order_by = "ORDER BY $column";
        }
        
        if(count($date_range) == 2){
            $var = join(" AND ", $date_range);
            $where[] = "( $var )";
        }
        
        if(count($delivery) == 2){
            $var = join(" OR ", $delivery);
            $where[] = "( $var )";
        }else if(count($delivery) == 1){
            $where[] = $delivery[0];
        }
        
        if(count($where) == 0){
            $condition = 'TRUE';
        } else {
            $condition = join(" AND ", $where);
        }
        
        $columns = "
                $concat
                o.order_id, 
                COUNT(o.order_id) AS ords,
                g.guest_id, 
                o.order_first_name, 
                o.order_last_name, 
                o.order_guest_company,
                o.order_date,
                m.metro_remoteness,
                o.order_guest_dr,
                g.guest_comment,
                o.order_phone1,
                o.order_city_id,
                o.order_street, 
                o.order_dom, 
                o.order_korpus, 
                o.order_podezd, 
                o.order_etag,
                o.order_kvartira,
                o.order_phone1,
                o.order_phone2,
                o.order_phoned_mark,
                o.order_email,
                o.order_comment,
                o.order_delivery_type,
                o.order_cart_sum,
                o.order_currency,
                o.order_source,
                o.order_is_mobile,
                SUM(o.order_cart_count) as crtsum
                
                ";
        
        $query = "
        SELECT DISTINCT SQL_CALC_FOUND_ROWS $columns
            FROM nikolay_order AS o 
            LEFT JOIN nikolay_guest AS g 
                ON o.order_guest_id = g.guest_id
            LEFT JOIN nikolay_metro AS m
                ON o.order_metro_id = m.metro_id
            LEFT JOIN nikolay_order_product AS op
                ON op.op_order_id = o.order_id
            LEFT JOIN nikolay_menu_product AS p
                ON op.op_product_id = p.product_id
            LEFT JOIN nikolay_order_cart AS c
                ON o.order_id = c.cart_order_id
            WHERE $condition
            $group_by            
                
            $order_by
            
            LIMIT ?d, ?d;
                ";
        
        //$results = $this->db->query($query, $offset, $rows);
        
        $this->db->transaction();
        try{
            $results = $this->db->query($query, $offset, $rows);
            $rows_qty = $this->db->query("SELECT FOUND_ROWS() AS rows;");
        } catch (Exception $ex) {
            $this->db->rollback(); // эмм, зачем откатывать селект? о_О
        } finally {
            $this->db->commit();
        }
        
        $ct_query = "
            SELECT SUM(order_cart_count) FROM (
                SELECT *
                FROM nikolay_order AS o 
                LEFT JOIN nikolay_guest AS g 
                    ON o.order_guest_id = g.guest_id
                LEFT JOIN nikolay_metro AS m
                    ON o.order_metro_id = m.metro_id
                LEFT JOIN nikolay_order_product AS op
                    ON op.op_order_id = o.order_id
                LEFT JOIN nikolay_menu_product AS p
                    ON op.op_product_id = p.product_id
                LEFT JOIN nikolay_order_cart AS c
                    ON o.order_id = c.cart_order_id
                WHERE $condition
                GROUP BY order_id) AS t
            ";
        
        $cart_total = $this->db->selectCell($ct_query);
        
        
        foreach($results as $rowId => $row){
            if($row['order_guest_dr'] == "0000-00-00"){
                $results[$rowId]['order_guest_dr'] = '';
            } else if($row['order_guest_dr'] != ''){
                $date = DateTime::createFromFormat('Y-m-d', $row['order_guest_dr']);
                $results[$rowId]['order_guest_dr'] = Leto_Dates_Utf8_Ru::date("d месяца", $date->getTimestamp());
                $results[$rowId]['guest_dr_day_month'] = $date->format('d-m');
            }
        }
        
        return array($results, $rows_qty[0]['rows'], $cart_total);
    }  
    