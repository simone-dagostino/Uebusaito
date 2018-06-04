<?php
namespace ReinventSoftware\UebusaitoBundle\Controller\ControlPanel;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use ReinventSoftware\UebusaitoBundle\Classes\System\Utility;
use ReinventSoftware\UebusaitoBundle\Classes\System\Ajax;
use ReinventSoftware\UebusaitoBundle\Classes\System\TableAndPagination;

use ReinventSoftware\UebusaitoBundle\Entity\User;

use ReinventSoftware\UebusaitoBundle\Form\UserFormType;
use ReinventSoftware\UebusaitoBundle\Form\UserSelectionFormType;

class UserController extends Controller {
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
    *   name = "cp_user_creation",
    *   path = "/cp_user_creation/{_locale}/{urlCurrentPageId}/{urlExtra}",
    *   defaults = {"_locale" = "%locale%", "urlCurrentPageId" = "2", "urlExtra" = ""},
    *   requirements = {"_locale" = "[a-z]{2}", "urlCurrentPageId" = "\d+", "urlExtra" = ".*"}
    * )
    * @Method({"POST"})
    * @Template("@UebusaitoBundleViews/render/control_panel/user_creation.html.twig")
    */
    public function creationAction($_locale, $urlCurrentPageId, $urlExtra, Request $request) {
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
        $userEntity = new User();
        
        $form = $this->createForm(UserFormType::class, $userEntity, Array(
            'validation_groups' => Array('user_creation')
        ));
        $form->handleRequest($request);
        
        $this->response['values']['userRoleSelectHtml'] = $this->utility->createUserRoleSelectHtml("form_user_roleUserId_field", true);
        
        if ($request->isMethod("POST") == true && $checkUserRole == true) {
            if ($form->isValid() == true) {
                $messagePassword = $this->utility->assignUserPassword("withoutOld", $userEntity, $form);

                if ($messagePassword == "ok") {
                    $this->utility->assignUserParameter($userEntity);

                    mkdir("{$this->utility->getPathSrcBundle()}/Resources/files/{$form->get("username")->getData()}");
                    mkdir("{$this->utility->getPathSrcBundle()}/Resources/public/files/{$form->get("username")->getData()}");
                    mkdir("{$this->utility->getPathWebBundle()}/files/{$form->get("username")->getData()}");
                    
                    // Insert in database
                    $this->entityManager->persist($userEntity);
                    $this->entityManager->flush();

                    $this->response['messages']['success'] = $this->utility->getTranslator()->trans("userController_1");
                }
                else
                    $this->response['messages']['error'] = $messagePassword;
            }
            else {
                $this->response['messages']['error'] = $this->utility->getTranslator()->trans("userController_2");
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
    *   name = "cp_user_selection",
    *   path = "/cp_user_selection/{_locale}/{urlCurrentPageId}/{urlExtra}",
    *   defaults = {"_locale" = "%locale%", "urlCurrentPageId" = "2", "urlExtra" = ""},
    *   requirements = {"_locale" = "[a-z]{2}", "urlCurrentPageId" = "\d+", "urlExtra" = ".*"}
    * )
    * @Method({"POST"})
    * @Template("@UebusaitoBundleViews/render/control_panel/user_selection.html.twig")
    */
    public function selectionAction($_locale, $urlCurrentPageId, $urlExtra, Request $request) {
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
        $_SESSION['userProfileId'] = 0;
        
        $userRows = $this->query->selectAllUserDatabase(1);
        
        $tableAndPagination = $this->tableAndPagination->request($userRows, 20, "user", true, true);
        
        $this->response['values']['search'] = $tableAndPagination['search'];
        $this->response['values']['pagination'] = $tableAndPagination['pagination'];
        $this->response['values']['listHtml'] = $this->createListHtml($userRows, $tableAndPagination['listHtml']);
        
        $form = $this->createForm(UserSelectionFormType::class, null, Array(
            'validation_groups' => Array('user_selection'),
            'choicesId' => array_reverse(array_column($userRows, "id", "username"), true)
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
    *   name = "cp_user_profile_result",
    *   path = "/cp_user_profile_result/{_locale}/{urlCurrentPageId}/{urlExtra}",
    *   defaults = {"_locale" = "%locale%", "urlCurrentPageId" = "2", "urlExtra" = ""},
    *   requirements = {"_locale" = "[a-z]{2}", "urlCurrentPageId" = "\d+", "urlExtra" = ".*"}
    * )
    * @Method({"POST"})
    * @Template("@UebusaitoBundleViews/render/control_panel/user_profile.html.twig")
    */
    public function profileResultAction($_locale, $urlCurrentPageId, $urlExtra, Request $request) {
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
                    || $this->isCsrfTokenValid("intention", $request->get("form_user_selection")['_token']) == true) {
                $id = 0;

                if (empty($request->get("id")) == false)
                    $id = $request->get("id");
                else if (empty($request->get("form_user_selection")['id']) == false)
                    $id = $request->get("form_user_selection")['id'];

                $userEntity = $this->entityManager->getRepository("UebusaitoBundle:User")->find($id);

                if ($userEntity != null) {
                    $_SESSION['userProfileId'] = $id;

                    $form = $this->createForm(UserFormType::class, $userEntity, Array(
                        'validation_groups' => Array('user_profile')
                    ));
                    $form->handleRequest($request);

                    $this->response['values']['userRoleSelectHtml'] = $this->utility->createUserRoleSelectHtml("form_user_roleUserId_field", true);
                    $this->response['values']['id'] = $_SESSION['userProfileId'];
                    $this->response['values']['credit'] = $userEntity->getCredit();

                    $this->response['render'] = $this->renderView("@UebusaitoBundleViews/render/control_panel/user_profile.html.twig", Array(
                        'urlLocale' => $this->urlLocale,
                        'urlCurrentPageId' => $this->urlCurrentPageId,
                        'urlExtra' => $this->urlExtra,
                        'response' => $this->response,
                        'form' => $form->createView()
                    ));
                }
                else
                    $this->response['messages']['error'] = $this->utility->getTranslator()->trans("userController_3");
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
    *   name = "cp_user_profile_save",
    *   path = "/cp_user_profile_save/{_locale}/{urlCurrentPageId}/{urlExtra}",
    *   defaults = {"_locale" = "%locale%", "urlCurrentPageId" = "2", "urlExtra" = ""},
    *   requirements = {"_locale" = "[a-z]{2}", "urlCurrentPageId" = "\d+", "urlExtra" = ".*"}
    * )
    * @Method({"POST"})
    * @Template("@UebusaitoBundleViews/render/control_panel/user_profile.html.twig")
    */
    public function profileSaveAction($_locale, $urlCurrentPageId, $urlExtra, Request $request) {
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
        $userEntity = $this->entityManager->getRepository("UebusaitoBundle:User")->find($_SESSION['userProfileId']);
        
        $form = $this->createForm(UserFormType::class, $userEntity, Array(
            'validation_groups' => Array('user_profile')
        ));
        $form->handleRequest($request);
        
        if ($request->isMethod("POST") == true && $checkUserRole == true) {
            if ($form->isValid() == true) {
                $messagePassword = $this->utility->assignUserPassword("withoutOld", $userEntity, $form);

                if ($messagePassword == "ok") {
                    $usernameOld = $userEntity->getUsername();

                    if (file_exists("{$this->utility->getPathSrcBundle()}/Resources/files/$usernameOld") == true)
                        @rename("{$this->utility->getPathSrcBundle()}/Resources/files/$usernameOld",
                                "{$this->utility->getPathSrcBundle()}/Resources/files/{$form->get("username")->getData()}");

                    if (file_exists("{$this->utility->getPathSrcBundle()}/Resources/public/files/$usernameOld") == true)
                        @rename("{$this->utility->getPathSrcBundle()}/Resources/public/files/$usernameOld",
                                "{$this->utility->getPathSrcBundle()}/Resources/public/files/{$form->get("username")->getData()}");

                    if (file_exists("{$this->utility->getPathWebBundle()}/files/$usernameOld") == true)
                        @rename("{$this->utility->getPathWebBundle()}/files/$usernameOld",
                                "{$this->utility->getPathWebBundle()}/files/{$form->get("username")->getData()}");

                    if ($form->get("notLocked")->getData() == true)
                        $userEntity->setHelpCode("");

                    // Update in database
                    $this->entityManager->persist($userEntity);
                    $this->entityManager->flush();

                    $this->response['messages']['success'] = $this->utility->getTranslator()->trans("userController_4");
                }
                else
                    $this->response['messages']['error'] = $messagePassword;
            }
            else {
                $this->response['messages']['error'] = $this->utility->getTranslator()->trans("userController_5");
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
    *   name = "cp_user_deletion",
    *   path = "/cp_user_deletion/{_locale}/{urlCurrentPageId}/{urlExtra}",
    *   defaults = {"_locale" = "%locale%", "urlCurrentPageId" = "2", "urlExtra" = ""},
    *   requirements = {"_locale" = "[a-z]{2}", "urlCurrentPageId" = "\d+", "urlExtra" = ".*"}
    * )
    * @Method({"POST"})
    * @Template("@UebusaitoBundleViews/render/control_panel/user_deletion.html.twig")
    */
    public function deletionAction($_locale, $urlCurrentPageId, $urlExtra, Request $request) {
        $this->urlLocale = $_locale;
        $this->urlCurrentPageId = $urlCurrentPageId;
        $this->urlExtra = $urlExtra;
        
        $this->entityManager = $this->getDoctrine()->getManager();
        
        $this->response = Array();
        
        $this->utility = new Utility($this->container, $this->entityManager);
        $this->ajax = new Ajax($this->container, $this->entityManager);
        
        $this->urlLocale = $this->utility->checkLanguage($request);
        
        $this->utility->checkSessionOverTime($request);
        
        $checkUserRole = $this->utility->checkUserRole(Array("ROLE_ADMIN"), $this->getUser()->getRoleUserId());
        
        // Logic
        if ($request->isMethod("POST") == true && $checkUserRole == true) {
            if ($this->isCsrfTokenValid("intention", $request->get("token")) == true) {
                if ($request->get("event") == "delete") {
                    $id = $request->get("id") == null ? $_SESSION['userProfileId'] : $request->get("id");

                    $userEntity = $this->entityManager->getRepository("UebusaitoBundle:User")->find($id);

                    $this->utility->removeDirRecursive("{$this->utility->getPathSrcBundle()}/Resources/files/{$userEntity->getUsername()}", true);
                    $this->utility->removeDirRecursive("{$this->utility->getPathSrcBundle()}/Resources/public/files/{$userEntity->getUsername()}", true);
                    $this->utility->removeDirRecursive("{$this->utility->getPathWebBundle()}/files/{$userEntity->getUsername()}", true);

                    $userDatabase = $this->userDatabase("delete", $userEntity->getId());

                    if ($userDatabase == true) {
                        $this->response['values']['id'] = $id;

                        $this->response['messages']['success'] = $this->utility->getTranslator()->trans("userController_6");
                    }
                }
                else if ($request->get("event") == "deleteAll") {
                    $userRows = $this->query->selectAllUserDatabase(1);

                    for ($a = 0; $a < count($userRows); $a ++) {
                        $this->utility->removeDirRecursive("{$this->utility->getPathSrcBundle()}/Resources/files/{$userRows[$a]['username']}", true);
                        $this->utility->removeDirRecursive("{$this->utility->getPathSrcBundle()}/Resources/public/files/{$userRows[$a]['username']}", true);
                        $this->utility->removeDirRecursive("{$this->utility->getPathWebBundle()}/files/{$userRows[$a]['username']}", true);
                    }

                    $userDatabase = $this->userDatabase("deleteAll", null);

                    if ($userDatabase == true)
                        $this->response['messages']['success'] = $this->utility->getTranslator()->trans("userController_7");
                }
                else
                    $this->response['messages']['error'] = $this->utility->getTranslator()->trans("userController_8");

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
    private function createListHtml($userRows, $tableResult) {
        $listHtml = "";
        
        $roleUserRow = Array();
        
        foreach ($userRows as $key => $value)
            $roleUserRow[] = $this->query->selectRoleUserDatabase($value['role_user_id'], true);
        
        foreach ($tableResult as $key => $value) {
            $listHtml .= "<tr>
                <td class=\"id_column\">
                    {$value['id']}
                </td>
                <td class=\"checkbox_column\">
                    <input class=\"display_inline margin_clear\" type=\"checkbox\"/>
                </td>
                <td>
                    {$roleUserRow[$key][0]}
                </td>
                <td>
                    {$value['username']}
                </td>
                <td>
                    {$value['name']}
                </td>
                <td>
                    {$value['surname']}
                </td>
                <td>
                    {$value['email']}
                </td>
                <td>
                    {$value['company_name']}
                </td>
                <td>";
                    if ($value['not_locked'] == 0)
                        $listHtml .= $this->utility->getTranslator()->trans("userController_9");
                    else
                        $listHtml .= $this->utility->getTranslator()->trans("userController_10");
                $listHtml .= "</td>
                <td class=\"horizontal_center\">
                    <button class=\"cp_user_deletion button_custom_danger\"><i class=\"fa fa-remove\"></i></button>
                </td>
            </tr>";
        }
        
        return $listHtml;
    }
    
    private function userDatabase($type, $id) {
        if ($type == "delete") {
            $query = $this->utility->getConnection()->prepare("DELETE FROM users
                                                                WHERE id > :idExclude
                                                                AND id = :id");
            
            $query->bindValue(":idExclude", 1);
            $query->bindValue(":id", $id);

            $query->execute();
            
            // Payments
            $query = $this->utility->getConnection()->prepare("DELETE FROM payments
                                                                WHERE user_id > :idExclude
                                                                AND user_id = :id");
            
            $query->bindValue(":idExclude", 1);
            $query->bindValue(":id", $id);

            return $query->execute();
        }
        else if ($type == "deleteAll") {
            $query = $this->utility->getConnection()->prepare("DELETE FROM users
                                                                WHERE id > :idExclude");

            $query->bindValue(":idExclude", 1);

            $query->execute();
            
            // Payments
            $query = $this->utility->getConnection()->prepare("DELETE FROM payments
                                                                WHERE user_id > :idExclude");
            
            $query->bindValue(":idExclude", 1);

            return $query->execute();
        }
    }
}