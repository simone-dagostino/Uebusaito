<?php
namespace ReinventSoftware\UebusaitoBundle\Classes;

use ReinventSoftware\UebusaitoBundle\Classes\Utility;
use ReinventSoftware\UebusaitoBundle\Classes\UtilityPrivate;

class Upload {
    // Vars
    private $container;
    private $entityManager;
    
    private $utility;
    private $utilityPrivate;
    
    private $path;
    private $inputType;
    private $maxSize;
    private $type;
    private $chunkSize;
    private $nameOverwrite;
    private $imageWidth;
    private $imageHeight;
    
    private $tmp;
    private $name;
    
    // Properties
    
    // Functions public
    public function __construct($container, $entityManager) {
        $this->container = $container;
        $this->entityManager = $entityManager;
        
        $this->utility = new Utility($this->container, $this->entityManager);
        $this->utilityPrivate = new UtilityPrivate($this->container, $this->entityManager);
        
        $this->path = "";
        $this->inputType = "";
        $this->maxSize = 0;
        $this->type = Array();
        $this->chunkSize = 0;
        $this->nameOverwrite = "";
        $this->imageWidth = 0;
        $this->imageHeight = 0;
        
        $this->tmp = 0;
        $this->name = "";
    }
    
    public function processFile($path, $inputType, $maxSize, $type, $chunkSize, $nameOverwrite, $imageWidth, $imageHeight) {
        $this->path = $path;
        $this->inputType = $inputType;
        $this->maxSize = $maxSize;
        $this->type = $type;
        $this->chunkSize = $chunkSize;
        $this->nameOverwrite = $nameOverwrite;
        $this->imageWidth = $imageWidth;
        $this->imageHeight = $imageHeight;
        
        $action = "";
        
        if (isset($_GET['action']) == true)
            $action = $_GET['action'];
        
        if (isset($_GET['tmp']) == true)
            $this->tmp = $_GET['tmp'];
        
        if (isset($_GET['name']) == true)
            $this->name = $_GET['name'];
        
        if ($action == "change")
            return $this->change();
        else if ($action == "start")
            return $this->start();
        else if ($action == "finish")
            return $this->finish();
        else if ($action == "abort")
            return $this->abort();
    }
    
    // Functions private
    function change() {
        $fileSize = 0;
        $fileType = "";
        
        if (isset($_POST['fileSize']) == true)
            $fileSize = $_POST['fileSize'];
        
        if (isset($_POST['fileType']) == true)
            $fileType = $_POST['fileType'];
        
        if ($this->maxSize > 0 && $fileSize > $this->maxSize) {
            return Array(
                'status' => 1,
                'text' => $this->utility->getTranslator()->trans("class_upload_1") . "<b>" . $this->utility->sizeUnits($this->maxSize) . "</b>"
            );
        }
        else if (in_array($fileType, $this->type) === false) {
            return Array(
                'status' => 1,
                'text' => $this->utility->getTranslator()->trans("class_upload_2") . "<b>" . implode(", ", $this->type) . "</b>"
            );
        }
    }
    
    function start() {
        if ($this->tmp == 0)
            $this->tmp = uniqid(mt_rand(), true) . ".tmp";

        $content = file_get_contents("php://input");

        if ($content != false) {
            $fopen = fopen($this->path . "/" . $this->tmp, "a");
            
            if ($this->maxSize > 0 && filesize($this->path . "/" . $this->tmp) > $this->maxSize) {
                unlink($this->path . "/" . $this->tmp);
                
                return Array(
                    'status' => 1,
                    'text' => $this->utility->getTranslator()->trans("class_upload_1") . "<b>" . $this->utility->sizeUnits($this->maxSize) . "</b>"
                );
            }
            else if ($this->checkChunkSize() == false) {
                return Array(
                    'status' => 1,
                    'text' => $this->utility->getTranslator()->trans("class_upload_4")
                );
            }
            else {
                fwrite($fopen, $content);
                fclose($fopen);
                
                return Array(
                    'status' => 0,
                    'tmp' => $this->tmp
                );
            }
        }
        else {
            return Array(
                'status' => 2
            );
        }
    }
    
    function finish() {
        if (file_exists($this->path . "/" . $this->tmp) == true) {
            if ($this->imageWidth > 0 ||  $this->imageHeight > 0)
                $imageSize = getimagesize($this->path . "/" . $this->tmp);
            
            if ($this->maxSize > 0 && filesize($this->path . "/" . $this->tmp) > $this->maxSize) {
                unlink($this->path . "/" . $this->tmp);
                
                return Array(
                    'status' => 1,
                    'text' => $this->utility->getTranslator()->trans("class_upload_1") . "<b>" . $this->utility->sizeUnits($this->maxSize) . "</b>"
                );
            }
            else if (in_array(mime_content_type($this->path . "/" . $this->tmp), $this->type) === false) {
                unlink($this->path . "/" . $this->tmp);
                
                return Array(
                    'status' => 1,
                    'text' => $this->utility->getTranslator()->trans("class_upload_2") . "<b>" . implode(", ", $this->type) . "</b>"
                );
            }
            else if ($this->imageWidth > 0 && $imageSize[0] > $this->imageWidth || $this->imageHeight > 0 && $imageSize[1] > $this->imageHeight) {
                unlink($this->path . "/" . $this->tmp);

                return Array(
                    'status' => 1,
                    'text' => $this->utility->getTranslator()->trans("class_upload_3") . "<b>{$this->imageWidth} px - {$this->imageHeight} px</b>"
                );
            }
            else {
                if ($this->nameOverwrite != "")
                    $this->name =  $this->nameOverwrite . "." . pathinfo($this->name, PATHINFO_EXTENSION);

                @rename($this->path . "/" . $this->tmp, $this->path . "/" . $this->name);

                return Array(
                    'text' => $this->utility->getTranslator()->trans("class_upload_5")
                );
            }
        }
        else {
            return Array(
                'text' => $this->utility->getTranslator()->trans("class_upload_6")
            );
        }
    }
    
    function abort() {
        if (file_exists($this->path . "/" . $this->tmp) == true)
            unlink($this->path . "/" . $this->tmp);
        
        return Array(
            'text' => $this->utility->getTranslator()->trans("class_upload_7")
        );
    }
    
    function checkChunkSize() {
        $fopen = fopen($this->path . "/check_" . $this->tmp, "a");
        $fstat = fstat($fopen);
        $size = array_slice($fstat, 13)['size'];
        fclose($fopen);

        if ($size > $this->chunkSize) {
            unlink($this->path . "/check_" . $this->tmp);

            return false;
        }
        else
            unlink($this->path . "/check_" . $this->tmp);
        
        return true;
    }
}