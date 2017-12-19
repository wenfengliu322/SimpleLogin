<?php

/**
 * Created by PhpStorm.
 * User: Liu
 */
class User
{
    /** @var object $pdo Copy of PDO connection */
    private $pdo;
    /** @var object of the logged in user */
    private $user;
    /** @var string error msg */
    private $msg;
    /** @var int number of permitted wrong login attemps */
    private $permittedAttemps = 5;

    /**
     * Connection init function
     * @param string $conString DB connection string.
     * @param string $user DB user.
     * @param string $pass DB password.
     *
     * @return bool Returns connection success.
     */
    public function dbConnect($conString, $user, $pass){
        if(session_status() === PHP_SESSION_ACTIVE){
            try {
                $pdo = new PDO($conString, $user, $pass);
                $this->pdo = $pdo;
                return true;
            }catch(PDOException $e) {
                $this->msg = 'Connection did not work out!';
                return false;
            }
        }else{
            $this->msg = 'Session did not start.';
            return false;
        }
    }

    /**
     * Return the logged in user.
     * @return user array data
     */
    public function getUser(){
        return $this->user;
    }

    /**
     * Register a new user account function
     * @param string $email User email.
     * @param string $uname User name.
     * @param string $pass User password.
     * @return boolean of success.
     */
    public function registration($email, $uname, $pass){
        $pdo = $this->pdo;
        if($this->checkEmail($email)){
            $this->msg = 'This email is already taken.';
            return false;
        }
        if(!(isset($email) && isset($uname) && isset($pass) && filter_var($email, FILTER_VALIDATE_EMAIL))){
            $this->msg = 'Insert all valid required fields.';
            return false;
        }

        $pass = $this->hashPass($pass);
        $activateCode = $this->hashPass(date('Y-m-d H:i:s').$email);
        $stmt = $pdo->prepare('INSERT INTO users (username, email, password, activate_code) VALUES (?, ?, ?, ?)');
        if($stmt->execute([$uname, $email, $pass, $activateCode])){
            if($this->sendConfirmationEmail($email)){
                return true;
            }else{
                $this->msg = 'activate email sending has failed.';
                return false;
            }
        }else{
            $this->msg = 'Inserting a new user failed.';
            return false;
        }
    }

    /**
     * Login function
     * @param string $email User email.
     * @param string $password User password.
     *
     * @return bool Returns login success.
     */
    public function login($email, $password){
        if(is_null($this->pdo)){
            $this->msg = 'Connection did not work out!';
            return false;
        }else{
            $pdo = $this->pdo;
            $stmt = $pdo->prepare('SELECT id, username, email, wrong_logins, password FROM users WHERE email = ? and activated = 1 limit 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if(password_verify($password,$user['password'])){
                if($user['wrong_logins'] <= $this->permittedAttemps){
                    $this->user = $user;
                    session_regenerate_id();
                    $_SESSION['user']['id'] = $user['id'];
                    $_SESSION['user']['username'] = $user['username'];
                    $_SESSION['user']['email'] = $user['email'];
                    return true;
                }else{
                    $this->msg = 'This user account is blocked, please contact our support department.';
                    return false;
                }
            }else{
                $this->registerWrongLoginAttemp($email);
                $this->msg = 'Invalid login information or the account is not activated.';
                return false;
            }
        }
    }

