<?php

include 'config.php';

DEFINE('ANAGRAFICA_CATEGORIE_CLASSIFICAZIONE', '/var/www/html/Anagrafica_categorie_classificazione.csv');

/**
* 
*/
class CategorieClassificazione
{
   
    //elaborazione di Anagrafica_categorie_classificazione.csv
     function importAnagraficaCategorieClassificazione(){


        $baseMethods = new Basic();
        $conn=$baseMethods->connection();
        $file = ANAGRAFICA_CATEGORIE_CLASSIFICAZIONE;
        $righe=$baseMethods->elabora_csv_1($file);
        $array_category_inserite=array(); //array che come chiave avrà l'ID nel CSV delle categorie e come valore il category_id in oc_category
        $parent_category_id = '';


         $today = date('Y-m-d');
         $category_id='';
        foreach($righe as $key => $row){
            $data_update = date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $row['ultima modifica'])));
            $id_opencart_item=$baseMethods->retrieve_oc_id($row["codice categoria"],'oc_category',$row);
           
            if ($id_opencart_item < 0) {
                $id_opencart_item=$row["codice categoria"]; //NOTHINNG TO UPDATE

            }
            else {
                if ($row['stato']=='SI') {
                    $category_status=1;
                }
                else {
                    $category_status=0;
                }
                if ($row['codice sezione']=='CAT') {
                    $parent_category_id=0;
                    $category_level=0;
                }
                else {
                    //retrieve tra parent category
                    $temp=explode('  ',$row['codice categoria']);
                    $parent_category_csvid=$temp[count($temp)-1];
                    $category_level=count($temp)-1;

                    $sql_to_execute="INSERT INTO `oc_synch` ( `oc_id`, `tabella_open_cart`,`module`,`id_csv`,`md5_csv`,`createdtime`,`modifiedtime`) VALUES (".$parent_category_csvid.",'oc_category','Category','".$row["codice categoria"]."','".$category_level."','".$today."', '".$today."')";
                    mysqli_query($conn,$sql_to_execute);

                    $sql = mysqli_query($conn,"SELECT oc_id from oc_synch where oc_id=".$parent_category_csvid." and module='Category' LIMIT 1");
                    $result_fetched_array = mysqli_fetch_array($sql,MYSQLI_ASSOC);
                    $parent_category_id=$result_fetched_array['oc_id'];

                }

                if ($id_opencart_item == 0) {

                    $query="INSERT INTO `oc_category` ( `image`, `parent_id`, `top`, `column`, `sort_order`, `status`, `date_added`, `date_modified`, `sorting`, `viewtype`, `itemsperpage`, `showviewtype`, `showsorting`, `showitemsperpage`) VALUES ( '', ".$parent_category_id.", 0, 2, 2, ".$category_status.", '".$data_update."', '".$data_update."', 1, 1, 15, 0, 0, 0)";
                    mysqli_query($conn,$query);
                    $sql=mysqli_query($conn,"select category_id from oc_category ORDER BY category_id DESC LIMIT 1");
                    $result_fetched_array = mysqli_fetch_array($sql, MYSQLI_ASSOC);
                    $category_id=$result_fetched_array['category_id'];

                }
                else {

                    mysqli_query($conn,"UPDATE `oc_category` SET `parent_id`=".$parent_category_id.", `status`=".$category_status.", `date_modified`='".$data_update."' where `category_id`=".$id_opencart_item);
                }

                $id_opencart_item =$category_id;

                $baseMethods->sync_checksums($row["codice categoria"],$id_opencart_item,'oc_category',$row);

                //aggiungiamo una voce in oc_category_path
                if ($parent_category_id==0) {
                    $path_id=$id_opencart_item;
                }
                else {
                    $path_id=$parent_category_id;
                }
                $sql_to_execute="DELETE FROM `oc_category_path`  WHERE `category_id`=".$category_id;
                mysqli_query($conn,$sql_to_execute);

                $sql_to_execute="INSERT INTO `oc_category_path` (`category_id`, `path_id`, `level`) VALUES
                (".$category_id.", ".$path_id.", ".$category_level.")";
                mysqli_query($conn,$sql_to_execute);

                //Aggiorniamo la descrizione della categoria
                $sql_to_execute="DELETE FROM `oc_category_description`  WHERE `category_id`=".$category_id;
                mysqli_query($conn,$sql_to_execute);

                $sql_to_execute="INSERT INTO `oc_category_description` (`category_id`, `language_id`, `name`, `description`, `meta_title`, `meta_description`, `meta_keyword`) VALUES
                (".$category_id.", 1, '".$row["descrizione categoria"]."', '', '".$row["descrizione categoria"]."', '', '')";
                mysqli_query($conn,$sql_to_execute);

                //aggiorniamo il layout
                $sql_to_execute="DELETE FROM `oc_category_to_layout`  WHERE `category_id`=".$category_id;
                mysqli_query($conn,$sql_to_execute);

                $sql_to_execute="INSERT INTO `oc_category_to_layout` (`category_id`, `store_id`, `layout_id`) VALUES (".$category_id.", 0,0)";
                mysqli_query($conn,$sql_to_execute);

                //aggiorniamo l'associazione allo store
                $sql_to_execute="DELETE FROM `oc_category_to_store`  WHERE `category_id`=".$category_id;
                mysqli_query($conn,$sql_to_execute);

                $sql_to_execute="INSERT INTO `oc_category_to_store` (`category_id`, `store_id`) VALUES (".$category_id.", 0)";
                mysqli_query($conn,$sql_to_execute);

                //aggiorniamo i link allo store
                //aggiorniamo l'associazione allo store
                $sql_to_execute="DELETE FROM `oc_seo_url`  WHERE `query`='category_id=".$category_id."'";
                mysqli_query($conn,$sql_to_execute);

                //creare una funzione che fa lo slug della
                $slug=$baseMethods->create_slug($row["descrizione categoria"]);

                //controlliamo prima l'univocità dello slug_altrimenti gli appendiamo l'id della categoria
                $sql=mysqli_query($conn,"SELECT * from oc_seo_url where keyword='".$slug."' LIMIT 1");

                $result_fetched_array_seo_url=$sql->rows;//variabile che contiene l'array della query

                if (count($result_fetched_array_seo_url)>0) {
                    $slug.='_'.$id_opencart_item;
                }
                $sql_to_execute="INSERT INTO `oc_seo_url` (`store_id`, `language_id`, `query`, `keyword`) VALUES(0, 1, 'category_id=".$category_id."', '".$slug."')";
                mysqli_query($conn,$sql_to_execute);
            }

        }
         return true;
     }
}

  
    