<?php
namespace App\Classes\System;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use App\Config;
use App\Classes\System\Query;

class Utility {
    // Vars
    private $container;
    private $entityManager;
    
    private $connection;
    private $translator;
    private $authorizationChecker;
    private $authenticationUtils;
    private $passwordEncoder;
    private $tokenStorage;
    private $session;
    
    private $sessionMaxIdleTime;
    
    private $config;
    private $query;
    
    private $protocol;
    
    private $pathRoot;
    private $pathSrc;
    private $pathWeb;
    
    private $urlRoot;
    
    private $supportSymlink;
    
    private $websiteFile;
    private $websiteName;
    
    private $curlLogin;
    
    // Properties
    public function getConnection() {
        return $this->connection;
    }
    
    public function getTranslator() {
        return $this->translator;
    }
    
    public function getAuthorizationChecker() {
        return $this->authorizationChecker;
    }
    
    public function getAuthenticationUtils() {
        return $this->authenticationUtils;
    }
    
    public function getPasswordEncoder() {
        return $this->passwordEncoder;
    }
    
    public function getTokenStorage() {
        return $this->tokenStorage;
    }
    
    public function getSession() {
        return $this->session;
    }
    
    public function getSessionMaxIdleTime() {
        return $this->sessionMaxIdleTime;
    }
    
    public function getQuery() {
        return $this->query;
    }
    
    public function getProtocol() {
        return $this->protocol;
    }
    
    public function getPathRoot() {
        return $this->pathRoot;
    }
    
    public function getPathSrc() {
        return $this->pathSrc;
    }
    
    public function getPathWeb() {
        return $this->pathWeb;
    }
    
    public function getUrlRoot() {
        return $this->urlRoot;
    }
    
    public function getSupportSymlink() {
        return $this->supportSymlink;
    }
    
    public function getWebsiteFile() {
        return $this->websiteFile;
    }
    
    public function getWebsiteName() {
        return $this->websiteName;
    }
    
    public function getCurlLogin() {
        return $this->curlLogin;
    }
    
    // Functions public
    public function __construct($container, $entityManager) {
        $this->container = $container;
        $this->entityManager = $entityManager;
        
        $this->connection = $this->entityManager->getConnection();
        $this->translator = $this->container->get("translator");
        $this->authorizationChecker = $this->container->get("security.authorization_checker");
        $this->authenticationUtils = $this->container->get("security.authentication_utils");
        $this->passwordEncoder = $this->container->get("security.password_encoder");
        $this->tokenStorage = $this->container->get("security.token_storage");
        $this->session = $this->container->get("session");
        
        $this->sessionMaxIdleTime = 3600;
        
        $this->config = new Config();
        $this->query = new Query($this->connection);
        
        $this->protocol = $this->config->getProtocol();
        
        $this->pathRoot = $_SERVER['DOCUMENT_ROOT'] . $this->config->getPathRoot();
        $this->pathSrc = "{$this->pathRoot}/src";
        $this->pathWeb = "{$this->pathRoot}/public";
        
        $this->urlRoot = $this->config->getProtocol() . $_SERVER['HTTP_HOST'] . $this->config->getUrlRoot();
        
        $this->supportSymlink = $this->config->getSupportSymlink();
        
        $this->websiteFile = $this->config->getFile();
        $this->websiteName = $this->config->getName();
        
        $this->curlLogin = $this->config->getCurlLogin();
        
        $this->arrayColumnFix();
    }
    
    // Generic
    public function configureCookie($name, $lifeTime, $secure, $httpOnly) {
        $currentCookieParams = session_get_cookie_params();
        
        $value = isset($_COOKIE[$name]) == true ? $_COOKIE[$name] : session_id();
        
        if (isset($_COOKIE[$name]) == true)
            setcookie($name, $value, $lifeTime, $currentCookieParams['path'], $currentCookieParams['domain'], $secure, $httpOnly);
    }
    
    public function sessionUnset() {
        session_unset();
        
        $cookies = Array(
            session_name() . "_REMEMBERME"
        );
        
        foreach ($cookies as $value) {
            unset($_COOKIE[$value]);
        }
    }
    
