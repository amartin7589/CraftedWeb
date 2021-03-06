<?php

    /* ___           __ _           _ __    __     _     
      / __\ __ __ _ / _| |_ ___  __| / / /\ \ \___| |__
      / / | '__/ _` | |_| __/ _ \/ _` \ \/  \/ / _ \ '_ \
      / /__| | | (_| |  _| ||  __/ (_| |\  /\  /  __/ |_) |
      \____/_|  \__,_|_|  \__\___|\__,_| \/  \/ \___|_.__/

      -[ Created by �Nomsoft
      `-[ Original core by Anthony (Aka. CraftedDev)

      -CraftedWeb Generation II-
      __                           __ _
      /\ \ \___  _ __ ___  ___  ___  / _| |_
      /  \/ / _ \| '_ ` _ \/ __|/ _ \| |_| __|
      / /\  / (_) | | | | | \__ \ (_) |  _| |_
      \_\ \/ \___/|_| |_| |_|___/\___/|_|  \__|	- www.Nomsoftware.com -
      The policy of Nomsoftware states: Releasing our software
      or any other files are protected. You cannot re-release
      anywhere unless you were given permission.
      � Nomsoftware 'Nomsoft' 2011-2012. All rights reserved. */

##############
##  Account functions goes here
##############

    class Account
    {
        ###############################
        ####### Log in method
        ###############################

        public function logIn($username, $password, $last_page, $remember)
        {
            if (!isset($username) || !isset($password) || empty($username) || empty($password))
            {
                echo '<span class="red_text">Please enter both fields.</span>';
            }
            else
            {
                global $Connect;
                $conn = $Connect->connectToDB();;
                $username = mysqli_real_escape_string($conn, trim(strtoupper($username)));
                $password = mysqli_real_escape_string($conn, trim(strtoupper($password)));

                $Connect->selectDB('logondb', $conn);
                $checkForAccount = mysqli_query($conn, "SELECT COUNT(id) FROM account WHERE username='". $username ."';");
                if (mysqli_data_seek($checkForAccount, 0) == 0)
                {
                    echo '<span class="red_text">Invalid username.</span>';
                }
                else
                {
                    if ($remember != 835727313) $password = sha1("" . $username . ":" . $password . "");

                    $result = mysqli_query($conn, "SELECT id FROM account WHERE username='". $username ."' AND sha_pass_hash='". $password ."';");
                    if (mysqli_num_rows($result) == 0)
                    {
                        echo '<span class="red_text">Wrong password.</span>';
                    }
                    else
                    {
                        if ($remember == 'on')
                        {
                            setcookie("cw_rememberMe", $username . ' * ' . $password, time() + 30758400);
                            //Set "remember me" cookie. Expires in 1 year.
                        }

                        $id = mysqli_fetch_assoc($result);
                        $id = $id['id'];

                        $this->GMLogin($username);
                        $_SESSION['cw_user']    = ucfirst(strtolower($username));
                        $_SESSION['cw_user_id'] = $id;

                        $Connect->selectDB('webdb', $conn);
                        $count = mysqli_query($conn, "SELECT COUNT(*) FROM account_data WHERE id=". $id .";");
                        if (mysqli_data_seek($count, 0) == 0)
                            mysqli_query($conn, "INSERT INTO account_data (id) VALUES(". $id .");");

                        if (!empty($last_page))
                        {
                            header("Location: " . $last_page);
                        }
                        else
                        {
                            header("Location: index.php");
                        }
                    }
                }
            }
        }

        public function loadUserData()
        {
            //Unused function
            $user_info = array();
            global $Connect, $conn;
            $Connect->selectDB('logondb', $conn);

            $account_info = mysqli_query($conn, "SELECT id, username, email, joindate, locked, last_ip, expansion FROM account WHERE username='". $_SESSION['cw_user'] ."';");
            while ($row = mysqli_fetch_array($account_info))
            {
                $user_info[] = $row;
            }

            return $user_info;
        }

        ###############################
        ####### Log out method
        ###############################

        public function logOut($last_page)
        {
            session_destroy();
            setcookie('cw_rememberMe', '', time() - 30758400);
            if (empty($last_page))
            {
                header('Location: ?p=home"');
                exit();
            }
            header('Location: ' . $last_page);
            exit();
        }

        ###############################
        ####### Registration method
        ###############################

        public function register($username, $email, $password, $repeat_password, $captcha, $raf)
        {
            $errors = array();

            if (empty($username))
            {
                $errors[] = 'Enter a username.';
            }

            if (empty($email))
            {
                $errors[] = 'Enter an email address.';
            }

            if (empty($password))
            {
                $errors[] = 'Enter a password.';
            }

            if (empty($repeat_password))
            {
                $errors[] = 'Enter the password repeat.';
            }

            if ($username == $password)
            {
                $errors[] = 'Your password cannot be your username!';
            }
            else
            {
                session_start();
                if ($GLOBALS['registration']['captcha'] == TRUE)
                {
                    if ($captcha != $_SESSION['captcha_numero'])
                    {
                        $errors[] = 'The captcha is incorrect!';
                    }
                }

                if (strlen($username) > $GLOBALS['registration']['userMaxLength'] || strlen($username) < $GLOBALS['registration']['userMinLength'])
                {
                    $errors[] = 'The username must be between ' . $GLOBALS['registration']['userMinLength'] . ' and ' . $GLOBALS['registration']['userMaxLength'] . ' letters.';
                }

                if (strlen($password) > $GLOBALS['registration']['passMaxLength'] || strlen($password) < $GLOBALS['registration']['passMinLength'])
                {
                    $errors[] = 'The password must be between ' . $GLOBALS['registration']['passMinLength'] . ' and ' . $GLOBALS['registration']['passMaxLength'] . ' letters.';
                }

                if ($GLOBALS['registration']['validateEmail'] == true)
                {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false)
                    {
                        $errors[] = 'Enter a valid email address.';
                    }
                }
            }
            global $Connect;
            $conn = $Connect->connectToDB();
            $username_clean  = mysqli_real_escape_string($conn, trim($username));
            $password_clean  = mysqli_real_escape_string($conn, trim($password));
            $username        = mysqli_real_escape_string($conn, trim(strtoupper(strip_tags($username))));
            $email           = mysqli_real_escape_string($conn, trim(strip_tags($email)));
            $password        = mysqli_real_escape_string($conn, trim(strtoupper(strip_tags($password))));
            $repeat_password = mysqli_real_escape_string($conn, trim(strtoupper($repeat_password)));
            $raf             = mysqli_real_escape_string($conn, $raf);


            $Connect->selectDB('logondb', $conn);
            //Check for existing user
            $result = mysqli_query($conn, "SELECT COUNT(id) FROM account WHERE username='". $username ."';");

            if (mysqli_data_seek($result, 0) > 1)
            {
                $errors[] = 'The username already exists!';
            }

            if ($password != $repeat_password)
            {
                $errors[] = 'The passwords does not match!';
            }

            if (!empty($errors))
            {
                //errors found.
                echo "<p><h4>The following errors occured:</h4>";
                if (is_array($errors) || is_object($errors))
                {
                    foreach ($errors as $error)
                    {
                        echo "<strong>*", $error, "</strong><br/>";
                    }
                }

                echo "</p>";
                exit();
            }
            else
            {
                $password = sha1("". $username .":". $password ."");
                mysqli_query($conn, "INSERT INTO account (username, email, sha_pass_hash, joindate, expansion, recruiter) 
                    VALUES('". $username ."','". $email ."','". $password ."','". date("Y-m-d H:i:s") ."','". $GLOBALS['core_expansion'] ."','". $raf ."');");

                $getID = mysqli_query($conn, "SELECT id FROM account WHERE username='". $username ."';");
                $row   = mysqli_fetch_assoc($getID);

                $Connect->selectDB('webdb', $conn);
                mysqli_query($conn, "INSERT INTO account_data (id) VALUES(". $row['id'] .");");

                $result = mysqli_query($conn, "SELECT id FROM account WHERE username='". $username_clean ."';");
                $id     = mysqli_fetch_assoc($result);
                $id     = $id['id'];

                $this->GMLogin($username_clean);

                $_SESSION['cw_user']    = ucfirst(strtolower($username_clean));
                $_SESSION['cw_user_id'] = $id;

                $this->forumRegister($username_clean, $password_clean, $email);
            }
        }

        // Unused
        /*public function forumRegister($username, $password, $email)
        {
            date_default_timezone_set($GLOBALS['timezone']);

            global $phpbb_root_path, $phpEx, $user, $db, $config, $cache, $template;
            if ($GLOBALS['forum']['type'] == 'phpbb' && $GLOBALS['forum']['autoAccountCreate'] == TRUE)
            {
                ////////PHPBB INTEGRATION//////////////
                define('IN_PHPBB', true);
                define('ROOT_PATH', '../..' . $GLOBALS['forum']['forum_path']);

                $phpEx           = "php";
                $phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : ROOT_PATH;

                if (file_exists($phpbb_root_path . 'common.' . $phpEx) && file_exists($phpbb_root_path . 'includes/functions_user.' . $phpEx))
                {
                    include($phpbb_root_path . 'common.' . $phpEx);

                    include($phpbb_root_path . 'includes/functions_user.' . $phpEx);

                    $arrTime  = getdate();
                    $unixTime = strtotime($arrTime['year'] . "-" . $arrTime['mon'] . '-' . $arrTime['mday'] . " " . $arrTime['hours'] . ":" .
                            $arrTime['minutes'] . ":" . $arrTime['seconds']);

                    $user_row = array(
                        'username'             => $username,
                        'user_password'        => phpbb_hash($password),
                        'user_email'           => $email,
                        'group_id'             => (int) 2,
                        'user_timezone'        => (float) 0,
                        'user_dst'             => "0",
                        'user_lang'            => "en",
                        'user_type'            => 0,
                        'user_actkey'          => "",
                        'user_ip'              => $_SERVER['REMOTE_HOST'],
                        'user_regdate'         => $unixTime,
                        'user_inactive_reason' => 0,
                        'user_inactive_time'   => 0
                    );

                    // All the information has been compiled, add the user
                    // tables affected: users table, profile_fields_data table, groups table, and config table.
                    $user_id = user_add($user_row);
                }
            }
        }
        */

        ###############################
        ####### Check if a user is logged in method.
        ###############################

        public function isLoggedIn()
        {
            if (isset($_SESSION['cw_user']))
            {
                header("Location: ?p=account");
            }
        }

        ###############################
        ####### Check if a user is NOT logged in method.
        ###############################

        public function isNotLoggedIn()
        {
            if (!isset($_SESSION['cw_user']))
            {
                header("Location: ?p=login&r=" . $_SERVER['REQUEST_URI']);
            }
        }

        public function isNotGmLoggedIn()
        {
            if (!isset($_SESSION['cw_gmlevel']))
            {
                header("Location: ?p=home");
            }
        }

        ###############################
        ####### Return ban status method.
        ###############################

        public function checkBanStatus($user)
        {
            global $Connect;
            $conn = $Connect->connectToDB();
            $Connect->selectDB('logondb', $conn);
            $acct_id = $this->getAccountID($user);

            $result = mysqli_query($conn, "SELECT bandate, unbandate, banreason FROM account_banned WHERE id=". $acct_id ." AND active=1;");
            if (mysqli_num_rows($result) > 0)
            {
                $row = mysqli_fetch_assoc($result);
                if ($row['bandate'] > $row['unbandate'])
                {
                    $duration = 'Infinite';
                }
                else
                {
                    $duration = $row['unbandate'] - $row['bandate'];
                    $duration = ($duration / 60) / 60;
                    $duration = $duration . ' hours';
                }
                echo '<span class="yellow_text">Banned<br/>
					  Reason: ' . $row['banreason'] . '<br/>
					  Time left: ' . $duration . '</span>';
            }
            else
            {
                echo '<b class="green_text">Active</b>';
            }
        }

        ###############################
        ####### Return account ID method.
        ###############################

        public function getAccountID($user)
        {
            global $Connect;
            $conn   = $Connect->connectToDB();
            $user   = mysqli_real_escape_string($conn, $user);
            $Connect->selectDB('logondb', $conn);

            $result = mysqli_query($conn, "SELECT id FROM account WHERE username='". $user ."';");
            $row    = mysqli_fetch_assoc($result);
            return $row['id'];
        }

        public function getAccountName($id)
        {
            global $Connect;
            $conn = $Connect->connectToDB();
            $id     = mysqli_real_escape_string($conn, $id);
            $Connect->selectDB('logondb', $conn);

            $result = mysqli_query($conn, "SELECT username FROM account WHERE id=". $id .";");
            $row    = mysqli_fetch_assoc($result);
            return $row['username'];
        }

        ###############################
        ####### "Remember me" method. Loads on page startup.
        ###############################

        public function getRemember()
        {
            if (isset($_COOKIE['cw_rememberMe']) && !isset($_SESSION['cw_user']))
            {
                $account_data = explode("*", $_COOKIE['cw_rememberMe']);
                $this->logIn($account_data[0], $account_data[1], $_SERVER['REQUEST_URI'], 835727313);
            }
        }

        ###############################
        ####### Return account Vote Points method.
        ###############################

        public function loadVP($account_name)
        {
            global $Connect;
            $conn = $Connect->connectToDB();
            $accountName = mysqli_real_escape_string($conn, $account_name);
            $acct_id = $this->getAccountID($accountName);
            $Connect->selectDB('webdb', $conn);
            $result  = mysqli_query($conn, "SELECT vp FROM account_data WHERE id=". $acct_id .";");
            if (mysqli_num_rows($result) == 0)
            {
                return 0;
            }
            else
            {
                $row = mysqli_fetch_assoc($result);
                return $row['vp'];
            }
        }

        public function loadDP($account_name)
        {
            global $Connect;
            $conn = $Connect->connectToDB();
            $accountName = mysqli_real_escape_string($conn, $account_name);
            $acct_id = $this->getAccountID($accountName);
            $Connect->selectDB('webdb', $conn);
            $result  = mysqli_query($conn, "SELECT dp FROM account_data WHERE id=". $acct_id .";");
            if (mysqli_num_rows($result) == 0)
            {
                return 0;
            }
            else
            {
                $row = mysqli_fetch_assoc($result);
                return $row['dp'];
            }
        }

        ###############################
        ####### Return email method.
        ###############################

        public function getEmail($account_name)
        {
            global $Connect;
            $conn = $Connect->connectToDB();
            $accountName = mysqli_real_escape_string($conn, $account_name);
            $Connect->selectDB('logondb', $conn);

            $result       = mysqli_query($conn, "SELECT email FROM account WHERE username='". $accountName ."';");
            $row          = mysqli_fetch_assoc($result);
            return $row['email'];
        }

        ###############################
        ####### Return online status method.
        ###############################

        public function getOnlineStatus($account_name)
        {
            global $Connect;
            $conn = $Connect->connectToDB();
            $accountName = mysqli_real_escape_string($conn, $account_name);
            $Connect->selectDB('logondb', $conn);
            $result       = mysqli_query($conn, "SELECT COUNT(online) FROM account WHERE username='" . $accountName . "' AND online=1;");
            if (mysqli_data_seek($result, 0) == 0)
            {
                return '<b class="red_text">Offline</b>';
            }
            else
            {
                return '<b class="green_text">Online</b>';
            }
        }

        ###############################
        ####### Return Join date method.
        ###############################

        public function getJoindate($account_name)
        {
            global $Connect;
            $conn = $Connect->connectToDB();
            $accountName = mysqli_real_escape_string($conn, $account_name);
            $Connect->selectDB('logondb', $conn);

            $result       = mysqli_query($conn, "SELECT joindate FROM account WHERE username='". $account_name ."';");
            $row          = mysqli_fetch_assoc($result);
            return $row['joindate'];
        }

        ###############################
        ####### Returns a GM session if the user is a GM with rank 2 and above.
        ###############################

        public function GMLogin($account_name)
        {
            global $Connect;
            $conn = $Connect->connectToDB();
            $Connect->selectDB('logondb', $conn);
            $accountName = mysqli_real_escape_string($conn, $account_name);
            $acct_id = $this->getAccountID($accountName);

            $result = mysqli_query($conn, "SELECT gmlevel FROM account_access WHERE gmlevel > 2 AND id=". $acct_id .";");
            if (mysqli_num_rows($result) > 0)
            {
                $row                    = mysqli_fetch_assoc($result);
                $_SESSION['cw_gmlevel'] = $row['gmlevel'];
            }
        }

        public function getCharactersForShop($account_name)
        {
            global $Connect;
            $conn = $Connect->connectToDB();
            $accountName = mysqli_real_escape_string($conn, $account_name);

            $acct_id = $this->getAccountID($accountName);

            $Connect->selectDB('webdb', $conn);

            $getRealms = mysqli_query($conn, "SELECT id, name FROM realms;");
            while ($row = mysqli_fetch_assoc($getRealms))
            {
                $Connect->connectToRealmDB($row['id']);
                $result = mysqli_query($conn, "SELECT name, guid FROM characters WHERE account=". $acct_id .";");
                if (mysqli_num_rows($result) == 0 && !isset($x))
                {
                    $x = true;
                    echo '<option value="">No characters found!</option>';
                }

                while ($char = mysqli_fetch_assoc($result))
                {
                    echo '<option value="' . $char['guid'] . '*' . $row['id'] . '">' . $char['name'] . ' - ' . $row['name'] . '</option>';
                }
            }
        }

        public function changeEmail($email, $current_pass)
        {
            $errors = array();

            if (empty($current_pass))
            {
                $errors[] = 'Please enter your current password';
            }
            else
            {
                if (empty($email))
                {
                    $errors[] = 'Please enter an email address.';
                }

                global $Connect;
                $conn = $Connect->connectToDB();

                $Connect->selectDB('logondb', $conn);

                $username = mysqli_real_escape_string($conn, trim(strtoupper($_SESSION['cw_user'])));
                $password = mysqli_real_escape_string($conn, trim(strtoupper($current_pass)));

                $password = sha1("" . $username . ":" . $password . "");

                $result = mysqli_query($conn, "SELECT COUNT(id) FROM account WHERE username='". $username ."' AND sha_pass_hash='". $password ."';");
                if (mysqli_data_seek($result, 0) == 0)
                {
                    $errors[] = 'The current password is incorrect.';
                }

                if ($GLOBALS['registration']['validateEmail'] == true)
                {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false)
                    {
                        $errors[] = 'Enter a valid email address.';
                    }
                    else
                    {
                        mysqli_query($conn, "UPDATE account SET email='". $email ."' WHERE username='". $_SESSION['cw_user'] ."';");
                    }
                }
            }

            if (empty($errors))
            {
                echo 'Successfully updated your account.';
            }
            else
            {
                echo '<div class="news" style="padding: 5px;">
                <h4 class="red_text">The following errors occured:</h4>';
                if (is_array($errors) || is_object($errors))
                {
                    foreach ($errors as $error)
                    {
                        echo '<strong class="yellow_text">*', $error, '</strong><br/>';
                    }
                }
                echo '</div>';
            }
        }

        //Used for the change password page.
        public function changePass($old, $new, $new_repeat)
        {
            global $Connect;
            $conn = $Connect->connectToDB();
            $_POST['current_password']    = mysqli_real_escape_string($conn, $old);
            $_POST['new_password']        = mysqli_real_escape_string($conn, $new);
            $_POST['new_password_repeat'] = mysqli_real_escape_string($conn, $new_repeat);

            //Check if all field values has been typed into
            if (empty($_POST['current_password']) || 
                empty($_POST['new_password']) || 
                empty($_POST['new_password_repeat']))
            {
                echo '<b class="red_text">Please type in all fields!</b>';
            }
            else
            {
                //Check if new passwords match?
                if ($_POST['new_password'] != $_POST['new_password_repeat'])
                {
                    echo '<b class="red_text">The new passwords doesnt match!</b>';
                }
                else
                {
                    if (strlen($_POST['new_password']) < $GLOBALS['registration']['passMinLength'] || strlen($_POST['new_password']) > $GLOBALS['registration']['passMaxLength'])
                    {
                        echo "<b class='red_text'>Your password must be between ". $GLOBALS['registration']['passMinLength'] ." and ". $GLOBALS['registration']['passMaxLength'] ." letters</b>";
                    }
                    else
                    {
                        //Lets check if the old password is correct!
                        $username = mysqli_real_escape_string($conn, strtoupper($_SESSION['cw_user']));

                        $Connect->selectDB('logondb', $conn);

                        $getPass = mysqli_query($conn, "SELECT `sha_pass_hash` FROM `account` WHERE `username`='". $username ."';");
                        $row     = mysqli_fetch_assoc($getPass);
                        $thePass = $row['sha_pass_hash'];

                        $pass      = mysqli_real_escape_string($conn, strtoupper($_POST['current_password']));
                        $pass_hash = sha1("". $username .":". $pass ."");

                        $new_password      = mysqli_real_escape_string($conn, strtoupper($_POST['new_password']));
                        $new_password_hash = sha1("". $username .":". $new_password ."");

                        if ($thePass != $pass_hash)
                        {
                            echo "<b class='red_text'>The old password is not correct!</b>'";
                        }
                        else
                        {
                            //success, change password
                            echo "<b class='green_text'>Your Password was changed!</b>";
                            mysqli_query($conn, "UPDATE account SET sha_pass_hash='". $new_password_hash ."' WHERE username='". $username ."';");
                            mysqli_query($conn, "UPDATE account SET v=0 AND s=0 WHERE username='". $username ."';");
                        }
                    }
                }
            }
        }

        public function changePassword($account_name, $password)
        {
            global $Connect;
            $conn = $Connect->connectToDB();
            $username  = mysqli_real_escape_string($conn, strtoupper($account_name));
            $pass      = mysqli_real_escape_string($conn, strtoupper($password));
            $pass_hash = sha1($username . ':' . $pass);

            $Connect->selectDB('logondb', $conn);
            mysqli_query($conn, "UPDATE `account` SET `sha_pass_hash`='". $pass_hash ."' WHERE `username`='". $username ."';");
            mysqli_query($conn, "UPDATE `account` SET `v`=0 AND `s`=0 WHERE username='". $username ."';");

            $this->logThis("Changed password", "passwordchange", NULL);
        }

        public function forgotPW($account_name, $account_email)
        {
            global $Website, $Account, $Connect;
            $conn = $Connect->connectToDB();

            $accountName  = mysqli_real_escape_string($conn, $account_name);
            $accountEmail = mysqli_real_escape_string($conn, $account_email);

            if (empty($accountName) || empty($accountEmail))
            {
                echo '<b class="red_text">Please enter both fields.</b>';
            }
            else
            {
                $Connect->selectDB('logondb', $conn);
                $result = mysqli_query($conn, "SELECT COUNT('id') FROM account WHERE username='". $accountName ."' AND email='". $accountEmail ."';");

                if (mysqli_data_seek($result, 0) == 0)
                {
                    echo '<b class="red_text">The username or email is incorrect.</b>';
                }
                else
                {
                    //Success, lets send an email & add the forgotpw thingy.
                    $code = RandomString();
                    $Website->sendEmail($accountEmail, $GLOBALS['default_email'], 'Forgot Password', "
				Hello there. <br/><br/>
				A password reset has been requested for the account " . $accountName . " <br/>
				If you wish to reset your password, click the following link: <br/>
				<a href='" . $GLOBALS['website_domain'] . "?p=forgotpw&code=" . $code . "&account=" . $this->getAccountID($accountName) . "'>
				" . $GLOBALS['website_domain'] . "?p=forgotpw&code=" . $code . "&account=" . $this->getAccountID($accountName) . "</a>
				
				<br/><br/>
				
				If you did not request this, just ignore this message.<br/><br/>
				Sincerely, The Management.");

                    $account_id = $this->getAccountID($accountName);
                    $Connect->selectDB('webdb', $conn);

                    mysqli_query($conn, "DELETE FROM password_reset WHERE account_id=". $account_id .";");
                    mysqli_query($conn, "INSERT INTO password_reset (code, account_id)
                        VALUES ('". $code ."', ". $account_id .");");
                    echo "An email containing a link to reset your password has been sent to the Email address you specified. 
					  If you've tried to send other forgot password requests before this, they won't work. <br/>";
                }
            }

            function hasVP($account_name, $points)
            {
                global $Connect;
                $conn = $Connect->connectToDB();

                $points         = mysqli_real_escape_string($conn, $points);
                $accountName    = mysqli_real_escape_string($conn, $account_name);

                $account_id = $this->getAccountID($accountName);
                $Connect->selectDB('webdb', $conn);
                $result = mysqli_query($conn, "SELECT COUNT('id') FROM account_data WHERE vp >= ". $points ." AND id=". $account_id .";");

                if (mysqli_data_seek($result, 0) == 0)
                {
                    return FALSE;
                }
                else
                {
                    return TRUE;
                }
            }

            function hasDP($account_name, $points)
            {
                global $Connect;
                $conn = $Connect->connectToDB();

                $points         = mysqli_real_escape_string($conn, $points);
                $accountName    = mysqli_real_escape_string($conn, $account_name);
                $account_id = $this->getAccountID($accountName);
                $Connect->selectDB('webdb', $conn);

                $result = mysqli_query($conn, "SELECT COUNT('id') FROM account_data WHERE dp >=". $points ." AND id=". $account_id .";");

                if (mysqli_data_seek($result, 0) == 0)
                {
                    return FALSE;
                }
                else
                {
                    return TRUE;
                }
            }

            function deductVP($account_id, $points)
            {
                global $Connect;
                $conn = $Connect->connectToDB();

                $points     = mysqli_real_escape_string($conn, $points);
                $accountId  = mysqli_real_escape_string($conn, $account_id);
                $Connect->selectDB('webdb', $conn);

                mysqli_query($conn, "UPDATE account_data SET vp=vp - ". $points ." WHERE id=". $accountId .";");
            }

            function deductDP($account_id, $points)
            {
                global $Connect;
                $conn = $Connect->connectToDB();

                $points     = mysqli_real_escape_string($conn, $points);
                $accountId  = mysqli_real_escape_string($conn, $account_id);
                $Connect->selectDB('webdb', $conn);

                mysqli_query($conn, "UPDATE account_data SET dp=dp - ". $points ." WHERE id=". $accountId .";");
            }

            function addDP($account_id, $points)
            {
                global $Connect;
                $conn = $Connect->connectToDB();

                $accountId  = mysqli_real_escape_string($conn, $account_id);
                $points     = mysqli_real_escape_string($conn, $points);
                $Connect->selectDB('webdb', $conn);

                mysqli_query($conn, "UPDATE account_data SET dp=dp + ". $points ." WHERE id=". $accountId .";");
            }

            function addVP($account_id, $points)
            {
                global $Connect;
                $conn = $Connect->connectToDB();

                $accountId  = mysqli_real_escape_string($conn, $account_id);
                $points     = mysqli_real_escape_string($conn, $points);
                $Connect->selectDB('webdb', $conn);

                mysqli_query($conn, "UPDATE account_data SET dp=dp + ". $points ." WHERE id=". $accountId .";");
            }

            function getAccountIDFromCharId($char_id, $realm_id)
            {
                global $Connect;
                $conn = $Connect->connectToDB();

                $charId  = mysqli_real_escape_string($conn, $char_id);
                $realmId = mysqli_real_escape_string($conn, $realm_id);

                $Connect->selectDB('webdb', $conn);
                $Connect->connectToRealmDB($realmId);

                $result = mysqli_query($conn, "SELECT account FROM characters WHERE guid=". $charId .";");
                $row    = mysqli_fetch_assoc($result);
                return $row['account'];
            }

            function isGM($account_name)
            {
                global $Connect;
                $conn = $Connect->connectToDB();

                $accountName = mysqli_real_escape_string($conn, $account_name);
                $account_id  = $this->getAccountID($accountName);
                $result      = mysqli_query($conn, "SELECT COUNT(id) FROM account_access WHERE id=". $account_id ." AND gmlevel >= 1;");
                if (mysqli_data_seek($result, 0) > 0)
                {
                    return TRUE;
                }
                else
                {
                    return FALSE;
                }
            }

            function logThis($desc, $service, $realmid)
            {
                global $Connect;
                $conn = $Connect->connectToDB();

                $desc    = mysqli_real_escape_string($conn, $desc);
                $realmId = mysqli_real_escape_string($conn, $realmid);
                $service = mysqli_real_escape_string($conn, $service);
                $account = mysqli_real_escape_string($conn, $_SESSION['cw_user_id']);

                $Connect->selectDB('webdb', $conn);
                mysqli_query($conn, "INSERT INTO user_log (`account`, `service`, `timestamp`, `ip`, `realmid`, `desc`) 
                    VALUES('". $account ."','". $service ."','". time() ."','". $_SERVER['REMOTE_ADDR'] ."','". $realmId ."','". $desc ."');");
            }
        }
    }

    $Account = new Account();
    