<?php

class ConvertToLayout {
  private $Input_Entries;
  private $Settings;
  private $Output;
  private $Layout;
  private $Registro;
  private $User_Request;
  
  public function __construct($Layout = '', $Registro = '') {
    if (is_numeric($Registro)) {
      $this->Registro($Registro);
    }

    if (is_numeric($Layout)) {
      $this->BootUp($Layout);
    }
  }

  public function BootUp($Layout) {
    if (empty($this->Registro)) {
      throw new Exception("Voce precisa informar o Registro por meio metodo Registro().");
    }

    $this->Layout = $Layout;
    $this->Settings_Load();
  }

  public function Registro($Registro) {
    $this->Registro = $Registro;
  }

  private function Settings_Load() {
    $Layout = $this->Layout;
    $Registro = $this->Registro;

    $file_location = "Layout_INIs/$Layout.ini";

    if (!file_exists($file_location)) {
      throw new Exception("Nao foi possivel ler o arquivo de configuracao em $file_location");
    }
    $this->Settings = parse_ini_file($file_location, TRUE);

    if (!isset($this->Settings[$Registro])) {
      throw new Exception("As configuracoes do Registro $Registro NAO foram informadas em $file_location");
    }
  }

  public function User_Request() {
    $parameters = [
      'file_location' => 'temp/User_Request.txt',
      'parse_ini' => TRUE,
    ];

    $this->User_Request = $this->Load_File($parameters);
  }

  public function Input_File() {
    if (empty($this->User_Request)) {
      throw new Exception("Voce precisa enviar os dados da requisicao por meio do metodo User_Request().");
    }

    $parameters = [
      'file_location' => 'temp/input.csv',
      'explode' => "\n",
    ];
    $Registro = $this->Registro;

    $this->Input_Entries = $this->Load_File($parameters);
    // First line is the header, so it must go.
    unset($this->Input_Entries[0]);

    $this->Cabecalho_do_Arquivo();
    foreach($this->Input_Entries as $line_number => $line_values) {
      if (empty($line_values)) {
        unset($this->Input_Entries[$line_number]);
      }
      else {
        $this->Input_Entries[$line_number] = $line_values = explode(';', $line_values);
        $this->Settings[$Registro]['Sequencial']['valor'] = $line_number;

        $this->Output_Line_Assembling(['input_file_line_number' => $line_number]);
      }
    }
    $this->Finalizador_do_Arquivo();
  }

  /**
   * Build up a single line to be output plus an EOL.
   */
  private function Output_Line_Assembling($parameters = []) {
    $input_file_line_number = (isset($parameters['input_file_line_number'])) ? $parameters['input_file_line_number'] : '';
    $section = (isset($parameters['section'])) ? $parameters['section'] : '';
    $user_request = $this->User_Request['Lancamentos'];

    if (empty($section)) {
      // The data for this output line comes from the input file.
      $section = $this->Registro;
    }
    $section_settings = $this->Settings[$section];

    $input_file_line_values = [];
    if (is_numeric($input_file_line_number)) {
      $input_file_line_values = $this->Input_Entries[$input_file_line_number];
    }
    $output_fields = $section_settings['Campos'];

    foreach($output_fields as $output_field_delta => $output_field_name) {
      $output_value = ' ';
      $output_pad_caracter = ' ';
      $output_pad_direction = STR_PAD_RIGHT;
      $output_settings = (isset($section_settings[$output_field_name])) ? $this->Settings[$section][$output_field_name] : [];

      if (isset($user_request[$output_field_name]['valor'])) {
        $output_settings['valor'] = $user_request[$output_field_name]['valor'];
      }

      if (isset($output_settings['delta'])) {
        $delta = $output_settings['delta'];
        // Get it from input file.
        $output_settings['valor'] = $input_file_line_values[$delta]; 
      }

      foreach($output_settings as $setting_name => $setting_value) {
        switch($setting_name) {
          case 'valor':
            // Check if field value has a fixed value.
            $output_value = $setting_value;
          break;
          case 'preenchimento_caractere':
            $output_pad_caracter = $setting_value;
          break;
          case 'preenchimento_direcao':
            if ($setting_value == 'Esquerda') {
              $output_pad_direction = STR_PAD_LEFT;
            }
          break;
        }
      }

      // Validate field value.
      if (isset($output_settings['tipo'])) {
        switch($output_settings['tipo']) {
          case 'Numero':
            // Remove all non numerical characteres.
            $output_value = preg_replace("/[^0-9]/", '', $output_value);
          break;
        }
      }

      if (isset($output_settings['tamanho'])) {
        $output_value = str_pad($output_value, $output_settings['tamanho'], $output_pad_caracter, $output_pad_direction);
      }

      $this->Output .= $output_value;
    }

    $this->Output .= PHP_EOL;
  }

  private function Load_File($parameters) {
    $file_location = $parameters['file_location'];
    $explode = (isset($parameters['explode'])) ? $parameters['explode'] : '';
    $parse_ini = (isset($parameters['parse_ini'])) ? $parameters['parse_ini'] : FALSE;

    if (!file_exists($file_location)) {
      throw new Exception("Nao foi possivel ler o arquivo em $file_location");
    }

    if ($parse_ini) {
      $file_content = parse_ini_file($file_location, TRUE);
    }
    else {
      $file_content = file_get_contents($file_location);
    }

    if (!empty($explode)) {
      $file_content = explode($explode, $file_content);
    }

    return $file_content;
  }

  private function Cabecalho_do_Arquivo() {
    $this->Output_Line_Assembling(['section' => 'Cabecalho_do_Arquivo']);
  }

  private function Finalizador_do_Arquivo() {
    $this->Output_Line_Assembling(['section' => 'Finalizador_do_Arquivo']);
  }

  public function Output_File() {
    file_put_contents('temp/output.txt', $this->Output);
  }
}