    public function searchInFile($filePath, $word, $replace) {
        $reading = fopen($filePath, "r");
        $writing = fopen($filePath + ".tmp", "w");
        
        $checked = false;
        
        while (feof($reading) == false) {
            $line = fgets($reading);
            
            if (stristr($line, $word) != false) {
                $line = $replace;
                
                $checked = true;
            }
            
            if (feof($reading) == true && $replace == null) {
                $line = "$word\n";

                $checked = true;
            }
            
            fwrite($writing, $line);
        }
        
        fclose($reading);
        fclose($writing);
        
        if ($checked == true) 
            rename($filePath + ".tmp", $filePath);
        else
            unlink($filePath + ".tmp");
    }
    
    public function removeDirRecursive($path, $parent) {
        if (file_exists($path) == true) {
            $rdi = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
            $rii = new RecursiveIteratorIterator($rdi, RecursiveIteratorIterator::CHILD_FIRST);

            foreach($rii as $file) {
                if (file_exists($file->getRealPath()) == true) {
                    if ($file->isDir() == true)
                        rmdir($file->getRealPath());
                    else
                        unlink($file->getRealPath());
                }
                else if (is_link($file->getPathName()) == true)
                    unlink($file->getPathName());
            }

            if ($parent == true)
                rmdir($path);
        }
    }
    
    public function generateRandomString($length) {
        $characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $charactersLength = strlen($characters);
        $randomString = "";
        
        for ($a = 0; $a < $length; $a ++)
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        
        return $randomString;
    }
    
    public function sendEmail($to, $subject, $message, $from) {
        $headers  = "MIME-Version: 1.0 \r\n";
        $headers .= "Content-type: text/html; charset=utf-8 \r\n";
        $headers .= "From: $from \r\n Reply-To: $from";

        mail($to, $subject, $message, $headers);
    }
    
    public function sizeUnits($bytes) {
        if ($bytes >= 1073741824)
            $bytes = number_format($bytes / 1073741824, 2) . " GB";
        else if ($bytes >= 1048576)
            $bytes = number_format($bytes / 1048576, 2) . " MB";
        else if ($bytes >= 1024)
            $bytes = number_format($bytes / 1024, 2) . " KB";
        else if ($bytes > 1)
            $bytes = "$bytes bytes";
        else if ($bytes == 1)
            $bytes = "$bytes byte";
        else
            $bytes = "0 bytes";

        return $bytes;
    }
    
    public function clientIp() {
        $ip = "";
        
        if (getenv("HTTP_CLIENT_IP"))
            $ip = getenv("HTTP_CLIENT_IP");
        else if(getenv("HTTP_X_FORWARDED_FOR"))
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        else if(getenv("HTTP_X_FORWARDED"))
            $ip = getenv("HTTP_X_FORWARDED");
        else if(getenv("HTTP_FORWARDED_FOR"))
            $ip = getenv("HTTP_FORWARDED_FOR");
        else if(getenv("HTTP_FORWARDED"))
           $ip = getenv("HTTP_FORWARDED");
        else if(getenv("REMOTE_ADDR"))
            $ip = getenv("REMOTE_ADDR");
        else
            $ip = "UNKNOWN";
        
        return $ip;
    }
    
    public function dateFormat($date) {
        $newData = Array("", "");
        
        $dateExplode = explode(" ", $date);
        
        if (count($dateExplode) == 0)
            $dateExplode = $newData;
        else {
            $languageDate = isset($_SESSION['languageDate']) == false ? "Y-m-d" : $_SESSION['languageDate'];
            
            if (strpos($dateExplode[0], "0000") === false)
                $dateExplode[0] = date($languageDate, strtotime($dateExplode[0]));
        }
        
        return $dateExplode;
    }
    
    public function timeFormat($type, $time) {
        if ($time == 0)
            return "0s";
        
        $result = Array();
        
        if ($type == "micro") {
            $elements = Array(
                'y' => $time / 31556926 % 12,
                'w' => $time / 604800 % 52,
                'd' => $time / 86400 % 7,
                'h' => $time / 3600 % 24,
                'm' => $time / 60 % 60,
                's' => $time % 60
            );
        }
        else if ($type == "seconds") {
            $elements = Array(
                'h' => floor($time / 3600),
                'm' => floor($time / 60),
                's' => $time % 60 == 0 ? round($time, 2) : $time % 60
            );
        }

        foreach ($elements as $key => $value) {
            if ($value > 0)
                $result[] = $value . $key;
        }

        return join(" ", $result);
    }
    
