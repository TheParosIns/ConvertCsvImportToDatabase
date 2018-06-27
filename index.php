<?php

include 'Basic.php';
include 'Articoli.php';
include 'Catalogazione.php';
include 'CategorieClassificazione.php';
include 'Cliente.php';
include 'Listini.php';
include 'Prezzi.php';
include 'config.php';
include 'Disponibilita.php';



if(isset($_GET['action'])) { $action = $_GET['action']; }

if(isset($action)){
  if ($action == 'convert') {

     $basic = new Basic();

  	 $articoliObj = new Articoli();
  	 $catalogazione = new Catalogazione();
     $categorieObj = new CategorieClassificazione();
     $clienteObj = new Cliente();
     $listiniObj = new Listini();
     $disponibilita= new Disponibilita();
     $prezzi = new Prezzi();

     $listiniObj->importAnagraficaListini();
     $categorieObj->importAnagraficaCategorieClassificazione();
     $articoliObj->importAnagraficaArticoli();
     $disponibilita->importAnagraficaDisponibilita();
  	 $prezzi->importAnagraficaPrezzi();
     $catalogazione->importAnagraficaCatalogazione();
     $clienteObj->importAnagraficaCliente();

         die("Inserimento correto");
  }
  else{
    echo 'Action not defined.';
  }

}
else{
  echo 'No action selected';
}
  

