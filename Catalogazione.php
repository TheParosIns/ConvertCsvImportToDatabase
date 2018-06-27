<?php

include 'config.php';

DEFINE('ANAGRAFICA_CATALOGAZIONE', '/var/www/html/Anagrafica_catalogazione.csv');


/**
* 
*/
class Catalogazione
{
    
  //elaborazione di Anagrafica_catalogazione.csv

     function importAnagraficaCatalogazione(){

         $baseMethods = new Basic();
         $conn=$baseMethods->connection();
        $filename = ANAGRAFICA_CATALOGAZIONE;

        $righe = $baseMethods->elabora_csv_1($filename);

        foreach($righe as $key => $row){

            if ((isset($row['codice articolo']) && (isset($row['Entita2'])))) {

                $sql = mysqli_query($conn,"SELECT product_id FROM `oc_product` WHERE `sku` ='".$row['codice articolo']."' LIMIT 1");
                $result_fetched_array = mysqli_fetch_array($sql,MYSQLI_ASSOC);
                $productId = $result_fetched_array['product_id'];
                mysqli_query($conn,"DELETE FROM `oc_product_to_category`  WHERE `product_id`=".$productId);

                $temp=explode('  ',$row['Entita2']);
                $categoryId=$temp[count($temp)-1];
                mysqli_query($conn,"INSERT INTO `oc_product_to_category` (`product_id`, `category_id`) VALUES (".intval($productId).", ".$categoryId.")");


            }

        }

        return true;
    }


}




    