<?php
// Version 1.0.0

namespace ReinventSoftware\UebusaitoBundle\Classes;

use ReinventSoftware\UebusaitoBundle\Classes\System\Utility;

class UebusaitoUtility {
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
        $this->query = $this->utility->getQuery();
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
            $roleLevel = $this->query->selectUserRoleLevelDatabase($user->getRoleId());
            
            foreach($roleLevel as $key => $value) {
                $user->setRoles(Array(
                    $value
                ));
            }
        }
    }
    
    public function assigUserPassword($type, $user, $form) {
        $row = $this->query->selectUserDatabase($user->getId());
        
        if ($type == "withOld") {
            if (password_verify($form->get("old")->getData(), $row['password']) == false)
                return $this->utility->getTranslator()->trans("class_uebusaitoUtility_1");

            if ($form->get("new")->getData() != $form->get("newConfirm")->getData())
                return $this->utility->getTranslator()->trans("class_uebusaitoUtility_2");
            
            $user->setPassword($this->createPasswordEncoder($type, $user, $form));
        }
        else if ($type == "withoutOld") {
            if ($form->get("password")->getData() != "" || $form->get("passwordConfirm")->getData() != "") {
                if ($form->get("password")->getData() != $form->get("passwordConfirm")->getData())
                    return $this->utility->getTranslator()->trans("class_uebusaitoUtility_3");
                
                $user->setPassword($this->createPasswordEncoder($type, $user, $form));
            }
            else
                $user->setPassword($row['password']);
        }
        
        return "ok";
    }
    
    public function createHtmlPages($urlLocale, $selectId) {
        $rows = $this->query->selectAllPagesDatabase($urlLocale);
        
        $pagesList = $this->createPagesList($rows, true);
        
        $html = "<p class=\"margin_clear\">" . $this->utility->getTranslator()->trans("class_uebusaitoUtility_4") . "</p>
        <select id=\"$selectId\">
            <option value=\"\">Select</option>";
            foreach($pagesList as $key => $value)
                $html .= "<option value=\"$key\">$value</option>";
        $html .= "</select>";
        
        return $html;
    }
    
    public function createHtmlRoles($selectId, $isRequired = false) {
        $rows = $this->query->selectAllUserRolesDatabase();
        
        $required = $isRequired == true ? "required=\"required\"" : "";
        
        $html = "<select id=\"$selectId\" class=\"form-control\" $required>
            <option value=\"\">" . $this->utility->getTranslator()->trans("class_uebusaitoUtility_5") . "</option>";
            foreach($rows as $key => $value)
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
        $templatesPath = "{$this->utility->getPathSrcBundle()}/Resources/public/images/templates";
        
        $scanDirElements = scandir($templatesPath);
        
        $list = Array();
        
        if ($scanDirElements != false) {
            foreach ($scanDirElements as $key => $value) {
                if ($value != "." && $value != ".." && $value != ".htaccess" && is_dir("$templatesPath/$value") == true)
                    $list[$value] = $value;
            }
        }
        
        return $list;
    }
    
    public function checkUserNotLocked($username) {
        $row = $this->query->selectUserDatabase($username);
        
        if ($row == false)
            return true;
        else
            return $row['not_locked'];
    }
    
    public function checkAttemptLogin($type, $userValue, $settingRow) {
        $userRow = $this->query->selectUserDatabase($userValue);
        
        $dateTimeCurrentLogin = new \DateTime($userRow['date_current_login']);
        $dateTimeCurrent = new \DateTime();
        
        $interval = intval($dateTimeCurrentLogin->diff($dateTimeCurrent)->format("%i"));
        $total = $settingRow['login_attempt_time'] - $interval;
        
        if ($total < 0)
            $total = 0;
        
        $dateCurrent = date("Y-m-d H:i:s");
        $dateLastLogin = strrpos($userRow['date_last_login'], "0000-") === 0 ? $dateCurrent : $userRow['date_current_login'];
        
        $result = Array("", "");
        
        if (isset($userRow['id']) == true && $settingRow['login_attempt_time'] > 0) {
            $count = $userRow['attempt_login'] + 1;
            
            $query = $this->utility->getConnection()->prepare("UPDATE users
                                                                SET date_current_login = :dateCurrentLogin,
                                                                    date_last_login = :dateLastLogin,
                                                                    ip = :ip,
                                                                    attempt_login = :attemptLogin
                                                                WHERE id = :id");
            
            if ($type == "success") {
                if ($count > $settingRow['login_attempt_count'] && $total > 0) {
                    $result[0] = "lock";
                    $result[1] = $total;
                    
                    return Array(false, $result[0], $result[1]);
                }
                else {
                    $query->bindValue(":dateCurrentLogin", $dateCurrent);
                    $query->bindValue(":dateLastLogin", $dateLastLogin);
                    $query->bindValue(":ip", $this->utility->clientIp());
                    $query->bindValue(":attemptLogin", 0);
                    $query->bindValue(":id", $userRow['id']);

                    $query->execute();
                }
            }
            else if ($type == "failure") {
                if ($count > $settingRow['login_attempt_count'] && $total > 0) {
                    $result[0] = "lock";
                    $result[1] = $total;
                }
                else {
                    if ($count > $settingRow['login_attempt_count'])
                        $count = 1;
                    
                    $query->bindValue(":dateCurrentLogin", $dateCurrent);
                    $query->bindValue(":dateLastLogin", $userRow['date_last_login']);
                    $query->bindValue(":ip", $this->utility->clientIp());
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
    
    public function checkLanguage($request) {
        if ($request->request->get('form_language')['codeText'] != null)
            $_SESSION['formLanguageCodeText'] = $request->request->get('form_language')['codeText'];
        
        if (isset($_SESSION['formLanguageCodeText']) === false) {
            $rows = $this->query->selectSettingDatabase();
            
            $_SESSION['formLanguageCodeText'] = $rows['language'];
        }
        
        $request->setLocale($_SESSION['formLanguageCodeText']);
        
        return $_SESSION['formLanguageCodeText'];
    }
    
    public function checkRoleLevel($roleName, $userRoleId) {
        $row = $this->query->selectUserRoleLevelDatabase($userRoleId);
        
        foreach ($roleName as $key => $value) {
            if (in_array($value, $row) == true) {
                return true;

                break;
            }
        }
        
        return false;
    }
    
    public function checkInRoles($roleIdA, $roleIdB) {
        $roleIdExplodeA = explode(",", $roleIdA);
        array_pop($roleIdExplodeA);

        $roleIdExplodeB =  explode(",", $roleIdB);
        array_pop($roleIdExplodeB);
        
        if ($this->utility->valueInSubArray($roleIdExplodeA, $roleIdExplodeB) == true)
            return true;
        
        return false;
    }
    
    // Functions private
    private function createPasswordEncoder($type, $user, $form) {
        if ($type == "withOld")
            return $this->utility->getPasswordEncoder()->encodePassword($user, $form->get("new")->getData());
        else if ($type == "withoutOld")
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
            if ($node['parent'] == null || array_key_exists($node['parent'], $nodes) == false)
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
            else if ($value['parent'] != null && $parentId != null && $value['parent'] < $parentId) {
                $count --;
                
                if ($count == 1)
                    $tag = substr($tag, 0, 2);
                else
                    $tag = substr($tag, 0, $count);
            }
            else if ($value['parent'] != null && $value['parent'] != $parentId) {
                $count ++;
                
                $tag .= "-";
            }
            
            $parentId = $value['parent'];
            
            $elements[$value['id']] = "|$tag| " . $value['alias'];
            
            if (count($value['children']) > 0)
                $this->createPagesListOnlyMenuName($value['children'], $tag, $parentId, $elements, $count);
        }
        
        return $elements;
    }
}