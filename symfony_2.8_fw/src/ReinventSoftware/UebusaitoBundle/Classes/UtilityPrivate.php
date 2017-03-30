<?php
namespace ReinventSoftware\UebusaitoBundle\Classes;

use ReinventSoftware\UebusaitoBundle\Classes\Utility;
use ReinventSoftware\UebusaitoBundle\Classes\Query;

class UtilityPrivate {
    // Vars
    private $container;
    private $entityManager;
    
    private $utility;
    private $query;
    
    // Properties
      
    // Functions public
    public function __construct($container, $entityManager) {
        $this->container = $container;
        $this->entityManager = $entityManager;
        
        $this->utility = new Utility($this->container, $this->entityManager);
        $this->query = new Query($this->utility->getConnection());
    }
    
    public function configureUserProfilePassword($user, $type, $form) {
        $userRow = $this->query->selectUserFromDatabase($user->getId());
        
        if ($type == 1) {
            if (password_verify($form->get("old")->getData(), $userRow['password']) == false)
                return $this->utility->getTranslator()->trans("utility_2");

            if ($form->get("new")->getData() != $form->get("newConfirm")->getData())
                return $this->utility->getTranslator()->trans("utility_3");
            
            $user->setPassword($this->passwordEncodedLogic($user, $type, $form));
        }
        else if ($type == 2) {
            if ($form->get("password")->getData() != "" || $form->get("passwordConfirm")->getData() != "") {
                if ($form->get("password")->getData() != $form->get("passwordConfirm")->getData())
                    return $this->utility->getTranslator()->trans("utility_4");
                
                $user->setPassword($this->passwordEncodedLogic($user, $type, $form));
            }
            else
                $user->setPassword($userRow['password']);
        }
        
        return "ok";
    }
    
