<?php
    define('DATA_DIR', '/your/path/to/application/root/dir');
    define('LIB_DIR', '/your/path/to/core/lib/dir');
    define('BOOTSTRAP_FILE', basename(__FILE__));
    // define('NAMESPACE', 'config_namespace_if_needed');
    include(LIB_DIR . 'libs/Init.php');
    Init::main(__FILE__);
