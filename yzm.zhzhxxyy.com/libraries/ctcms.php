<?php

require_once(FCPATH . 'libraries/config.php');
require_once(FCPATH . 'libraries/database.php');
$install_path = FCPATH . "packs/install/install.lock";
if( !file_exists($install_path) && strpos($_SERVER["REQUEST_URI"], "install") === false ) 
{
    $web_path = str_replace("\\", "/", str_replace(getcwd(), "", FCPATH));
    header('location:' . $web_path . "index.php/install");
    exit();
}

return 1;


