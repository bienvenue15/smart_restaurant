<?php
class View{

    public function __construct(){

    }

    public function render($_name, $data){
        foreach($data as $key => $value){
            global $$key;
            $$key = $value; 
        }
        require_once "app/views/".$_name.".php";
    }
}
?>