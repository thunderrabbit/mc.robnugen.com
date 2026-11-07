<?php

/**
 * This file tries to simplify knowing if user is logged in.
 *
 *
 */

namespace Auth;

class IsLoggedIn
{
    private int $who_is_logged_in = 0;

    private string $loggedInUsername = 'YUNOset?'; // default value, should be overwritten if user is logged in
    public function __construct(
        private readonly \PDO $di_pdo,
        private readonly \Config $di_config,
    ) {
    }

    public function checkLogin(\Mlaphp\Request $mla_request): void
    {
        $found_user_id = 0;
        $cookie = $mla_request->cookie[$this->di_config->cookie_name] ?? null;
        $username = $mla_request->post['username'] ?? null;
        $password = $mla_request->post['pass'] ?? null;
        if (is_string($cookie) && $cookie !== '') {
            $found_user_id = $this->getUserIdForCookieInDatabase(
                cookie: $cookie,
                ip_address: self::serverString('REMOTE_ADDR'),
                user_agent: self::serverString('HTTP_USER_AGENT')
            );
            if (empty($found_user_id)) {
                $this->killCookie();
                $this->who_is_logged_in = 0;
            } else {
                $this->who_is_logged_in = $found_user_id;
            }
        } elseif (is_string($username) && $username !== '' && is_string($password) && $password !== '') {
            $found_user_id = $this->checkPHPHashedPassword($username, $password);
            if (empty($found_user_id)) {
                $this->killCookie();        // bad login, so kill any cookie
                $this->who_is_logged_in = 0;
            } else {
                $this->setAutoLoginCookie($found_user_id);
                $this->who_is_logged_in = $found_user_id;
            }
        }
        // set the session variable for username
        $this->setUsernameOfLoggedInID($this->who_is_logged_in);
    }

    /**
     * A $_SERVER entry as a string, or '' when it is absent or not a string.
     */
    private static function serverString(string $key): string
    {
        $value = $_SERVER[$key] ?? '';
        return is_string($value) ? $value : '';
    }

    private function setUsernameOfLoggedInID(int $user_id): void
    {
        if ($user_id <= 0) {
            return;
        }
        // set the session variable for username
        $stmt = $this->di_pdo->prepare("SELECT `username` FROM `users` WHERE `user_id` = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $username = $stmt->fetchColumn();

        if (is_string($username)) {
            $this->loggedInUsername = $username;
        }
    }

    public function getLoggedInUsername(): string
    {
        return $this->loggedInUsername;
    }

    public function getUserRole(): string
    {
        if ($this->who_is_logged_in <= 0) {
            return '';
        }

        $stmt = $this->di_pdo->prepare("SELECT `role` FROM `users` WHERE `user_id` = ? LIMIT 1");
        $stmt->execute([$this->who_is_logged_in]);
        $role = $stmt->fetchColumn();

        return is_string($role) ? $role : '';
    }

    public function isAdmin(): bool
    {
        return $this->getUserRole() === 'admin';
    }
    private function setAutoLoginCookie(int $user_id): void
    {
        $cookie = \Utilities::randomString(32);

        $record = [
            'user_id' => $user_id,
            'cookie' => $cookie,
            'last_access' => date(format: "Y-m-d H:i:s"),
            'user_agent_md5' => md5(self::serverString('HTTP_USER_AGENT')),
            'ip_address' => \Auth\IPBin::ipToBinary(ip: self::serverString('REMOTE_ADDR'))
        ];

        // Insert using native PDO
        $stmt = $this->di_pdo->prepare("INSERT INTO `cookies` (`user_id`, `cookie`, `last_access`, `user_agent_md5`, `ip_address`) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(array_values($record));

        $cookie_options = [
            'expires' => time() + $this->di_config->cookie_lifetime, // 30 days
            'path' => '/',
            'domain' => $this->di_config->domain_name,
            'samesite' => 'Strict' // None || Lax  || Strict
        ];
        setcookie($this->di_config->cookie_name, $cookie, $cookie_options);
    }



    /**
     * @param string $username
     * @return array<mixed> The user_id and password_hash row, or [] when there is no such user
     */
    private function getIDandPHPHashedPasswordForUsername(string $username)
    {
        // get password hash
        $stmt = $this->di_pdo->prepare("SELECT `user_id`, `password_hash` FROM `users` WHERE LOWER(`username`) = LOWER(?) LIMIT 1");
        $stmt->execute([$username]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : [];
    }
    /**
     * Looks up hashed password for username, and checks it against the password provided
     * @param string $username
     * @param string $password
     * @return int The user_id, or 0 when the password does not match
     */
    private function checkPHPHashedPassword(string $username, string $password): int
    {
        // get password hash
        $user_id_and_hash_array = $this->getIDandPHPHashedPasswordForUsername($username);
        if (!empty($user_id_and_hash_array)) {
            $hashed_password = $user_id_and_hash_array['password_hash'] ?? null;
            // check it
            if (is_string($hashed_password) && password_verify($password, $hashed_password)) {
                // password is correct, so this user_id has logged in properly
                $user_id = $user_id_and_hash_array['user_id'] ?? 0;
                return is_numeric($user_id) ? (int) $user_id : 0;
            }
        } else {
            return 0;
        }

        return 0;
    }


    private function getUserIdForCookieInDatabase(
        string $cookie,
        string $ip_address,
        string $user_agent
    ): int {
        $varbinary_ip = \Auth\IPBin::ipToBinary($ip_address);
        $stmt = $this->di_pdo->prepare("SELECT `user_id` FROM `cookies` WHERE `cookie` = ? AND `ip_address` = ? AND `user_agent_md5` = ? LIMIT 1");
        $stmt->execute([$cookie, $varbinary_ip, md5($user_agent)]);
        $user_id = $stmt->fetchColumn();

        if (is_numeric($user_id)) {
            return (int) $user_id;
        } else {
            return 0;
        }
    }
    public function isLoggedIn(): bool
    {
        return $this->who_is_logged_in > 0;
    }

    public function loggedInID(): int
    {
        return $this->who_is_logged_in;
    }


    public function logout(): void
    {
        $this->who_is_logged_in = 0;
        $this->killCookie();
        session_destroy();
        session_start();
        session_regenerate_id();
    }

    private function killCookie(): void
    {
        $cookie_options = [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => $this->di_config->domain_name,
            'samesite' => 'Strict' // None || Lax  || Strict
        ];
        setcookie($this->di_config->cookie_name, '', $cookie_options);
        $this->who_is_logged_in = 0;
    }
}
