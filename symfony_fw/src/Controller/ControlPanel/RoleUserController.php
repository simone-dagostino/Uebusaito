<?php
namespace App\Controller\ControlPanel;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use App\Classes\System\Utility;
use App\Classes\System\Ajax;
use App\Classes\System\TableAndPagination;

use App\Entity\RoleUser;

use App\Form\RoleUserFormType;
use App\Form\RoleUserSelectFormType;

class RoleUserController extends Controller {
    // Vars
    private $urlLocale;
    private $urlCurrentPageId;
    private $urlExtra;
    
    private $entityManager;
    
    private $response;
    
    private $utility;
    private $query;
    private $ajax;
    private $tableAndPagination;
    
    // Properties
    
    // Functions public
    /**
    * @Route(
    *   name = "cp_roleUser_create",
    *   path = "/cp_roleUser_create/{_locale}/{urlCurrentPageId}/{urlExtra}",
    *   defaults = {"_locale" = "%locale%", "urlCurrentPageId" = "2", "urlExtra" = ""},
    *   requirements = {"_locale" = "[a-z]{2}", "urlCurrentPageId" = "\d+", "urlExtra" = "[^/]+"},
    *	methods={"POST"}
    * )
    * @Template("@templateRoot/render/control_panel/roleUser_create.html.twig")
    */
    public function createAction($_locale, $urlCurrentPageId, $urlExtra, Request $request) {
        $this->urlLocale = $_locale;
        $this->urlCurrentPageId = $urlCurrentPageId;
        $this->urlExtra = $urlExtra;
        
        $this->entityManager = $this->getDoctrine()->getManager();
        
        $this->response = Array();
        
        $this->utility = new Utility($this->container, $this->entityManager);
        $this->ajax = new Ajax($this->container, $this->entityManager);
        
        $this->urlLocale = $this->utility->checkLanguage($request);
        
        $this->utility->checkSessionOverTime($request);
        
        $checkUserRole = $this->utility->checkUserRole(Array("ROLE_ADMIN", "ROLE_MODERATOR"), $this->getUser()->getRoleUserId());
        
        // Logic
        $roleUserEntity = new RoleUser();
        
        $_SESSION['roleUserProfileId'] = 0;
        
        $form = $this->createForm(RoleUserFormType::class, $roleUserEntity, Array(
            'validation_groups' => Array('roleUser_create')
        ));
        $form->handleRequest($request);
        
        if ($request->isMethod("POST") == true && $checkUserRole == true) {
            if ($form->isSubmitted() == true && $form->isValid() == true) {
                // Database insert
                $this->entityManager->persist($roleUserEntity);
                $this->entityManager->flush();

                $this->response['messages']['success'] = $this->utility->getTranslator()->trans("roleUserController_1");
            }
            else {
                $this->response['messages']['error'] = $this->utility->getTranslator()->trans("roleUserController_2");
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
    *   name = "cp_roleUser_select",
    *   path = "/cp_roleUser_select/{_locale}/{urlCurrentPageId}/{urlExtra}",
    *   defaults = {"_locale" = "%locale%", "urlCurrentPageId" = "2", "urlExtra" = ""},
    *   requirements = {"_locale" = "[a-z]{2}", "urlCurrentPageId" = "\d+", "urlExtra" = "[^/]+"},
    *	methods={"POST"}
    * )
    * @Template("@templateRoot/render/control_panel/roleUser_select.html.twig")
    */
    public function selectAction($_locale, $urlCurrentPageId, $urlExtra, Request $request) {
        $this->urlLocale = $_locale;
        $this->urlCurrentPageId = $urlCurrentPageId;
        $this->urlExtra = $urlExtra;
        
        $this->entityManager = $this->getDoctrine()->getManager();
        
        $this->response = Array();
        
        $this->utility = new Utility($this->container, $this->entityManager);
        $this->query = $this->utility->getQuery();
        $this->ajax = new Ajax($this->container, $this->entityManager);
        $this->tableAndPagination = new TableAndPagination($this->container, $this->entityManager);
        
        $this->urlLocale = $this->utility->checkLanguage($request);
        
        $this->utility->checkSessionOverTime($request);
        
        $checkUserRole = $this->utility->checkUserRole(Array("ROLE_ADMIN", "ROLE_MODERATOR"), $this->getUser()->getRoleUserId());
        
        // Logic
        $_SESSION['roleUserProfileId'] = 0;
        
        $userRoleRows = $this->query->selectAllRoleUserDatabase();
        
        $tableAndPagination = $this->tableAndPagination->request($userRoleRows, 20, "role", true, true);
        
        $this->response['values']['search'] = $tableAndPagination['search'];
        $this->response['values']['pagination'] = $tableAndPagination['pagination'];
        $this->response['values']['listHtml'] = $this->createListHtml($tableAndPagination['listHtml']);
        $this->response['values']['count'] = $tableAndPagination['count'];
        
        $form = $this->createForm(RoleUserSelectFormType::class, null, Array(
            'validation_groups' => Array('roleUser_select'),
            'choicesId' => array_reverse(array_column($userRoleRows, "id", "level"), true)
        ));
        $form->handleRequest($request);
        
        if ($request->isMethod("POST") == true && $checkUserRole == true) {
            if ($this->isCsrfTokenValid("intention", $request->get("token")) == true) {
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
            'response' => $this->response,
            'form' => $form->createView()
        );
    }
    
    /**
    * @Route(
    *   name = "cp_roleUser_profile",
    *   path = "/cp_roleUser_profile/{_locale}/{urlCurrentPageId}/{urlExtra}",
    *   defaults = {"_locale" = "%locale%", "urlCurrentPageId" = "2", "urlExtra" = ""},
    *   requirements = {"_locale" = "[a-z]{2}", "urlCurrentPageId" = "\d+", "urlExtra" = "[^/]+"},
    *	methods={"POST"}
    * )
    * @Template("@templateRoot/render/control_panel/roleUser_profile.html.twig")
    */
    public function profileAction($_locale, $urlCurrentPageId, $urlExtra, Request $request) {
        $this->urlLocale = $_locale;
        $this->urlCurrentPageId = $urlCurrentPageId;
        $this->urlExtra = $urlExtra;
        
        $this->entityManager = $this->getDoctrine()->getManager();
        
        $this->response = Array();
        
        $this->utility = new Utility($this->container, $this->entityManager);
        $this->ajax = new Ajax($this->container, $this->entityManager);
        
        $this->urlLocale = $this->utility->checkLanguage($request);
        
        $this->utility->checkSessionOverTime($request);
        
        $checkUserRole = $this->utility->checkUserRole(Array("ROLE_ADMIN", "ROLE_MODERATOR"), $this->getUser()->getRoleUserId());
        
        // Logic
        if ($request->isMethod("POST") == true && $checkUserRole == true) {
            if ($this->isCsrfTokenValid("intention", $request->get("token")) == true
                    || $this->isCsrfTokenValid("intention", $request->get("form_roleUser_select")['_token']) == true) {
                $id = 0;

                if (empty($request->get("id")) == false)
                    $id = $request->get("id");
                else if (empty($request->get("form_roleUser_select")['id']) == false)
                    $id = $request->get("form_roleUser_select")['id'];

                $roleUserEntity = $this->entityManager->getRepository("App\Entity\RoleUser")->find($id);

                if ($roleUserEntity != null) {
                    $_SESSION['roleUserProfileId'] = $id;

                    $form = $this->createForm(RoleUserFormType::class, $roleUserEntity, Array(
                        'validation_groups' => Array('roleUser_profile')
                    ));
                    $form->handleRequest($request);

                    $this->response['values']['id'] = $_SESSION['roleUserProfileId'];

                    $this->response['render'] = $this->renderView("@templateRoot/render/control_panel/roleUser_profile.html.twig", Array(
                        'urlLocale' => $this->urlLocale,
                        'urlCurrentPageId' => $this->urlCurrentPageId,
                        'urlExtra' => $this->urlExtra,
                        'response' => $this->response,
                        'form' => $form->createView()
                    ));
                }
                else
                    $this->response['messages']['error'] = $this->utility->getTranslator()->trans("roleUserController_3");
            }
        }
        
        return $this->ajax->response(Array(
            'urlLocale' => $this->urlLocale,
            'urlCurrentPageId' => $this->urlCurrentPageId,
            'urlExtra' => $this->urlExtra,
            'response' => $this->response
        ));
    }
    
    /**
    * @Route(
    *   name = "cp_roleUser_profile_save",
    *   path = "/cp_roleUser_profile_save/{_locale}/{urlCurrentPageId}/{urlExtra}",
    *   defaults = {"_locale" = "%locale%", "urlCurrentPageId" = "2", "urlExtra" = ""},
    *   requirements = {"_locale" = "[a-z]{2}", "urlCurrentPageId" = "\d+", "urlExtra" = "[^/]+"},
    *	methods={"POST"}
    * )
    * @Template("@templateRoot/render/control_panel/roleUser_profile.html.twig")
    */
    public function profileSaveAction($_locale, $urlCurrentPageId, $urlExtra, Request $request) {
        $this->urlLocale = $_locale;
        $this->urlCurrentPageId = $urlCurrentPageId;
        $this->urlExtra = $urlExtra;
        
        $this->entityManager = $this->getDoctrine()->getManager();
        
        $this->response = Array();
        
        $this->utility = new Utility($this->container, $this->entityManager);
        $this->query = $this->utility->getQuery();
        $this->ajax = new Ajax($this->container, $this->entityManager);
        
        $this->urlLocale = $this->utility->checkLanguage($request);
        
        $this->utility->checkSessionOverTime($request);
        
        $checkUserRole = $this->utility->checkUserRole(Array("ROLE_ADMIN", "ROLE_MODERATOR"), $this->getUser()->getRoleUserId());
        
        // Logic
        $roleUserEntity = $this->entityManager->getRepository("App\Entity\RoleUser")->find($_SESSION['roleUserProfileId']);
        
        $form = $this->createForm(RoleUserFormType::class, $roleUserEntity, Array(
            'validation_groups' => Array('roleUser_profile')
        ));
        $form->handleRequest($request);
        
        if ($request->isMethod("POST") == true && $checkUserRole == true) {
            if ($form->isSubmitted() == true && $form->isValid() == true) {
                // Database update
                $this->entityManager->persist($roleUserEntity);
                $this->entityManager->flush();
                
                $userRows = $this->query->selectAllUserDatabase();
                
                foreach ($userRows as $key => $value) {
                    $roleRow = $this->query->selectRoleUserDatabase($value['role_user_id']);
                    
                    $roleImplode = implode(",", $roleRow);
                    
                    $this->roleUserDatabase("update", $value['id'], $roleImplode);
                }
                
                $this->response['messages']['success'] = $this->utility->getTranslator()->trans("roleUserController_4");
            }
            else {
                $this->response['messages']['error'] = $this->utility->getTranslator()->trans("roleUserController_5");
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
    *   name = "cp_roleUser_delete",
    *   path = "/cp_roleUser_delete/{_locale}/{urlCurrentPageId}/{urlExtra}",
    *   defaults = {"_locale" = "%locale%", "urlCurrentPageId" = "2", "urlExtra" = ""},
    *   requirements = {"_locale" = "[a-z]{2}", "urlCurrentPageId" = "\d+", "urlExtra" = "[^/]+"},
    *	methods={"POST"}
    * )
    * @Template("@templateRoot/render/control_panel/roleUser_delete.html.twig")
    */
    public function deleteAction($_locale, $urlCurrentPageId, $urlExtra, Request $request) {
        $this->urlLocale = $_locale;
        $this->urlCurrentPageId = $urlCurrentPageId;
        $this->urlExtra = $urlExtra;
        
        $this->entityManager = $this->getDoctrine()->getManager();
        
        $this->response = Array();
        
        $this->utility = new Utility($this->container, $this->entityManager);
        $this->query = $this->utility->getQuery();
        $this->ajax = new Ajax($this->container, $this->entityManager);
        
        $this->urlLocale = $this->utility->checkLanguage($request);
        
        $this->utility->checkSessionOverTime($request);
        
        $checkUserRole = $this->utility->checkUserRole(Array("ROLE_ADMIN"), $this->getUser()->getRoleUserId());
        
        // Logic
        if ($request->isMethod("POST") == true && $checkUserRole == true) {
            if ($this->isCsrfTokenValid("intention", $request->get("token")) == true) {
                if ($request->get("event") == "delete") {
                    $id = $request->get("id") == null ? $_SESSION['roleUserProfileId'] : $request->get("id");

                    $roleUserDatabase = $this->roleUserDatabase("delete", $id);

                    if ($roleUserDatabase == true) {
                        $this->deleteFromTable("delete", $this->query, $id);
                        
                        $this->response['values']['id'] = $id;

                        $this->response['messages']['success'] = $this->utility->getTranslator()->trans("roleUserController_6");
                    }
                }
                else if ($request->get("event") == "deleteAll") {
                    $roleUserDatabase = $this->roleUserDatabase("deleteAll");

                    if ($roleUserDatabase == true) {
                        $this->deleteFromTable("deleteAll", $this->query);
                        
                        $this->response['messages']['success'] = $this->utility->getTranslator()->trans("roleUserController_7");
                    }
                }
                else
                    $this->response['messages']['error'] = $this->utility->getTranslator()->trans("roleUserController_8");

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
    private function createListHtml($tableResult) {
        $listHtml = "";
        
        foreach ($tableResult as $key => $value) {
            $listHtml .= "<tr>
                <td class=\"id_column\">
                    {$value['id']}
                </td>
                <td class=\"checkbox_column\">
                    <div class=\"mdc-checkbox\">
                        <input class=\"mdc-checkbox__native-control\" type=\"checkbox\"/>
                        <div class=\"mdc-checkbox__background\">
                            <svg class=\"mdc-checkbox__checkmark\" viewBox=\"0 0 24 24\">
                                <path class=\"mdc-checkbox__checkmark-path\" fill=\"none\" stroke=\"white\" d=\"M1.73,12.91 8.1,19.28 22.79,4.59\"/>
                            </svg>
                            <div class=\"mdc-checkbox__mixedmark\"></div>
                        </div>
                    </div>
                </td>
                <td>
                    {$value['level']}
                </td>";
                $listHtml .= "<td class=\"horizontal_center\">";
                    if ($value['id'] > 4)
                        $listHtml .= "<button class=\"mdc-fab mdc-fab--mini cp_roleUser_delete\" type=\"button\" aria-label=\"Delete\"><span class=\"mdc-fab__icon material-icons\">delete</span></button>
                </td>
            </tr>";
        }
        
        return $listHtml;
    }
    
    private function roleUserDatabase($type, $id = null, $roles = null) {
        if ($type == "update") {
            $query = $this->utility->getConnection()->prepare("UPDATE users
                                                                SET roles = :roles
                                                                WHERE id = :id");

            $query->bindValue(":roles", $roles);
            $query->bindValue(":id", $id);

            $query->execute();
        }
        else if ($type == "delete") {
            $query = $this->utility->getConnection()->prepare("DELETE FROM roles_users
                                                                WHERE id > :idExclude
                                                                AND id = :id");
            
            $query->bindValue(":idExclude", 4);
            $query->bindValue(":id", $id);
            
            return $query->execute();
        }
        else if ($type == "deleteAll") {
            $query = $this->utility->getConnection()->prepare("DELETE FROM roles_users
                                                                WHERE id > :idExclude");
            
            $query->bindValue(":idExclude", 4);
            
            return $query->execute();
        }
    }
    
    private function deleteFromTable($type, $query, $id = null) {
        $pageRows = $query->selectAllPageDatabase($this->urlLocale);
        $userRows = $query->selectAllUserDatabase(1);
        $settingRow = $query->selectSettingDatabase();
        
        if ($type == "delete") {
            foreach ($pageRows as $key => $value) {
                $roleExplode = explode(",", $value['role_user_id']);
                
                $key = array_search($id, $roleExplode);
                
                if ($key !== false) {
                    unset($roleExplode[$key]);
                    
                    $roleImplode = implode(",", $roleExplode);
                    
                    $queryPage = $this->utility->getConnection()->prepare("UPDATE pages
                                                                            SET role_user_id = :roleImplode
                                                                            WHERE id = :id");
                    
                    $queryPage->bindValue(":roleImplode", $roleImplode);
                    $queryPage->bindValue(":id", $value['id']);
                    
                    $queryPage->execute();
                }
            }
            
            foreach ($userRows as $key => $value) {
                $roleExplode = explode(",", $value['role_user_id']);
                
                $key = array_search($id, $roleExplode);
                
                if ($key !== false) {
                    unset($roleExplode[$key]);
                    
                    $roleImplode = implode(",", $roleExplode);
                    
                    $roleUserRow = $query->selectRoleUserDatabase($roleImplode);
                    
                    $roleUserImplode = implode(",", $roleUserRow);
                    
                    $queryUser = $this->utility->getConnection()->prepare("UPDATE users
                                                                            SET role_user_id = :roleImplode,
                                                                                roles = :roleUserImplode
                                                                            WHERE id = :id");
                    
                    $queryUser->bindValue(":roleImplode", $roleImplode);
                    $queryUser->bindValue(":roleUserImplode", $roleUserImplode);
                    $queryUser->bindValue(":id", $value['id']);
                    
                    $queryUser->execute();
                }
            }
            
            $roleExplode = explode(",", $settingRow['role_user_id']);
            
            $key = array_search($id, $roleExplode);
            
            if ($key !== false) {
                unset($roleExplode[$key]);
                
                $roleImplode = implode(",", $roleExplode);
                
                $querySetting = $this->utility->getConnection()->prepare("UPDATE settings
                                                                            SET role_user_id = :roleImplode
                                                                            WHERE id = :id");
                
                $querySetting->bindValue(":roleImplode", $roleImplode);
                $querySetting->bindValue(":id", 1);
                
                $querySetting->execute();
            }
        }
        else if ($type == "deleteAll") {
            foreach ($pageRows as $key => $value) {
                $roleImplode = $this->roleImplode($value['role_user_id']);
                
                $queryPage = $this->utility->getConnection()->prepare("UPDATE pages
                                                                        SET role_user_id = :roleImplode
                                                                        WHERE id = :id");
                
                $queryPage->bindValue(":roleImplode", $roleImplode);
                $queryPage->bindValue(":id", $value['id']);
                
                $queryPage->execute();
            }
            
            foreach ($userRows as $key => $value) {
                $roleImplode = $this->roleImplode($value['role_user_id']);
                
                $roleUserRow = $this->query->selectRoleUserDatabase($roleImplode);
                
                $roleUserImplode = implode(",", $roleUserRow);
                
                $queryUser = $this->utility->getConnection()->prepare("UPDATE users
                                                                        SET role_user_id = :roleImplode,
                                                                            roles = :roleUserImplode
                                                                        WHERE id = :id");
                
                $queryUser->bindValue(":roleImplode", $roleImplode);
                $queryUser->bindValue(":roleUserImplode", $roleUserImplode);
                $queryUser->bindValue(":id", $value['id']);
                
                $queryUser->execute();
            }
            
            $roleImplode = $this->roleImplode($settingRow['role_user_id']);
            
            $querySetting = $this->utility->getConnection()->prepare("UPDATE settings
                                                                        SET role_user_id = :roleImplode
                                                                        WHERE id = :id");
            
            $querySetting->bindValue(":roleImplode", $roleImplode);
            $querySetting->bindValue(":id", 1);
            
            $querySetting->execute();
        }
    }
    
    private function roleImplode($roleUserId) {
        $roleExplode = explode(",", $roleUserId);

        for ($i = 0; $i < count($roleExplode); $i ++) {
            if (intval($roleExplode[$i]) > 4)
                unset($roleExplode[$i]);
        }
        
        if (isset($roleExplode[0]) == false && empty($roleExplode[1]) == true)
            $roleExplode[1] = "1,";

        return implode(",", $roleExplode);
    }
}