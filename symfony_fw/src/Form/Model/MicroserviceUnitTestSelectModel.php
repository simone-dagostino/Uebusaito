<?php
namespace App\Form\Model;

class MicroserviceUnitTestSelectModel {
    // Vars
    private $id;
    
    // Properties
    public function setId($value) {
        $this->id = $value;
    }
    
    // ---
    
    public function getId() {
        return $this->id;
    }
}