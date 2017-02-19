<?php
namespace ReinventSoftware\UebusaitoBundle\Controller\ControlPanel;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use ReinventSoftware\UebusaitoBundle\Entity\Page;

use ReinventSoftware\UebusaitoBundle\Form\PageFormType;

use ReinventSoftware\UebusaitoBundle\Form\PagesSelectionFormType;
use ReinventSoftware\UebusaitoBundle\Form\Model\PagesSelectionModel;

use ReinventSoftware\UebusaitoBundle\Classes\Utility;
use ReinventSoftware\UebusaitoBundle\Classes\Ajax;
use ReinventSoftware\UebusaitoBundle\Classes\Table;

class PageController extends Controller {
    // Vars
    private $urlLocale;
    private $urlCurrentPageId;
    private $urlExtra;
    
    private $entityManager;
    private $requestStack;
    private $translator;
    
    private $utility;
    private $ajax;
    private $table;
    
    private $listHtml;
    
    private $response;
    
    // Properties
    
    // Functions public
    /**
     * @Template("UebusaitoBundle:render:control_panel/page_creation.html.twig")
     */
    public function creationAction($_locale, $urlCurrentPageId, $urlExtra) {
        $this->urlLocale = $_locale;
        $this->urlCurrentPageId = $urlCurrentPageId;
        $this->urlExtra = $urlExtra;
        
        $this->entityManager = $this->getDoctrine()->getManager();
        $this->requestStack = $this->get("request_stack")->getCurrentRequest();
        $this->translator = $this->get("translator");
        
        $this->utility = new Utility($this->container, $this->entityManager);
        $this->ajax = new Ajax($this->translator);
        
        $this->response = Array();
        
        $page = new Page();
        
        // Create form
        $pageFormType = new PageFormType($this->urlLocale, $this->utility, $page);
        $form = $this->createForm($pageFormType, $page, Array(
            'validation_groups' => Array(
                'page_creation'
            )
        ));
        
        $this->response['rolesSelect'] = $this->utility->createRolesSelectHtml("form_page_roleId_field", "required=\"required\"");
        
        // Request post
        if ($this->requestStack->getMethod() == "POST") {
            $sessionActivity = $this->utility->checkSessionOverTime($this->container, $this->requestStack);
            
            if ($sessionActivity != "")
                $this->response['session']['activity'] = $sessionActivity;
            else {
                $form->handleRequest($this->requestStack);

                // Check form
                if ($form->isValid() == true) {
                    // Insert in database
                    $this->entityManager->persist($page);
                    $this->entityManager->flush();

                    $this->pagesInDatabase("insert", $form, null, $this->urlLocale);

                    $this->response['messages']['success'] = $this->translator->trans("pageController_1");
                }
                else {
                    $this->response['messages']['error'] = $this->translator->trans("pageController_2");
                    $this->response['errors'] = $this->ajax->errors($form);
                }
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
     * @Template("UebusaitoBundle:render:control_panel/pages_selection.html.twig")
     */
    public function selectionAction($_locale, $urlCurrentPageId, $urlExtra) {
        $this->urlLocale = $_locale;
        $this->urlCurrentPageId = $urlCurrentPageId;
        $this->urlExtra = $urlExtra;
        
        $this->entityManager = $this->getDoctrine()->getManager();
        $this->requestStack = $this->get("request_stack")->getCurrentRequest();
        $this->translator = $this->get("translator");
        
        $this->utility = new Utility($this->container, $this->entityManager);
        $this->ajax = new Ajax($this->translator);
        $this->table = new Table($this->utility);
        
        $this->listHtml = "";
        
        $this->response = Array();
        
        // Create form
        $pagesSelectionFormType = new PagesSelectionFormType($this->urlLocale, $this->utility);
        $form = $this->createForm($pagesSelectionFormType, new PagesSelectionModel(), Array(
            'validation_groups' => Array(
                'pages_selection'
            )
        ));
        
        $pageRows = $this->utility->getQuery()->selectAllPagesFromDatabase($this->urlLocale);
        
        $tableResult = $this->table->request($pageRows, 20, "page", false, true);
        
        $this->listHtml($tableResult['list']);
        
        $this->response['values']['search'] = $tableResult['search'];
        $this->response['values']['pagination'] = $tableResult['pagination'];
        $this->response['values']['list'] = $this->listHtml;
        
        // Request post
        if ($this->requestStack->getMethod() == "POST") {
            $id = 0;
            
            $sessionActivity = $this->utility->checkSessionOverTime($this->container, $this->requestStack);
            
            if ($sessionActivity != "")
                $this->response['session']['activity'] = $sessionActivity;
            else {
                $form->handleRequest($this->requestStack);
                
                // Check form
                if ($form->isValid() == true) {
                    $id = $form->get("id")->getData();
                    
                    $this->selectionResult($id);
                }
                else if (isset($_SESSION['token']) == true && $this->requestStack->request->get("token") == $_SESSION['token'] && $form->isValid() == false && $this->requestStack->request->get("event") == null) {
                    $id = $this->requestStack->request->get("id") == "" ? 0 : $this->requestStack->request->get("id");
                    
                    $this->selectionResult($id);
                }
                else if (isset($_POST['searchWritten']) == true && isset($_POST['paginationCurrent']) == true || (isset($_SESSION['token']) == true && $this->requestStack->request->get("token") == $_SESSION['token'] && $this->requestStack->request->get("event") == "refresh")) {
                    $render = $this->renderView("UebusaitoBundle::render/control_panel/pages_selection_desktop.html.twig", Array(
                        'urlLocale' => $this->urlLocale,
                        'urlCurrentPageId' => $this->urlCurrentPageId,
                        'urlExtra' => $this->urlExtra,
                        'response' => $this->response
                    ));
                    
                    $this->response['render'] = $render;
                }
                else {
                    $this->response['messages']['error'] = $this->translator->trans("pageController_3");
                    $this->response['errors'] = $this->ajax->errors($form);
                }
            }
            
            return $this->ajax->response(Array(
                'urlLocale' => $this->urlLocale,
                'urlCurrentPageId' => $this->urlCurrentPageId,
                'urlExtra' => $id,
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
     * @Template("UebusaitoBundle:render:control_panel/page_profile.html.twig")
     */
    public function profileAction($_locale, $urlCurrentPageId, $urlExtra) {
        $this->urlLocale = $_locale;
        $this->urlCurrentPageId = $urlCurrentPageId;
        $this->urlExtra = $urlExtra;
        
        $this->entityManager = $this->getDoctrine()->getManager();
        $this->requestStack = $this->get("request_stack")->getCurrentRequest();
        $this->translator = $this->get("translator");
        
        $this->utility = new Utility($this->container, $this->entityManager);
        $this->ajax = new Ajax($this->translator);
        
        $this->response = Array();
        
        $page = $this->entityManager->getRepository("UebusaitoBundle:Page")->find($this->urlExtra);
        
        // Create form
        $pageFormType = new PageFormType($this->urlLocale, $this->utility, $page);
        $form = $this->createForm($pageFormType, $page, Array(
            'validation_groups' => Array(
                'page_profile'
            )
        ));
        
        $this->response['rolesSelect'] = $this->utility->createRolesSelectHtml("form_page_roleId_field", "required=\"required\"");
        
        // Request post
        if ($this->requestStack->getMethod() == "POST") {
            $sessionActivity = $this->utility->checkSessionOverTime($this->container, $this->requestStack);
            
            if ($sessionActivity != "")
                $this->response['session']['activity'] = $sessionActivity;
            else {
                $form->handleRequest($this->requestStack);

                // Check form
                if ($form->isValid() == true) {
                    // Insert in database
                    $this->entityManager->persist($page);
                    $this->entityManager->flush();

                    $this->pagesInDatabase("update", $form, $page, null);

                    $this->response['messages']['success'] = $this->translator->trans("pageController_4");
                }
                else {
                    $this->response['messages']['error'] = $this->translator->trans("pageController_5");
                    $this->response['errors'] = $this->ajax->errors($form);
                }
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
     * @Template("UebusaitoBundle:render:control_panel/page_deletion.html.twig")
     */
    public function deletionAction($_locale, $urlCurrentPageId, $urlExtra) {
        $this->urlLocale = $_locale;
        $this->urlCurrentPageId = $urlCurrentPageId;
        $this->urlExtra = $urlExtra;
        
        $this->entityManager = $this->getDoctrine()->getManager();
        $this->requestStack = $this->get("request_stack")->getCurrentRequest();
        $this->translator = $this->get("translator");
        
        $this->utility = new Utility($this->container, $this->entityManager);
        $this->ajax = new Ajax($this->translator);
        $this->table = new Table($this->utility);
        
        $this->listHtml = "";
        
        $this->response = Array();
        
        $pageRows = $this->utility->getQuery()->selectAllPagesFromDatabase($this->urlLocale);
        
        $tableResult = $this->table->request($pageRows, 20, "page", false, true);
        
        $this->listHtml($tableResult['list']);
        
        $this->response['values']['search'] = $tableResult['search'];
        $this->response['values']['pagination'] = $tableResult['pagination'];
        $this->response['values']['list'] = $this->listHtml;
        
        // Request post
        if ($this->requestStack->getMethod() == "POST") {
            $sessionActivity = $this->utility->checkSessionOverTime($this->container, $this->requestStack);
            
            if ($sessionActivity != "")
                $this->response['session']['activity'] = $sessionActivity;
            else {
                if (isset($_SESSION['token']) == true && $this->requestStack->request->get("token") == $_SESSION['token'] && $this->requestStack->request->get("event") == null) {
                    $id = $this->requestStack->request->get("id") == "" ? 0 : $this->requestStack->request->get("id");
                    
                    $page = $this->entityManager->getRepository("UebusaitoBundle:Page")->find($id);

                    $pageChildrenRows = $this->utility->getQuery()->selectAllPageChildrenIdFromDatabase($page);
                    
                    if ($pageChildrenRows == false) {
                        // Remove from database
                        $this->pagesInDatabase("delete", null, $page, null);

                        $this->response['messages']['success'] = $this->translator->trans("pageController_6");
                    }
                    else {
                        // Popup
                        $this->response['values']['id'] = $id;
                        $this->response['values']['text'] = "<p class=\"margin_bottom\">" . $this->translator->trans("pageController_7") . "</p>";
                        $this->response['values']['button'] = "<button id=\"cp_page_deletion_parent_all\" class=\"margin_bottom\">" . $this->translator->trans("pageController_8") . "</button>";
                        $this->response['values']['select'] = $this->utility->createPagesSelectHtml($this->urlLocale, "cp_page_deletion_parent_new");
                    }
                }
                else if (isset($_SESSION['token']) == true && $this->requestStack->request->get("token") == $_SESSION['token'] && $this->requestStack->request->get("event") == "deleteAll") {
                    // Remove from database
                    $this->pagesInDatabase("deleteAll", null, null, null);
                    
                    $render = $this->renderView("UebusaitoBundle::render/control_panel/pages_selection_desktop.html.twig", Array(
                        'urlLocale' => $this->urlLocale,
                        'urlCurrentPageId' => $this->urlCurrentPageId,
                        'urlExtra' => $this->urlExtra,
                        'response' => $this->response
                    ));
                    
                    $this->response['render'] = $render;

                    $this->response['messages']['success'] = $this->translator->trans("pageController_9");
                }
                else if (isset($_SESSION['token']) == true && $this->requestStack->request->get("token") == $_SESSION['token'] && $this->requestStack->request->get("event") == "parentAll") {
                    $id = $this->requestStack->request->get("id") == "" ? 0 : $this->requestStack->request->get("id");
                    
                    $page = $this->entityManager->getRepository("UebusaitoBundle:Page")->find($id);
                    
                    // Remove children from database
                    $this->removePageChildrenInDatabase($page);
                    
                    // Remove from database
                    $this->pagesInDatabase("delete", null, $page, null);

                    $this->response['messages']['success'] = $this->translator->trans("pageController_9");
                }
                else if (isset($_SESSION['token']) == true && $this->requestStack->request->get("token") == $_SESSION['token'] && $this->requestStack->request->get("event") == "parentNew") {
                    $id = $this->requestStack->request->get("id") == "" ? 0 : $this->requestStack->request->get("id");
                    
                    $page = $this->entityManager->getRepository("UebusaitoBundle:Page")->find($id);
                    
                    // Change parent children in database
                    $this->updatePageChildrenInDatabase($page, $this->requestStack->request->get("parentNew"));

                    // Remove from database
                    $this->pagesInDatabase("delete", null, $page, null);

                    $this->response['messages']['success'] = $this->translator->trans("pageController_10");
                }
                else
                    $this->response['messages']['error'] = $this->translator->trans("pageController_11");
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
            'response' => $this->response
        );
    }
    
    // Functions private
    private function selectionResult($id) {
        $page = $this->entityManager->getRepository("UebusaitoBundle:Page")->find($id);
        
        if ($page != null) {
            // Create form
            $pageFormType = new PageFormType($this->urlLocale, $this->utility, $page);
            $formPageProfile = $this->createForm($pageFormType, $page, Array(
                'validation_groups' => Array(
                    'page_profile'
                )
            ));
            
            $this->response['rolesSelect'] = $this->utility->createRolesSelectHtml("form_page_roleId_field", "required=\"required\"");
            
            $render = $this->renderView("UebusaitoBundle::render/control_panel/page_profile.html.twig", Array(
                'urlLocale' => $this->urlLocale,
                'urlCurrentPageId' => $this->urlCurrentPageId,
                'urlExtra' => $page->getId(),
                'response' => $this->response,
                'form' => $formPageProfile->createView()
            ));

            $this->response['render'] = $render;
        }
        else
            $this->response['messages']['error'] = $this->translator->trans("pageController_3");
    }
    
    private function listHtml($tableResult) {
        foreach ($tableResult as $key => $value) {
            $this->listHtml .= "<tr>
                <td class=\"id_column\">
                    {$value['id']}
                </td>
                <td class=\"checkbox_column\">
                    <input class=\"display_inline margin_clear\" type=\"checkbox\"/>
                </td>
                <td>
                    {$value['title']}
                </td>
                <td>
                    {$value['menu_name']}
                </td>
                <td>";
                    if ($value['protected'] == 0)
                        $this->listHtml .= $this->translator->trans("pageController_12");
                    else
                        $this->listHtml .= $this->translator->trans("pageController_13");
                $this->listHtml .= "</td>
                    <td>";
                        if ($value['show_in_menu'] == 0)
                            $this->listHtml .= $this->translator->trans("pageController_12");
                        else
                            $this->listHtml .= $this->translator->trans("pageController_13");
                $this->listHtml .= "</td>
                    <td>";
                        if ($value['only_link'] == 0)
                            $this->listHtml .= $this->translator->trans("pageController_12");
                        else
                            $this->listHtml .= $this->translator->trans("pageController_13");
                $this->listHtml .= "</td>
                <td class=\"horizontal_center\">";
                    if ($value['id'] > 5)
                        $this->listHtml .= "<button class=\"cp_page_deletion btn btn-danger\"><i class=\"fa fa-remove\"></i></button>
                </td>
            </tr>";
                    
            if (count($value['children']) > 0)
                $this->listHtml($value['children']);
        }
    }
    
    private function removePageChildrenInDatabase($page) {
        $pageChildrenRows = $this->utility->getQuery()->selectAllPageChildrenIdFromDatabase($page);
        
        foreach($pageChildrenRows as $key => $value) {
            $connection = $this->entityManager->getConnection();

            $query = $connection->prepare("DELETE pages, pages_titles, pages_arguments, pages_menu_names FROM pages, pages_titles, pages_arguments, pages_menu_names
                                            WHERE pages.id > :idExclude
                                            AND pages.id = :id
                                            AND pages_titles.id = :id
                                            AND pages_arguments.id = :id
                                            AND pages_menu_names.id = :id");
            
            $query->bindValue(":idExclude", "5");
            $query->bindValue(":id", $value['id']);
            
            $query->execute();
        }
    }
    
    private function updatePageChildrenInDatabase($page, $parentNew) {
        $connection = $this->entityManager->getConnection();
        
        $query = $connection->prepare("UPDATE pages
                                        SET parent = :parentNew
                                        WHERE parent = :id");
        
        $query->bindValue(":parentNew", $parentNew);
        $query->bindValue(":id", $page->getId());
        
        $query->execute();
    }
    
    private function pagesInDatabase($type, $form, $page, $urlLocale) {
        $connection = $this->entityManager->getConnection();
        
        if ($type == "insert") {
            $query = $connection->prepare("INSERT INTO pages_titles (
                                                pages_titles.$urlLocale
                                            )
                                            VALUES (
                                                :title
                                            );
                                            INSERT INTO pages_arguments (
                                                pages_arguments.$urlLocale
                                            )
                                            VALUES (
                                                :argument
                                            );
                                            INSERT INTO pages_menu_names (
                                                pages_menu_names.$urlLocale
                                            )
                                            VALUES (
                                                :menuName
                                            );");
            
            $query->bindValue(":title", $form->get("title")->getData());
            $query->bindValue(":argument", $form->get("argument")->getData());
            $query->bindValue(":menuName", $form->get("menuName")->getData());
            
            $query->execute();
        }
        else if ($type == "update") {
            $language = $form->get("language")->getData();
            
            $query = $connection->prepare("UPDATE pages_titles, pages_arguments, pages_menu_names
                                            SET pages_titles.$language = :title,
                                                pages_arguments.$language = :argument,
                                                pages_menu_names.$language = :menuName
                                            WHERE pages_titles.id = :id
                                            AND pages_arguments.id = :id
                                            AND pages_menu_names.id = :id");
            
            $query->bindValue(":title", $form->get("title")->getData());
            $query->bindValue(":argument", $form->get("argument")->getData());
            $query->bindValue(":menuName", $form->get("menuName")->getData());
            $query->bindValue(":id", $page->getId());
            
            $query->execute();
        }
        else if ($type == "delete") {
            $query = $connection->prepare("DELETE pages, pages_titles, pages_arguments, pages_menu_names FROM pages, pages_titles, pages_arguments, pages_menu_names
                                            WHERE pages.id > :idExclude
                                            AND pages.id = :id
                                            AND pages_titles.id = :id
                                            AND pages_arguments.id = :id
                                            AND pages_menu_names.id = :id");
            
            $query->bindValue(":idExclude", "5");
            $query->bindValue(":id", $page->getId());
            
            $query->execute();
        }
        else if ($type == "deleteAll") {
            $query = $connection->prepare("DELETE pages, pages_titles, pages_arguments, pages_menu_names FROM pages, pages_titles, pages_arguments, pages_menu_names
                                            WHERE pages.id > :idExclude
                                            AND pages_titles.id > :idExclude
                                            AND pages_arguments.id > :idExclude
                                            AND pages_menu_names.id > :idExclude");
            
            $query->bindValue(":idExclude", "5");
            
            $query->execute();
        }
    }
}