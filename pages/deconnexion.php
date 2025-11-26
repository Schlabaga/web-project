<?php
    $_SESSION = array();

    session_destroy();

    header("Location: index.php?page=navigation");
    exit();
?>