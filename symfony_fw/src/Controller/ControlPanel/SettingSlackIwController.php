<?php
namespace App\Controller\ControlPanel;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use App\Classes\System\Utility;
use App\Classes\System\Ajax;

use App\Entity\SettingSlackIw;
use App\Form\SettingSlackIwFormType;

class SettingSlackIwController extends AbstractController {
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
    *   name = "cp_setting_slack_iw_create",
    *   path = "/cp_setting_slack_iw_create/{_locale}/{urlCurrentPageId}/{urlExtra}",
    *   defaults = {"_locale" = "%locale%", "urlCurrentPageId" = "2", "urlExtra" = ""},
    *   requirements = {"_locale" = "[a-z]{2}", "urlCurrentPageId" = "\d+", "urlExtra" = "[^/]+"},
    *	methods={"POST"}
    * )
    * @Template("@templateRoot/render/control_panel/setting_slack_iw.html.twig")
    */
    public function incomingWebhookCreateAction($_locale, $urlCurrentPageId, $urlExtra, Request $request, TranslatorInterface $translator) {
        $this->urlLocale = $_locale;
        $this->urlCurrentPageId = $urlCurrentPageId;
        $this->urlExtra = $urlExtra;
        
        $this->entityManager = $this->getDoctrine()->getManager();
        
        $this->response = Array();
        
        $this->utility = new Utility($this->container, $this->entityManager, $translator);
        $this->query = $this->utility->getQuery();
        $this->ajax = new Ajax($this->utility);
        
        $this->urlLocale = $this->utility->checkLanguage($request);
        
        $this->utility->checkSessionOverTime($request);
        
        $checkUserRole = $this->utility->checkUserRole(Array("ROLE_ADMIN"), $this->getUser());
        
        // Logic
        $this->settingSlackIwList();
        
        if ($request->get("event") == null) {
            if (isset($_SESSION['settingSlackIwProfileId']) == false)
                $settingSlackIwEntity = new SettingSlackIw();
            else
                $settingSlackIwEntity = $this->entityManager->getRepository("App\Entity\SettingSlackIw")->find($_SESSION['settingSlackIwProfileId']);
            
            $form = $this->createForm(SettingSlackIwFormType::class, $settingSlackIwEntity, Array(
                'validation_groups' => Array('setting_slack_iw')
            ));
            $form->handleRequest($request);
            
            if ($request->isMethod("POST") == true && $checkUserRole == true) {
                if ($form->isSubmitted() == true && $form->isValid() == true) {
                    if (isset($_SESSION['settingSlackIwProfileId']) == true)
                        unset($_SESSION['settingSlackIwProfileId']);
                    
                    // Database insert / update
                    $this->entityManager->persist($settingSlackIwEntity);
                    $this->entityManager->flush();
                    
                    $this->settingSlackIwList();
                    
                    $this->response['messages']['success'] = $this->utility->getTranslator()->trans("settingSlackIwController_1");
                }
                else {
                    $this->response['messages']['error'] = $this->utility->getTranslator()->trans("settingSlackIwController_2");
                    $this->response['errors'] = $this->ajax->errors($form);
                }
                
                return $this->ajax->response(Array(
                    'urlLocale' => $this->urlLocale,
                    'urlCurrentPageId' => $this->urlCurrentPageId,
                    'urlExtra' => $this->urlExtra,
                    'response' => $this->response
                ));
            }
        }
        else if ($request->get("event") == "profile") {
            $id = $request->get("id") != null ? $request->get("id") : 0;
            
            $settingSlackIwEntity = $this->entityManager->getRepository("App\Entity\SettingSlackIw")->find($id);
            
            if ($settingSlackIwEntity != null) {
                $_SESSION['settingSlackIwProfileId'] = $id;
                
                $this->response['values']['entity'] = Array(
                    $settingSlackIwEntity->getName(),
                    $settingSlackIwEntity->getHook(),
                    $settingSlackIwEntity->getChannel()
                );
                
                $this->settingSlackIwList();
            }
            else
                $this->response['messages']['error'] = $this->utility->getTranslator()->trans("settingSlackIwController_3");
            
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
    *   name = "cp_setting_slack_iw_delete",
    *   path = "/cp_setting_slack_iw_delete/{_locale}/{urlCurrentPageId}/{urlExtra}",
    *   defaults = {"_locale" = "%locale%", "urlCurrentPageId" = "2", "urlExtra" = ""},
    *   requirements = {"_locale" = "[a-z]{2}", "urlCurrentPageId" = "\d+", "urlExtra" = "[^/]+"},
    *	methods={"POST"}
    * )
    * @Template("@templateRoot/render/control_panel/setting_slack_iw.html.twig")
    */
    public function deleteAction($_locale, $urlCurrentPageId, $urlExtra, Request $request, TranslatorInterface $translator) {
        $this->urlLocale = $_locale;
        $this->urlCurrentPageId = $urlCurrentPageId;
        $this->urlExtra = $urlExtra;
        
        $this->entityManager = $this->getDoctrine()->getManager();
        
        $this->response = Array();
        
        $this->utility = new Utility($this->container, $this->entityManager, $translator);
        $this->query = $this->utility->getQuery();
        $this->ajax = new Ajax($this->utility);
        
        $this->urlLocale = $this->utility->checkLanguage($request);
        
        $this->utility->checkSessionOverTime($request);
        
        $checkUserRole = $this->utility->checkUserRole(Array("ROLE_ADMIN"), $this->getUser());
        
        // Logic
        if ($request->isMethod("POST") == true && $checkUserRole == true) {
            if ($this->isCsrfTokenValid("intention", $request->get("token")) == true) {
                if ($request->get("event") == "delete") {
                    $id = $request->get("id") != null ? $request->get("id") : 0;
                    
                    $settingSlackIwEntity = $this->entityManager->getRepository("App\Entity\SettingSlackIw")->find($id);
                    
                    if ($settingSlackIwEntity != null) {
                        if (isset($_SESSION['settingSlackIwProfileId']) == true)
                            unset($_SESSION['settingSlackIwProfileId']);
                        
                        // Database remove
                        $this->entityManager->remove($settingSlackIwEntity);
                        $this->entityManager->flush();
                        
                        $this->settingSlackIwList();

                        $this->response['messages']['success'] = $this->utility->getTranslator()->trans("settingSlackIwController_4");
                    }
                }
                else
                    $this->response['messages']['error'] = $this->utility->getTranslator()->trans("settingSlackIwController_5");

                return $this->ajax->response(Array(
                    'urlLocale' => $this->urlLocale,
                    'urlCurrentPageId' => $this->urlCurrentPageId,
                    'urlExtra' => $this->urlExtra,
                    'response' => $this->response
                ));
            }
        }
        
        return Array(
            'urlLocale' => $this->urlLocale,
            'urlCurrentPageId' => $this->urlCurrentPageId,
            'urlExtra' => $this->urlExtra,
            'response' => $this->response
        );
    }
    
    /**
    * @Route(
    *   name = "cp_setting_slack_iw_reset",
    *   path = "/cp_setting_slack_iw_reset/{_locale}/{urlCurrentPageId}/{urlExtra}",
    *   defaults = {"_locale" = "%locale%", "urlCurrentPageId" = "2", "urlExtra" = ""},
    *   requirements = {"_locale" = "[a-z]{2}", "urlCurrentPageId" = "\d+", "urlExtra" = "[^/]+"},
    *	methods={"POST"}
    * )
    * @Template("@templateRoot/render/control_panel/setting_slack_iw.html.twig")
    */
    public function resetAction($_locale, $urlCurrentPageId, $urlExtra, Request $request, TranslatorInterface $translator) {
        $this->urlLocale = $_locale;
        $this->urlCurrentPageId = $urlCurrentPageId;
        $this->urlExtra = $urlExtra;
        
        $this->entityManager = $this->getDoctrine()->getManager();
        
        $this->response = Array();
        
        $this->utility = new Utility($this->container, $this->entityManager, $translator);
        $this->query = $this->utility->getQuery();
        $this->ajax = new Ajax($this->utility);
        
        $this->urlLocale = $this->utility->checkLanguage($request);
        
        $this->utility->checkSessionOverTime($request);
        
        $checkUserRole = $this->utility->checkUserRole(Array("ROLE_ADMIN"), $this->getUser());
        
        // Logic
        if ($request->isMethod("POST") == true && $checkUserRole == true) {
            if ($this->isCsrfTokenValid("intention", $request->get("token")) == true) {
                if ($request->get("event") == "reset") {
                    unset($_SESSION['settingSlackIwProfileId']);
                    
                    $this->response['messages']['success'] = $this->utility->getTranslator()->trans("settingSlackIwController_6");
                }
                else
                    $this->response['messages']['error'] = $this->utility->getTranslator()->trans("settingSlackIwController_7");
                
                return $this->ajax->response(Array(
                    'urlLocale' => $this->urlLocale,
                    'urlCurrentPageId' => $this->urlCurrentPageId,
                    'urlExtra' => $this->urlExtra,
                    'response' => $this->response
                ));
            }
        }
        
        return Array(
            'urlLocale' => $this->urlLocale,
            'urlCurrentPageId' => $this->urlCurrentPageId,
            'urlExtra' => $this->urlExtra,
            'response' => $this->response
        );
    }
    
    // Functions private
    private function settingSlackIwList() {
        $rows = $this->query->selectAllSettingSlackIwDatabase();
        
        $this->response['values']['settingSlackIwListHtml'] = $this->utility->createSettingSlackIwListHtml($rows);
    }
}