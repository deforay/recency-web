<?php
/**
 * Local Configuration Override
 *
 * This configuration override file is for overriding environment-specific and
 * security-sensitive configuration information. Copy this file without the
 * .dist extension at the end and populate values as needed.
 *
 * @NOTE: This file is ignored from Git by default with the .gitignore included
 * in ZendSkeletonApplication. This is a good practice, as it prevents sensitive
 * credentials from accidentally being committed into version control.
 */



$env = getenv('APP_ENV') ?: 'production';



if($env == 'development'){
    $local['db'] = array(
                    'username' => 'root',
                    'password' => 'zaq12345',
                );
    $local['google'] = array(
                    'redirect' => 'http://legalcm.co/login/google-oauth'
                );
}



if($env == 'testing'){
    $local['db'] = array(
                    'username' => 'root',
                    'password' => 'zaq12345',
                );
    $local['google'] = array(
                    'redirect' => 'http://legalcm.deforay.in/login/google-oauth'
                );                
}



if($env == 'production'){
    $local['db'] = array(
                    'username' => 'root',
                    'password' => 'zaq12345',
                );
    $local['google'] = array(
                   // 'redirect' => 'https://makka.co.uk/login/google-oauth'
                   'redirect' => 'http://legalcm.co/login/google-oauth'
                );                
}


return $local;
