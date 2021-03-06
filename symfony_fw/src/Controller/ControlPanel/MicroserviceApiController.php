<?php
namespace App\Controller\ControlPanel;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use App\Classes\System\Utility;
use App\Classes\System\Ajax;

use App\Entity\MicroserviceApi;
use App\Form\MicroserviceApiFormType;

class MicroserviceApiController extends AbstractController {
    // Vars
    private $urlLocale;
    private $urlCurrentPageId;
    private $urlExtra;
    
    private $entityManager;
    
    private $response;
    
    private $utility;
    private $query;
    private $ajax;
    
    // Properties
    
    // Functions public
    /**
    * @Route(
    *   name = "cp_microservice_api_render",
    *   path = "/cp_microservice_api_render/{_locale}/{urlCurrentPageId}/{urlExtra}",
    *   defaults = {"_locale" = "%locale%", "urlCurrentPageId" = "2", "urlExtra" = ""},
    *   requirements = {"_locale" = "[a-z]{2}", "urlCurrentPageId" = "\d+", "urlExtra" = "[^/]+"},
    *	methods={"GET"}
    * )
    * @Template("@templateRoot/render/control_panel/microservice_api.html.twig")
    */
    public function renderAction($_locale, $urlCurrentPageId, $urlExtra, Request $request, TranslatorInterface $translator) {
        $this->urlLocale = isset($_SESSION['languageTextCode']) == true ? $_SESSION['languageTextCode'] : $_locale;
        $this->urlCurrentPageId = $urlCurrentPageId;
        $this->urlExtra = $urlExtra;
        
        $this->entityManager = $this->getDoctrine()->getManager();
        
        $this->response = Array();
        
        $this->utility = new Utility($this->container, $this->entityManager, $translator);
        $this->query = $this->utility->getQuery();
        $this->ajax = new Ajax($this->utility);
        
        // Logic
        $checkUserRole = $this->utility->checkUserRole(Array("ROLE_ADMIN", "ROLE_MICROSERVICE"), $this->getUser());
        
        if ($request->isMethod("GET") == true && $checkUserRole == true)
            $this->response['values']['microserviceApiRows'] = $this->query->selectAllMicroserviceApiDatabase(true);
        
        return Array(
            'urlLocale' => $this->urlLocale,
            'urlCurrentPageId' => $this->urlCurrentPageId,
            'urlExtra' => $this->urlExtra,
            'response' => $this->response
        );
    }
    
    /**
    * @Route(
    *   name = "cp_microservice_api_create",
    *   path = "/cp_microservice_api_create/{_locale}/{urlCurrentPageId}/{urlExtra}",
    *   defaults = {"_locale" = "%locale%", "urlCurrentPageId" = "2", "urlExtra" = ""},
    *   requirements = {"_locale" = "[a-z]{2}", "urlCurrentPageId" = "\d+", "urlExtra" = "[^/]+"},
    *	methods={"POST"}
    * )
    * @Template("@templateRoot/render/control_panel/microservice_api_create.html.twig")
    */
    public function createAction($_locale, $urlCurrentPageId, $urlExtra, Request $request, TranslatorInterface $translator) {
        $this->urlLocale = isset($_SESSION['languageTextCode']) == true ? $_SESSION['languageTextCode'] : $_locale;
        $this->urlCurrentPageId = $urlCurrentPageId;
        $this->urlExtra = $urlExtra;
        
        $this->entityManager = $this->getDoctrine()->getManager();
        
        $this->response = Array();
        
        $this->utility = new Utility($this->container, $this->entityManager, $translator);
        $this->query = $this->utility->getQuery();
        $this->ajax = new Ajax($this->utility);
        
        // Logic
        $checkUserRole = $this->utility->checkUserRole(Array("ROLE_ADMIN", "ROLE_MICROSERVICE"), $this->getUser());
        
        $microserviceApiEntity = new MicroserviceApi();
        
        $_SESSION['microserviceApiProfileId'] = 0;
        
        $form = $this->createForm(MicroserviceApiFormType::class, $microserviceApiEntity, Array(
            'validation_groups' => Array('microservice_api_create')
        ));
        $form->handleRequest($request);
        
        if ($request->isMethod("POST") == true && $checkUserRole == true) {
            if ($form->isSubmitted() == true && $form->isValid() == true) {
                $this->fileUpload($form, $microserviceApiEntity);
                
                $this->entityManager->persist($microserviceApiEntity);
                $this->entityManager->flush();

                $this->response['messages']['success'] = $this->utility->getTranslator()->trans("microserviceApiController_1");
            }
            else {
                $this->response['messages']['error'] = $this->utility->getTranslator()->trans("microserviceApiController_2");
                $this->response['errors'] = $this->ajax->errors($form);
            }
            
            return $this->ajax->response(Array(
                'urlLocale' => $this->urlLocale,
                'urlCurrentPageId' => $this->urlCurrentPageId,
                'urlExtra' => $this->urlExtra,
                'response' => $this->response
            ));
        }
        
        return Array(
            'urlLocale' => $this->urlLocale,
            'urlCurrentPageId' => $this->urlCurrentPageId,
            'urlExtra' => $this->urlExtra,
            'response' => $this->response,
            'form' => $form->createView()
        );
    }
    