    public function configureUserParameters($user) {
        $query = $this->utility->getConnection()->prepare("SELECT id FROM users
                                                            LIMIT 1");
        
        $query->execute();
        
        $rowsCount = $query->rowCount();
        
        if ($rowsCount == 0) {
            $user->setRoleId("1,2,");
            $user->setNotLocked(1);
        }
        else {
            $user->setRoleId("1,");
            $user->setNotLocked(0);
        }
        
        $user->setCredits(0);
    }
    
    public function assignUserRole($user) {
        if ($user != null) {
            $rolesExplode = explode(",", $user->getRoleId());
            array_pop($rolesExplode);
            
            foreach($rolesExplode as $key => $value) {
                $query = $this->utility->getConnection()->prepare("SELECT level FROM users_roles
                                                                    WHERE id = :value");

                $query->bindValue(":value", $value);
                
                $query->execute();
                
                $rows = $query->fetch();
                
                $user->setRoles(Array(
                    $rows['level']
                ));
            }
        }
    }
    
    public function createPagesSelectHtml($urlLocale, $selectId) {
        $pageRows = $this->query->selectAllPagesFromDatabase($urlLocale);
        
        $pagesList = $this->createPagesList($pageRows, true);
        
        $html = "<p class=\"margin_clear\">" . $this->utility->getTranslator()->trans("utility_5") . "</p>
        <select id=\"$selectId\">
            <option value=\"\">Select</option>";
            foreach($pagesList as $key => $value)
                $html .= "<option value=\"$key\">$value</option>";
        $html .= "</select>";
        
        return $html;
    }
    
    public function createRolesSelectHtml($selectId, $isRequired = false) {
        $roleRows = $this->query->selectAllUserRolesFromDatabase();
        
        $required = $isRequired == true ? "required=\"required\"" : "";
        
        $html = "<select id=\"$selectId\" class=\"form-control\" $required>
            <option value=\"\">Select</option>";
            foreach($roleRows as $key => $value)
                $html .= "<option value=\"{$value['id']}\">{$value['level']}</option>";
        $html .= "</select>";
        
        return $html;
    }
    
    public function createPagesList($pagesRows, $onlyMenuName, $pagination = null) {
        $pagesListHierarchy = $this->createPagesListHierarchy($pagesRows, $pagination);
        
        if ($onlyMenuName == true) {
            $tag = "";
            $parentId = 0;
            $elements = Array();
            $count = 0;

            $pagesListOnlyMenuName = $this->createPagesListOnlyMenuName($pagesListHierarchy, $tag, $parentId, $elements, $count);
            
            return $pagesListOnlyMenuName;
        }
        
        return $pagesListHierarchy;
    }
    
    public function createTemplatesList() {
        $templatesPath = $this->utility->getPathRootFull() . "/src/ReinventSoftware/UebusaitoBundle/Resources/public/images/templates";
        
        $scanDirElements = @scandir($templatesPath);
        
        $list = Array();
        
        if ($scanDirElements != false) {
            foreach ($scanDirElements as $key => $value) {
                if ($value != "." && $value != ".." && $value != ".htaccess" && is_dir("$templatesPath/$value") == true)
                    $list[$value] = $value;
            }
        }
        
        return $list;
    }
    
    public function attemptLogin($type, $value) {
        $userRow = $this->query->selectUserFromDatabase($value);
        
        $dateLastLogin = new \DateTime($userRow['date_last_login']);
        $dateCurrent = new \DateTime();
        
        $interval = intval($dateLastLogin->diff($dateCurrent)->format("%i"));
        $total = $this->utility->getSettings()['login_attempt_time'] - $interval;
        
        if ($total < 0)
            $total = 0;
        
        $date = date("Y-m-d H:i:s");
        $ip = $this->utility->clientIp();
        
        $result = Array("", "");
        
        if (isset($userRow['id']) == true && $this->utility->getSettings()['login_attempt_time'] > 0) {
            $count = $userRow['attempt_login'] + 1;
            
            $query = $this->utility->getConnection()->prepare("UPDATE users
                                                                SET date_last_login = :dateLastLogin,
                                                                    ip = :ip,
                                                                    attempt_login = :attemptLogin
                                                                WHERE id = :id");
            
            if ($type == "loginSuccess") {
                if ($count > $this->utility->getSettings()['login_attempt_count'] && $total > 0) {
                    $result[0] = "lock";
                    $result[1] = $total;
                    
                    return Array(false, $result[0], $result[1]);
                }
                else {
                    $query->bindValue(":dateLastLogin", $date);
                    $query->bindValue(":ip", $ip);
                    $query->bindValue(":attemptLogin", 0);
                    $query->bindValue(":id", $userRow['id']);

                    $query->execute();
                }
            }
            else if ($type == "loginFailure") {
                if ($count > $this->utility->getSettings()['login_attempt_count'] && $total > 0) {
                    $result[0] = "lock";
                    $result[1] = $total;
                }
                else {
                    if ($count > $this->utility->getSettings()['login_attempt_count'])
                        $count = 1;
                    
                    $query->bindValue(":dateLastLogin", $date);
                    $query->bindValue(":ip", $ip);
                    $query->bindValue(":attemptLogin", $count);
                    $query->bindValue(":id", $userRow['id']);
                    
                    $query->execute();
                    
                    $result[0] = "try";
                    $result[1] = $count;
                }
                
                return Array(false, $result[0], $result[1]);
            }
        }
        
        return Array(true, $result[0], $result[1]);
    }
    
    // Functions private
    private function passwordEncodedLogic($user, $type, $form) {
        if ($type == 1)
            return $this->utility->getPasswordEncoder()->encodePassword($user, $form->get("new")->getData());
        else if ($type == 2)
            return $this->utility->getPasswordEncoder()->encodePassword($user, $form->get("password")->getData());
    }
    
    private function createPagesListHierarchy($pagesRows, $pagination) {
        $elements = array_slice($pagesRows, $pagination['offset'], $pagination['show']);
        
        $nodes = Array();
        $tree = Array();
        
        foreach ($elements as $page) {
            $nodes[$page['id']] = array_merge($page, Array(
                'children' => Array()
            ));
        }
        
        foreach ($nodes as &$node) {
            if ($node['parent'] == 0 || array_key_exists($node['parent'], $nodes) == false)
                $tree[] = &$node;
            else
                $nodes[$node['parent']]['children'][] = &$node;
        }
        
        unset($node);
        unset($nodes);
        
        return $tree;
    }
    
    private function createPagesListOnlyMenuName($pagesListHierarchy, &$tag, &$parentId, &$elements, &$count) {
        foreach ($pagesListHierarchy as $key => $value) {
            if ($value['parent'] == null) {
                $count = 0;
                
                $tag = "-";
            }
            else if ($value['parent'] == $parentId) {
                $count ++;
                
                $tag .= "-";
            }
            else {
                $count --;
                
                $tag = substr($tag, 0, $count);
            }
            
            $parentId = $value['id'];
            
            $elements[$value['id']] = "|$tag| " . $value['title'];
            
            if (count($value['children']) > 0)
                $this->createPagesListOnlyMenuName($value['children'], $tag, $parentId, $elements, $count);
        }
        
        return $elements;
    }
}