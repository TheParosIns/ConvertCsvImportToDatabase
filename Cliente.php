<?php
include 'config.php';

DEFINE('ANAGRAFICA_CLIENTE', '/var/www/html/Anagrafica_cliente.csv');
/**
* 
*/
class Cliente
{
  //elaborazione di Anagrafica_cliente.csv
     function importAnagraficaCliente(){


        $filename = ANAGRAFICA_CLIENTE;
        $baseMethods = new Basic();
        $conn = $baseMethods->connection();
        $righe=$baseMethods->elabora_csv_1($filename);
        $array_clienti_inseriti=array(); //array che come chiave avrà l'ID nel CSV dei clienti e come valore il customer_id in oc_customer
         $result_fetched_array='';
        foreach($righe as $key => $row){
            $id_opencart_item=$baseMethods->retrieve_oc_id($row["codice"],'oc_customer',$row);

            if ($id_opencart_item < 0) {
                $array_clienti_inseriti[$row["codice"]]=$row["codice"]; //nothing to update
            }
            else {

                $firstname=addslashes($row["ragione sociale"]);
                $lastname=addslashes($row["ragione sociale"]);
                $company=addslashes($row["ragione sociale"]);
                $address_1=addslashes($row["via"]);
                $email=str_replace("'", "", $row['Email']);
                if ($email=='') $email=$row['codice'].'@sweeping.it';
                $telephone=$row['telefono'];
                $fax=$row['fax'];
                $cap=$row['cap'];
                $password="3a4bb0b51fee300cdb19370cab4a434f02ed818c"; //mvtech.2018
                $salt='f77B00sKY';
                $date_insert=date('Y-m-d H:i:s');
                $token = "3a4bb0b51fee300cdb19370cab4a434f02ed818c";
                $code = $row['codice'];

                //recuperiamo oc_zone
                $sql="select * from oc_zone where UPPER(code)='".strtoupper($row['Prov'])."'";
                $query = mysqli_query($conn,$sql);

                if ($query->num_rows==0) { //si tratta di un nuovo prodotto e quindi va fatto l'inserimento

                    $zone_id=3924; //Roma
                    $country_id=105;
                    $sql_to_execute="INSERT INTO `oc_zone` ( `zone_id`, `country_id`, `name`, `code`, `status`) VALUES ('".$zone_id."','".$country_id."' ,'".$row['Citta']."','".$row['Prov']."',1)";
                    mysqli_query($conn,$sql_to_execute);
                }
                else {

                    $data = [];
                    while ($result=mysqli_fetch_array($query,MYSQLI_ASSOC)){
                        $data[] = $result;

                    }
                    foreach ($data as $rest) {
                        $zone_id=$rest['zone_id'];
                        $country_id=$rest['country_id'];
                    }

                    $city=addslashes($row['Citta']);

                    if ($id_opencart_item==0) {

//                        $sql_to_execute="INSERT INTO `oc_customer_group` ( `approval`, `sort_order`) VALUES (0, 1)";
//                        mysqli_query($conn,$sql_to_execute);

                        $sql = mysqli_query($conn,"select customer_group_id from oc_customer_group ORDER BY customer_group_id DESC LIMIT 1");
                        $result_fetched_array = mysqli_fetch_array($sql,MYSQLI_ASSOC);

                        $customers_group_id = $result_fetched_array['customer_group_id'];

                        $sql = "INSERT INTO `oc_customer` (`customer_group_id`, `store_id`, `language_id`, `firstname`, `lastname`, `email`, `telephone`, `fax`, `password`, `salt`, `cart`, `wishlist`, `newsletter`, `address_id`, `custom_field`, `ip`, `status`, `safe`, `token`, `code`, `date_added`) VALUES ('".$customers_group_id."', 0, 1, '".$firstname."', '".$lastname."', '".$email."', '".$telephone."', '".$fax."', '".$password."', '".$salt."', NULL, NULL, 0, 0, '', '172.17.0.29', 1, 0, '".$token."', '".$code."', '".$date_insert."')";

                        mysqli_query($conn,$sql);

                        $sql="select product_id from oc_product ORDER BY product_id DESC LIMIT 1";
                        $sql =  mysqli_query($conn,$sql);
                        $result_fetched_array=mysqli_fetch_array($sql,MYSQLI_ASSOC);//variabile che contiene l'array della query
                        $id_opencart_item=$result_fetched_array['product_id'];

                    }
                    else {
                        mysqli_query($conn,"UPDATE `oc_address` SET `firstname`='".$firstname."',`lastname`='".$lastname."',`company`='".$company."',`address_1`='".$address_1."',`address_2`='',   `city`='".$city."', `postcode`='".$cap."',`country_id`=".$country_id.",`zone_id`=".$zone_id." where `customer_id`=".$id_opencart_item);
                    }
                    $id_opencart_item =$result_fetched_array['product_id'];
                    $baseMethods->sync_checksums($row["codice"],$id_opencart_item,'oc_customer',$row);


                    //eseguiamo prima l'address_id
                    mysqli_query($conn,"DELETE FROM `oc_address`  WHERE `customer_id`=".$id_opencart_item);

                    $sql_to_execute="INSERT INTO `oc_address` (`customer_id`, `firstname`, `lastname`, `company`, `address_1`, `address_2`, `city`, `postcode`, `country_id`, `zone_id`, `custom_field`) VALUES (".$id_opencart_item.", '".$firstname."', '".$lastname."', '".$company."', '".$address_1."', '', '".$city."', '".$cap."', ".$country_id.", ".$zone_id.", '')";
                    mysqli_query($conn,$sql_to_execute);
                }
            }

        }
         return true;
}
}
    