    public function cutStringOnLength($value, $length) {
        return strlen($value) > $length ? substr(value, 0, $length) . "..." : $value;
    }
    
    public function takeStringBetween($string, $start, $end) {
        $string = " " . $string;
        $position = strpos($string, $start);
        
        if ($position == 0)
            return "";
        
        $position += strlen($start);
        $length = strpos($string, $end, $position) - $position;
        
        return substr($string, $position, $length);
    }
    
    public function arrayLike($elements, $like, $flat) {
        $result = Array();
        
        if ($flat == true) {
            foreach ($elements as $key => $value) {
                $pregGrep = preg_grep("~$like~i", $value);

                if (empty($pregGrep) == false)
                    $result[] = $elements[$key];
            }
        }
        else
            $result = preg_grep("~$like~i", $elements);
        
        return $result;
    }
    
    public function arrayMoveElement(&$array, $a, $b) {
        $out = array_splice($array, $a, 1);
        array_splice($array, $b, 0, $out);
    }
    
    public function arrayFindValue($elements, $subElements) {
        $result = false;
        
        foreach ($elements as $key => $value) {
            if (in_array($value, $subElements) == true) {
                $result = true;
                
                break;
            }
        }
        
        return $result;
    }
    
    public function arrayFindKeyWithValue($elements, $label, $item) {
        foreach($elements as $key => $value) {
            if ($value[$label] === $item )
                return $key;
        }
        
        return false;
    }
    
    public function arrayExplodeFindValue($elementsFirst, $elementsSecond) {
        $elementsFirstExplode = explode(",", $elementsFirst);
        array_pop($elementsFirstExplode);

        $elementsSecondExplode =  explode(",", $elementsSecond);
        array_pop($elementsSecondExplode);
        
        if ($this->arrayFindValue($elementsFirstExplode, $elementsSecondExplode) == true)
            return true;
        
        return false;
    }
    
    function arrayUniqueMulti($elements, $index, $fix = true) {
        $results = Array();
        
        $i = 0;
        $keys = Array();
        
        foreach($elements as $key => $value) {
            if (in_array($value[$index], $keys) == false) {
                $results[$i] = $value;
                
                $keys[$i] = $value[$index];
            }
            
            $i ++;
        }
        
        if ($fix == true)
            $results = array_values($results);
        
        return $results;
    }
    
    public function urlParameters($completeUrl, $baseUrl) {
        $lastPath = substr($completeUrl, strpos($completeUrl, $baseUrl) + strlen($baseUrl));
        $lastPathExplode = explode("/", $lastPath);
        array_shift($lastPathExplode);
        
        return $lastPathExplode;
    }
    
    public function requestParametersParse($json) {
        $parameters = Array();
        $match = Array();
        
        foreach ($json as $key => $value) {
            if (is_object($value) == false)
                $parameters[$key] = $value;
            else {
                preg_match("#\[(.*?)\]#", $value->name, $match);
                
                $keyTmp = "";
                
                if (count($match) == 0)
                    $keyTmp = $value->name;
                else
                    $keyTmp = $match[1];
                    
                $parameters[$keyTmp] = $value->value;
            }
        }
        
        return $parameters;
    }
    
    public function assignUserPassword($type, $user, $form) {
        $row = $this->query->selectUserDatabase($user->getId());
        
        if ($type == "withOld") {
            if (password_verify($form->get("old")->getData(), $row['password']) == false)
                return $this->translator->trans("classUtility_2");
            else if ($form->get("new")->getData() != $form->get("newConfirm")->getData())
                return $this->translator->trans("classUtility_3");
            
            $user->setPassword($this->createPasswordEncoder($type, $user, $form));
        }
        else if ($type == "withoutOld") {
            if ($form->get("password")->getData() != "" || $form->get("passwordConfirm")->getData() != "") {
                if ($form->get("password")->getData() != $form->get("passwordConfirm")->getData())
                    return $this->translator->trans("classUtility_4");
                
                $user->setPassword($this->createPasswordEncoder($type, $user, $form));
            }
            else
                $user->setPassword($row['password']);
        }
        
        return "ok";
    }
    
