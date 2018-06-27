<?php

include 'config.php';

DEFINE('ANAGRAFICA_LISTINI', '/var/www/html/Anagrafica_listini.csv');

  /**
  * 
  */
  class Listini
  {
      
        //elaborazione di Anagrafica_listini.csv
     function importAnagraficaListini(){

        $file = ANAGRAFICA_LISTINI;
        $baseMethods = new Basic();
        $conn = $baseMethods->connection();
        
         $righe = $baseMethods->elabora_csv_1($file);
         $result_fetched_array=array(); //array che come chiave avrà l'ID nel CSV dei listini e come valore il customer_group_id in oc_customer_group
         $id_customer_group='';
        foreach($righe as $key => $row){

            $id_opencart_item=$baseMethods->retrieve_oc_id($row["codice"],'oc_customer_group',$row);
            if ($id_opencart_item<0) {
                $id_opencart_item = $row['codice']; //nothing to update
            }
            else {
                if ($id_opencart_item==0) {

                    $sql_to_execute="INSERT INTO `oc_customer_group` ( `approval`, `sort_order`) VALUES (0, 1)";
                    mysqli_query($conn,$sql_to_execute);

                    $sql="select customer_group_id from oc_customer_group ORDER BY customer_group_id DESC LIMIT 1";
                    $result = mysqli_query($conn,$sql);
                    $result_fetched_array = mysqli_fetch_array($result,MYSQLI_ASSOC);
                    $id_customer_group=$result_fetched_array['customer_group_id'];

                    $sql_to_execute="INSERT INTO `oc_customer_group_description` (`customer_group_id`, `language_id`, `name`, `description`) VALUES (".$id_customer_group.", 1, '".addslashes($row['codice'])."', '".addslashes($row['descrizione'])."')";

                    mysqli_query($conn,$sql_to_execute);
                }else{

                    $sql_to_execute="UPDATE `oc_customer_group_description`  SET `customer_group_id`=".$id_customer_group.", `language_id`=1, `name`='".addslashes($row['codice'])."', `description`='".addslashes($row['descrizione'])."') ";
                    mysqli_query($conn,$sql_to_execute);
                }

                $id_opencart_item=$id_customer_group;

                $baseMethods->sync_checksums($row["codice"],$id_opencart_item,'oc_customer_group',$row);
            }
        }

        return true;
     }
  }