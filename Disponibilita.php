<?php

include 'config.php';

 DEFINE('ANAGRAFICA_DISPONIBILITA', '/var/www/html/Anagrafica_disponibilita.csv');
    
    /**
    * 
    */
    class Disponibilita
    {
        
         //elaborazione di Anagrafica_disponibilita.csv
     function importAnagraficaDisponibilita(){

        $baseMethods = new Basic();
        $conn=$baseMethods->connection();

        $query_update_on_products=array();
        $array_prodotti_inseriti=array();
        $filename = ANAGRAFICA_DISPONIBILITA;
        $righe=$baseMethods->elabora_csv_1($filename);

        foreach($righe as $key => $row){

            $data_update = date('Y-m-d H:i:s');
            if (isset($row['codice articolo'])) {
                $quantity=intval($row['inventario']);
                if ($quantity>0) {
                    $stock_status_id=7;//Disponibile
                }
                elseif (intval($row['ordinato fornitore'])>0) {
                    $stock_status_id=6;//In 2-3 Giorni
                }
                else {
                    $stock_status_id=5;//Non disponibile
                }

                $query_update_on_products[$array_prodotti_inseriti[$row['codice articolo']]]="`quantity`=".addslashes($row['codice articolo']).",`stock_status_id`=".$stock_status_id;
                        mysqli_query($conn,"UPDATE `oc_product` SET `quantity`=".intval($row['inventario']).", `stock_status_id`=".$stock_status_id.", `date_modified`='".$data_update."' where `product_id`=".addslashes($row['codice articolo']));
                  

            }
        }
    }

}