    public function createUserRoleSelectHtml($selectId, $label, $isRequired = false) {
        $rows = $this->query->selectAllRoleUserDatabase();
        
        $required = $isRequired == true ? "required=\"required\"" : "";
        
        $html = "<div id=\"$selectId\" class=\"mdc-select\" $required>
            <select class=\"mdc-select__native-control\">
                <option value=\"\"></option>";
                foreach ($rows as $key => $value) {
                    $html .= "<option value=\"{$value['id']}\">{$value['level']}</option>";
                }
            $html .= "</select>
            <label class=\"mdc-floating-label mdc-floating-label--float-above\">" . $this->translator->trans($label) . "</label>
            <div class=\"mdc-line-ripple\"></div>
        </div>";
        
        return $html; //classUtility_5
    }
    
    public function createPageSortListHtml($rows) {
        $html = "<ul class=\"sort_list\">";
            foreach ($rows as $key => $value) {
                $html .= "<li class=\"ui-state-default\">
                    <div class=\"mdc-chip\">
                        <i class=\"material-icons mdc-chip__icon mdc-chip__icon--leading\">drag_handle</i>
                        <div class=\"mdc-chip__text sort_elemet_data\" data-id=\"$key\">[$key] $value</div>
                    </div>
                </li>";
            }
            
            if ($_SESSION['pageProfileId'] == 0) {
                $pageRows = $this->query->selectAllPageDatabase($_SESSION['languageTextCode']);
                $id = count($pageRows) + 1;
                
                $html .= "<li class=\"ui-state-default\">
                    <div class=\"mdc-chip\">
                        <i class=\"material-icons mdc-chip__icon mdc-chip__icon--leading\">drag_handle</i>
                        <div class=\"mdc-chip__text sort_elemet_data\" data-id=\"$id\">[$id] " . $this->translator->trans("classUtility_6") . "</div>
                    </div>
                </li>";
            }
        $html .= "</ul>";
        
        return $html;
    }
    
    public function createModuleSortListHtml($rows) {
        $html = "<ul class=\"sort_list\">";
            foreach ($rows as $key => $value) {
                $html .= "<li class=\"ui-state-default\">
                    <div class=\"mdc-chip\">
                        <i class=\"material-icons mdc-chip__icon mdc-chip__icon--leading\">drag_handle</i>
                        <div class=\"mdc-chip__text sort_elemet_data\" data-id=\"$key\">[$key] $value</div>
                    </div>
                </li>";
            }
            
            if ($_SESSION['moduleProfileId'] == 0) {
                $moduleRows = $this->query->selectAllModuleDatabase();
                $id = count($moduleRows) + 1;
                
                $html .= "<li class=\"ui-state-default\">
                    <div class=\"mdc-chip\">
                        <i class=\"material-icons mdc-chip__icon mdc-chip__icon--leading\">drag_handle</i>
                        <div class=\"mdc-chip__text sort_elemet_data\" data-id=\"$id\">[$id] " . $this->translator->trans("classUtility_7") . "</div>
                    </div>
                </li>";
            }
        $html .= "</ul>";
        
        return $html;
    }
    
