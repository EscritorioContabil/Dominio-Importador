<?php

include_once "ConvertToLayout.Class.php";

try {
  $Registro_02 = new ConvertToLayout();
  $Registro_02->Registro('02');
  $Registro_02->BootUp(20);
  $Registro_02->User_Request();
  $Registro_02->Input_File();
  $Registro_02->Output_File();
}
catch(Exception $e) {
  print $e->getMessage() . PHP_EOL;
}
