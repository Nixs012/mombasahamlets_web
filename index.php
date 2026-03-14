<?php
/**
 * Root index.php - Redirects to frontend
 * This file ensures that accessing http://localhost/mombasahamlets_web/
 * automatically redirects to the frontend folder
 */
header('Location: frontend/index.php');
exit;
?>

