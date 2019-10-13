<?php
define('IS_ADMIN', true);
define('SELF', pathinfo(preg_replace("@\\(.*\\(.*\$@", "", __FILE__), PATHINFO_BASENAME));
define('FCPATH', str_replace('\\', DIRECTORY_SEPARATOR, dirname(preg_replace("@\\(.*\\(.*\$@", "", __FILE__)) . DIRECTORY_SEPARATOR));
require('index.php');


