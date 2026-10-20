<?php
session_start(); 
session_unset(); 
session_destroy();

// back page 
header("Location: login.php");
exit();
?>