    public function createTemplateList() {
        $templatesPath = "{$this->pathWeb}/images/templates";
        
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
    
    public function checkSessionOverTime($request, $root = false) {
        if ($root == true) {
            if (isset($_SESSION['userActivityCount']) == false || isset($_SESSION['userActivity']) == false) {
                $_SESSION['userActivityCount'] = 0;
                $_SESSION['userActivity'] = "";
            }
        }
        
        if ($request->cookies->has(session_name() . "_REMEMBERME") == false && $this->authorizationChecker->isGranted("IS_AUTHENTICATED_FULLY") == true) {
            if (isset($_SESSION['userActivityTimestamp']) == false)
                $_SESSION['userActivityTimestamp'] = time();
            else {
                $timeLapse = time() - $_SESSION['userActivityTimestamp'];

                if ($timeLapse > $this->sessionMaxIdleTime) {
                    $userActivity = $this->translator->trans("classUtility_1");
                    
                    if ($request->isXmlHttpRequest() == true) {
                        echo json_encode(Array(
                            'userActivity' => $userActivity
                        ));

                        exit;
                    }
                    else
                        $this->tokenStorage->setToken(null);
                    
                    $_SESSION['userActivity'] = $userActivity;
                    
                    unset($_SESSION['userActivityTimestamp']);
                }
                else
                    $_SESSION['userActivityTimestamp'] = time();
            }
        }
        
        if (isset($_SESSION['userActivity']) == true) {
            if ($request->isXmlHttpRequest() == true && $_SESSION['userActivity'] != "") {
                echo json_encode(Array(
                    'userActivity' => $_SESSION['userActivity']
                ));

                exit;
            }
        }
        
        if ($root == true && $_SESSION['userActivity'] != "") {
            if ($_SESSION['userActivityCount'] > 1) {
                $_SESSION['userActivityCount'] = 0;
                $_SESSION['userActivity'] = "";
            }
            
            $_SESSION['userActivityCount'] ++;
        }
    }
    
    public function checkAttemptLogin($type, $userValue, $settingRow) {
        $row = $this->query->selectUserDatabase($userValue);
        
        $dateTimeCurrentLogin = new \DateTime($row['date_current_login']);
        $dateTimeCurrent = new \DateTime();
        
        $interval = intval($dateTimeCurrentLogin->diff($dateTimeCurrent)->format("%i"));
        $total = $settingRow['login_attempt_time'] - $interval;
        
        if ($total < 0)
            $total = 0;
        
        $dateCurrent = date("Y-m-d H:i:s");
        $dateLastLogin = strpos($row['date_last_login'], "0000") !== false ? $dateCurrent : $row['date_current_login'];
        
        $result = Array("", "");
        
        if (isset($row['id']) == true && $settingRow['login_attempt_time'] > 0) {
            $count = $row['attempt_login'] + 1;
            
            $query = $this->connection->prepare("UPDATE users
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
                    $query->bindValue(":ip", $this->clientIp());
                    $query->bindValue(":attemptLogin", 0);
                    $query->bindValue(":id", $row['id']);

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
                    $query->bindValue(":dateLastLogin", $row['date_last_login']);
                    $query->bindValue(":ip", $this->clientIp());
                    $query->bindValue(":attemptLogin", $count);
                    $query->bindValue(":id", $row['id']);
                    
                    $query->execute();
                    
                    $result[0] = "try";
                    $result[1] = $count;
                }
                
                return Array(false, $result[0], $result[1]);
            }
        }
        
        return Array(true, $result[0], $result[1]);
    }
    
    public function checkUserActive($username) {
        $row = $this->query->selectUserDatabase($username);
        
        if ($row == false)
            return false;
        else
            return $row['active'];
    }
    
    public function checkUserRole($roleName, $roleId) {
        $row = $this->query->selectRoleUserDatabase($roleId);
        
        if ($this->arrayFindValue($roleName, $row) == true)
            return true;
        
        return false;
    }
    
