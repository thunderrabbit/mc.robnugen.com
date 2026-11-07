<?php

class Config
{
    /** @var string */
    public $domain_name = '';  // used for cookies
    /** @var string */
    public $cookie_name = '';  // used for cookies
    /** @var int */
    public $cookie_lifetime = 60 * 60 * 24 * 30; // 30 days
    /** @var string */
    public $app_path = '/home/user/sub.domain.com';

    /** @var string */
    public $dbHost = "eich";
    /** @var string */
    public $dbUser = "";
    /** @var string */
    public $dbPass = "";
    /** @var string */
    public $dbName = "";
}