    /**
    * @Route(
    *   name = "cp_microservice_api_profile_save",
    *   path = "/cp_microservice_api_profile_save/{_locale}/{urlCurrentPageId}/{urlExtra}",
    *   defaults = {"_locale" = "%locale%", "urlCurrentPageId" = "2", "urlExtra" = "", "id" = "0"},
    *   requirements = {"_locale" = "[a-z]{2}", "urlCurrentPageId" = "\d+", "urlExtra" = "[^/]+"},
    *	methods={"GET", "POST"}
    * )
    * @Template("@templateRoot/render/control_panel/microservice_api_profile.html.twig")
    */
    public function profileSaveAction($_locale, $urlCurrentPageId, $urlExtra, Request $request, TranslatorInterface $translator) {
        $this->urlLocale = isset($_SESSION['languageTextCode']) == true ? $_SESSION['languageTextCode'] : $_locale;
        $this->urlCurrentPageId = $urlCurrentPageId;
        $this->urlExtra = $urlExtra;
                
        $this->entityManager = $this->getDoctrine()->getManager();
        
        $this->response = Array();
        
        $this->utility = new Utility($this->container, $this->entityManager, $translator);
        $this->query = $this->utility->getQuery();
        $this->ajax = new Ajax($this->utility);
        
        // Logic
        $checkUserRole = $this->utility->checkUserRole(Array("ROLE_ADMIN", "ROLE_MICROSERVICE"), $this->getUser());
        
        $settingRow = $this->query->selectSettingDatabase();
        
        $urlExtraExplode = explode("_", $this->urlExtra);
        
        $id = isset($urlExtraExplode[4]) == true ? $urlExtraExplode[4] : 0;
        
        $_SESSION['microserviceApiProfileId'] = $id;
        
        $microserviceApiEntity = $this->entityManager->getRepository("App\Entity\MicroserviceApi")->find($id);
        
        $logo = "{$this->utility->getUrlRoot()}/images/templates/{$settingRow['template']}/api.png";
        
        if ($microserviceApiEntity->getImage() != "" && file_exists("{$this->utility->getPathPublic()}/files/microservice/api/{$microserviceApiEntity->getImage()}") == true)
            $logo = "{$this->utility->getUrlRoot()}/files/microservice/api/{$microserviceApiEntity->getImage()}";
        
        $this->response['values']['microserviceApiEntity'] = $microserviceApiEntity;

        $form = $this->createForm(MicroserviceApiFormType::class, $microserviceApiEntity, Array(
            'validation_groups' => Array('microservice_api_profile')
        ));
        $form->handleRequest($request);
        
        if ($request->isMethod("POST") == true && $checkUserRole == true) {
            if ($form->isSubmitted() == true && $form->isValid() == true) {
                $this->fileUpload($form, $microserviceApiEntity);
                
                $this->entityManager->persist($microserviceApiEntity);
                $this->entityManager->flush();
                
                $this->response['messages']['success'] = $this->utility->getTranslator()->trans("microserviceApiController_3");
            }
            else {
                $this->response['messages']['error'] = $this->utility->getTranslator()->trans("microserviceApiController_4");
                $this->response['errors'] = $this->ajax->errors($form);
            }
            
            return $this->ajax->response(Array(
                'urlLocale' => $this->urlLocale,
                'urlCurrentPageId' => $this->urlCurrentPageId,
                'urlExtra' => $this->urlExtra,
                'response' => $this->response,
                'logo' => $logo
            ));
        }
        
        return Array(
            'urlLocale' => $this->urlLocale,
            'urlCurrentPageId' => $this->urlCurrentPageId,
            'urlExtra' => $this->urlExtra,
            'response' => $this->response,
            'form' => $form->createView(),
            'logo' => $logo
        );
    }
    
    // Functions private
    private function fileUpload($form, $entity) {
        $row = $this->query->selectMicroserviceApiDatabase($_SESSION['microserviceApiProfileId'], true);
        
        $pathImage = "{$this->utility->getPathPublic()}/files/microservice/api";
        
        $image = $entity->getImage();
        
        // Remove image
        if ($form->get("removeImage")->getData() == true) {
            if (file_exists("{$pathImage}/{$row['image']}") == true)
                unlink("{$pathImage}/{$row['image']}");
            
            $entity->setImage("");
        }
        
        // Upload image
        if ($image != null && $form->get("removeImage")->getData() == false) {
            if ($row['image'] != "" && file_exists("{$pathImage}/{$row['image']}") == true)
                unlink("{$pathImage}/{$row['image']}");
            
            $fileName = $image->getClientOriginalName();
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newName = uniqid() . ".{$extension}";
            $image->move($pathImage, $newName);
            $entity->setImage($newName);
        }
    }
}