    // Symfony
    public function assignUserParameter($user) {
        $query = $this->connection->prepare("SELECT id FROM users
                                                LIMIT 1");
        
        $query->execute();
        
        $rowsCount = $query->rowCount();
        
        if ($rowsCount == 0) {
            $user->setRoleUserId("1,2,");
            $user->setActive(1);
        }
        else {
            $user->setRoleUserId("1,");
            $user->setActive(0);
        }
    }
    
    public function createLanguageSelectOptionHtml($code) {
        $row = $this->query->selectLanguageDatabase($code);
        $rows = $this->query->selectAllLanguageDatabase();
        
        $key = array_search($row, $rows);
        unset($rows[$key]);
        array_unshift($rows, $row);
        
        $html = "";
        
        foreach ($rows as $key => $value) {
            $html .= "<option value=\"{$value['code']}\">{$value['code']}</option>";
        }
        
        return $html;
    }
    
    public function createPageSelectHtml($urlLocale, $selectId, $label) {
        $rows = $this->query->selectAllPageDatabase($urlLocale);
        
        $pagesList = $this->createPageList($rows, true);
        
        $html = "<div id=\"$selectId\" class=\"mdc-select\">
            <select class=\"mdc-select__native-control\">
                <option value=\"\"></option>";
                foreach ($pagesList as $key => $value) {
                    $html .= "<option value=\"$key\">$value</option>";
                }
            $html .= "</select>
            <label class=\"mdc-floating-label mdc-floating-label--float-above\">$label</label>
            <div class=\"mdc-line-ripple\"></div>
        </div>";
        
        return $html;
    }
    
    public function createPageList($pagesRows, $onlyMenuName, $pagination = null) {
        $pagesListHierarchy = $this->createPageListHierarchy($pagesRows, $pagination);
        
        if ($onlyMenuName == true) {
            $tag = "";
            $parentId = 0;
            $elements = Array();
            $count = 0;

            $pagesListOnlyMenuName = $this->createPageListOnlyMenuName($pagesListHierarchy, $tag, $parentId, $elements, $count);
            
            return $pagesListOnlyMenuName;
        }
        
        return $pagesListHierarchy;
    }
    
    public function checkLanguage($request) {
        if (isset($_SESSION['languageTextCode']) == false) {
            $row = $this->query->selectSettingDatabase();
            
            $_SESSION['languageTextCode'] = $row['language'];
        }
        
        if ($request->get("languageTextCode") != null)
            $_SESSION['languageTextCode'] = $request->get("languageTextCode");
        
        $request->setLocale($_SESSION['languageTextCode']);
        
        return $_SESSION['languageTextCode'];
    }
    
    // Functions private
    private function arrayColumnFix() {
        if (function_exists("array_column") == false) {
            function array_column($input = null, $columnKey = null, $indexKey = null) {
                $argc = func_num_args();
                $params = func_get_args();
                
                if ($argc < 2) {
                    trigger_error("array_column() expects at least 2 parameters, {$argc} given", E_USER_WARNING);
                    
                    return null;
                }
                
                if (!is_array($params[0])) {
                    trigger_error("array_column() expects parameter 1 to be array, " . gettype($params[0]) . " given", E_USER_WARNING);
                    
                    return null;
                }
                
                if (!is_int($params[1]) && !is_float($params[1]) && !is_string($params[1]) && $params[1] !== null && !(is_object($params[1]) && method_exists($params[1], "__toString"))) {
                    trigger_error("array_column(): The column key should be either a string or an integer", E_USER_WARNING);
                    
                    return false;
                }
                
                if (isset($params[2]) && !is_int($params[2]) && !is_float($params[2]) && !is_string($params[2]) && !(is_object($params[2]) && method_exists($params[2], "__toString"))) {
                    trigger_error("array_column(): The index key should be either a string or an integer", E_USER_WARNING);
                    
                    return false;
                }
                
                $paramsInput = $params[0];
                $paramsColumnKey = ($params[1] !== null) ? (string) $params[1] : null;
                $paramsIndexKey = null;
                
                if (isset($params[2])) {
                    if (is_float($params[2]) || is_int($params[2]))
                        $paramsIndexKey = (int)$params[2];
                    else
                        $paramsIndexKey = (string)$params[2];
                }
                
                $resultArray = array();
                
                foreach ($paramsInput as $row) {
                    $key = null;
                    $value = null;
                    
                    $keySet = false;
                    $valueSet = false;
                    
                    if ($paramsIndexKey !== null && array_key_exists($paramsIndexKey, $row)) {
                        $keySet = true;
                        $key = (string)$row[$paramsIndexKey];
                    }
                    
                    if ($paramsColumnKey == null) {
                        $valueSet = true;
                        $value = $row;
                    }
                    else if (is_array($row) && array_key_exists($paramsColumnKey, $row)) {
                        $valueSet = true;
                        $value = $row[$paramsColumnKey];
                    }
                    
                    if ($valueSet) {
                        if ($keySet)
                            $resultArray[$key] = $value;
                        else
                            $resultArray[] = $value;
                    }
                }
                
                return $resultArray;
            }
        }
    }
    
    private function createPasswordEncoder($type, $user, $form) {
        if ($type == "withOld")
            return $this->passwordEncoder->encodePassword($user, $form->get("new")->getData());
        else if ($type == "withoutOld")
            return $this->passwordEncoder->encodePassword($user, $form->get("password")->getData());
    }
    
    // Symfony
    private function createPageListHierarchy($pagesRows, $pagination) {
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
    
    private function createPageListOnlyMenuName($pagesListHierarchy, &$tag, &$parentId, &$elements, &$count) {
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
                $this->createPageListOnlyMenuName($value['children'], $tag, $parentId, $elements, $count);
        }
        
        return $elements;
    }
}