    /**
     * Email the confirmation code function
     * @param string $email User email.
     * @return boolean of success.
     */
    private function sendConfirmationEmail($email){
        $pdo = $this->pdo;
        $stmt = $pdo->prepare('SELECT activate_code FROM users WHERE email = ? limit 1');
        $stmt->execute([$email]);
        $code = $stmt->fetch();

        $subject = 'Confirm your registration';
        $message = 'Please confirm you registration by pasting this code in the confirmation box: '.$code['activate_code'];
        $headers = 'X-Mailer: PHP/' . phpversion();

        if(mail($email, $subject, $message, $headers)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * Activate a login by a activate code and login function
     * @param string $email User email.
     * @param string $activeCode Activation code.
     * @return boolean of success.
     */
    public function emailActivation($email, $activeCode){
        if($this->getActivationCodeByEmail($email) != $activeCode ){
            $this->msg = 'Account activation failed.';
            return false;
        }
        $pdo = $this->pdo;
        $stmt = $pdo->prepare('UPDATE users SET activated = 1 WHERE email = ?');
        $stmt->execute([$email]);
        if($stmt->rowCount()>0){
            $stmt = $pdo->prepare('SELECT id, username, email, wrong_logins FROM users WHERE email = ? and activated = 1 limit 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            $this->user = $user;
            session_regenerate_id();
            if(!empty($user['email'])){
                $_SESSION['user']['id'] = $user['id'];
                $_SESSION['user']['username'] = $user['username'];
                $_SESSION['user']['email'] = $user['email'];
                return true;
            }else{
                $this->msg = 'Account activation failed.';
                return false;
            }
        }else{
            $this->msg = 'Account activation failed.';
            return false;
        }
    }

    /**
     * Return activate_code of the user whose email is $email
     * @param string $email User email.
     * @return string activate_code.
     */
    public function getActivationCodeByEmail($email){
        $pdo = $this->pdo;
        $stmt = $pdo->prepare('SELECT activate_code FROM users WHERE email = ? limit 1');
        $stmt->execute([$email]);
        $act = $stmt->fetch();
        return $act['activate_code'];
    }

    /**
     * Password change function
     * @param int $id User id.
     * @param string $pass New password.
     * @return boolean of success.
     */
    public function passwordChange($id, $pass){
        $pdo = $this->pdo;
        if(isset($id) && isset($pass)){
            $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            if($stmt->execute([$id, $this->hashPass($pass)])){
                return true;
            }else{
                $this->msg = 'Password change failed.';
                return false;
            }
        }else{
            $this->msg = 'Provide an ID and a password.';
            return false;
        }
    }

    /**
     * Check if email is already used function
     * @param string $email User email.
     * @return boolean of success.
     */
    private function checkEmail($email){
        $pdo = $this->pdo;
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? limit 1');
        $stmt->execute([$email]);
        if($stmt->rowCount() > 0){
            return true;
        }else{
            return false;
        }
    }

    /**
     * Register a wrong login attemp function
     * @param string $email User email.
     * @return void.
     */
    private function registerWrongLoginAttemp($email){
        $pdo = $this->pdo;
        $stmt = $pdo->prepare('UPDATE users SET wrong_logins = wrong_logins + 1 WHERE email = ?');
        $stmt->execute([$email]);
    }

    /**
     * Password hash function
     * @param string $pass User password.
     * @return string $password Hashed password.
     */
    private function hashPass($pass){
        return password_hash($pass, PASSWORD_DEFAULT);
    }

    /**
     * Print error msg function
     * @return void.
     */
    public function printMsg(){
        print $this->msg;
    }

    /**
     * Logout the user and remove it from the session.
     *
     * @return true
     */
    public function logout() {
        $_SESSION['user'] = null;
        session_regenerate_id();
        return true;
    }

    /**
     * Simple template rendering function
     * @param string $path path of the template file.
     * @return void.
     */
    public function render($path, $vars = '') {
        ob_start();
        include($path);
        return ob_get_clean();
    }

    /**
     * Template for index head function
     * @return void.
     */
    public function indexHead() {
        print $this->render(indexHead);
    }

    /**
     * Template for index top function
     * @return void.
     */
    public function indexTop() {
        print $this->render(indexTop);
    }

    /**
     * Template for login form function
     * @return void.
     */
    public function loginForm() {
        print $this->render(loginForm);
    }

    /**
     * Template for activation form function
     * @return void.
     */
    public function activationForm() {
        print $this->render(activationForm);
    }

    /**
     * Template for index middle function
     * @return void.
     */
    public function indexMiddle() {
        print $this->render(indexMiddle);
    }

    /**
     * Template for register form function
     * @return void.
     */
    public function registerForm() {
        print $this->render(registerForm);
    }

    /**
     * Template for index footer function
     * @return void.
     */
    public function indexFooter() {
        print $this->render(indexFooter);
    }

    /**
     * Template for user page function
     * @return void.
     */
    public function userPage() {
        print $this->render(userPage);
    }
}