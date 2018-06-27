<?php

include 'config.php';

DEFINE('ANAGRAFICA_PREZZI', '/var/www/html/Anagrafica_prezzi.csv');
/**
* 
*/
class Prezzi
{
 //elaborazione di Anagrafica_prezzi.csv
     function importAnagraficaPrezzi(){
//
         $file = ANAGRAFICA_PREZZI;
        $baseMethods = new Basic();
        $conn = $baseMethods->connection();
        $righe=$baseMethods->elabora_csv_prezzi($file);
        
        $listino_di_default="LIS_50_0";
        $array_prodotti_inseriti = [];
        $query_update_on_products = [];
        $array_customer_group_inseriti = [];
        foreach($righe as $key => $row){

             if (isset($row['codice articolo']) && ($row['codice listino']==$listino_di_default)) {

                $query_update_on_products[$row['codice articolo']]=$row['prezzo'];
                
            }
            elseif (isset($row['codice listino'])) { //aggiorniamo i prezzi per gli altri clienti
                //aggiorniamo l'associazione allo store
                $sql_to_execute="DELETE FROM `oc_product_discount`  WHERE `product_id`=".$row['codice articolo']." AND `customer_group_id`=".$row['codice listino'];
                mysqli_query($conn,$sql_to_execute);

                $sql_to_execute="INSERT INTO `oc_product_to_store` (`product_id`, `customer_group_id`) VALUES  (".$row['codice articolo'].", ".$row['codice listino'].",".$row['prezzo'].")";
                mysqli_query($conn,$sql_to_execute);
            }

        }

        foreach($query_update_on_products as $key => $row){
            $sql_to_execute="UPDATE `oc_product` SET price=".$row." where `product_id`=".$key;
            mysqli_query($conn,$sql_to_execute);
        }

        return true;
}    
    
}
