<?php

include 'config.php';

DEFINE('ANAGRAFICA_ARTICOLI', '/var/www/html/Anagrafica_articoli.csv');

class Articoli
{
    
     //elaborazione di Anagrafica_articoli.csv
     public function importAnagraficaArticoli(){

        $baseMethods = new Basic();

        $file = ANAGRAFICA_ARTICOLI;
        $product_id = '';
        $righe= $baseMethods->elabora_csv_articoli($file);
        $array_prodotti_inseriti=array(); //array che come chiave avrà l'ID nel CSV dei prodotti e come valore il product_id in oc_product
        foreach($righe as $key => $row) {

            $id_opencart_item =$baseMethods->retrieve_oc_id($row["codice"], 'oc_product', $row);
            if ($id_opencart_item < 0) {
                $array_prodotti_inseriti[$row["codice"]] = $id_opencart_item; //nothing to update
            } else {

                $data_update = date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $row['ultima modifica'])));

                $data_disponibilitae = date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $row['data disponibilita'])));

                $Data_scadenza = date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $row['Data_scadenza'])));

                $minimum = "";
                if ($id_opencart_item == 0) {
                    //quantity e stock_status_id li aggiorneremo dopo sulla base di Anagrafica_diponinilita;
                    $quantity = 0;
                    $stock_status_id = 0;
                    $prezzo = 0; //lo aggiorniamo sul parsing dell'anagrafica listini
                    $status = 1;
                    $minimum = $row['pezzi_confezione'];

                    $query="INSERT INTO `oc_product` (`model`, `sku`, `upc`, `ean`, `jan`, `isbn`, `mpn`, `location`, `quantity`, `stock_status_id`, `image`, `manufacturer_id`, `shipping`, `price`, `points`, `tax_class_id`, `date_available`, `weight`, `weight_class_id`, `length`, `width`, `height`, `length_class_id`, `subtract`, `minimum`, `sort_order`, `status`, `viewed`, `date_added`, `date_modified`, `date_start`, `date_end`) VALUES ('" . addslashes($row["descrizione"]) . "', '" . addslashes($row["codice"]) . "', '', '', '', '', '', '', " . $quantity . ",  " . $stock_status_id . ", 'catalog/" . addslashes($row["codice"]) . ".jpg', 0, 1, " . $prezzo . ", 0, 0, '2017-01-01', 0.00000000, 1, 0.00000000, 0.00000000, 0.00000000, 1, " . $minimum . ", " . $minimum . ", 1, " . $status . ", 0, '" . $data_update . "', '" . $data_update . "', '".$data_update."', '".$Data_scadenza."')";

                    mysqli_query($baseMethods->connection(),$query);

                    $sql = mysqli_query($baseMethods->connection(),"select product_id from oc_product ORDER BY product_id DESC LIMIT 1");
                    $result_fetched_array = mysqli_fetch_array($sql,MYSQLI_ASSOC);//variabile che contiene l'array della query
                    $product_id = $result_fetched_array['product_id'];

                    $sql_to_execute = "INSERT INTO `oc_product_image` (`product_id`, `image`, `sort_order`) VALUES (" . $product_id . ", 'catalog/" . $row["codice"] . ".jpg', 0)";
                    mysqli_query($baseMethods->connection(),$sql_to_execute);

                } else {
                    $sql_to_execute = "UPDATE `oc_product` SET `model`='" . addslashes($row["descrizione"]) . "',`subtract`=" . $minimum . ", `minimum`=" . $minimum . ", `date_modified`='" . $data_update . "' where `product_id`=" . $product_id;
                    mysqli_query($baseMethods->connection(),$sql_to_execute);
                }
                 $id_opencart_item = $product_id ;

                $baseMethods->sync_checksums($row["codice"], $id_opencart_item, 'oc_product', $row);
                //Aggiorniamo la descrizione della categoria
                $sql_to_execute = "DELETE FROM `oc_product_description`  WHERE `product_id`=" . $product_id;
                mysqli_query($baseMethods->connection(),$sql_to_execute);

                $sql_to_execute = "INSERT INTO `oc_product_description` (`product_id`, `language_id`, `name`, `subtitle`, `description`, `tag`, `meta_title`, `meta_description`, `meta_keyword`, `introtext`) VALUES
                (" . $product_id . ", 1, '" . addslashes($row["descrizione"]) . "', '', '" . addslashes($row["descrizione aggiuntiva"]) . "', '" . addslashes($row["descrizione aggiuntiva"]) . "','" . addslashes($row["descrizione aggiuntiva"]) . "','" . addslashes($row["descrizione aggiuntiva"]) . "','" . addslashes($row["descrizione aggiuntiva"]) . "','" . addslashes($row["descrizione aggiuntiva"]) . "')";
               
                mysqli_query($baseMethods->connection(),$sql_to_execute);

//                $sql_to_execute = "INSERT INTO `oc_product_to_category` (`product_id`, `category_id`) VALUES
//                (" . $product_id . ", 2)";
//                mysqli_query($baseMethods->connection(),$sql_to_execute);
                
                //aggiorniamo il layout
                $sql_to_execute = "DELETE FROM `oc_product_to_layout`  WHERE `product_id`=" . $product_id;
                mysqli_query($baseMethods->connection(),$sql_to_execute);

                $sql_to_execute = "INSERT INTO `oc_product_to_layout` (`product_id`, `store_id`, `layout_id`) VALUES(" . $product_id . ", 0,0)";
                mysqli_query($baseMethods->connection(),$sql_to_execute);
                //aggiorniamo l'associazione allo store
                $sql_to_execute = "DELETE FROM `oc_product_to_store`  WHERE `product_id`=" . $product_id;
                mysqli_query($baseMethods->connection(),$sql_to_execute);

                $sql_to_execute = "INSERT INTO `oc_product_to_store` (`product_id`, `store_id`) VALUES  (" . $product_id . ", 0)";
                mysqli_query($baseMethods->connection(),$sql_to_execute);

                //aggiorniamo i link allo store
                //aggiorniamo l'associazione allo store
                $sql_to_execute = "DELETE FROM `oc_seo_url`  WHERE `query`='product_id=" . $product_id . "'";
                mysqli_query($baseMethods->connection(),$sql_to_execute);

                //creare una funzione che fa lo slug della
                $slug =$baseMethods->create_slug($row["descrizione aggiuntiva"]);

                //controlliamo prima l'univocità dello slug_altrimenti gli appendiamo l'id della categoria
                $sql = "SELECT * from oc_seo_url where keyword='" . $slug . "'";
                mysqli_query($baseMethods->connection(),$sql);
                $result_fetched_array_seo_url = $sql->rows;//variabile che contiene l'array della query

                if (count($result_fetched_array_seo_url) > 0) {
                    $slug .= '_' . $id_opencart_item;
                }
                $sql_to_execute = "INSERT INTO `oc_seo_url` (`store_id`, `language_id`, `query`, `keyword`) VALUES(0, 1, 'product_id=" . $product_id . "', '" . $slug . "')";
                mysqli_query($baseMethods->connection(),$sql_to_execute);
            }
        }

       return true;
    }
}
    