<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /VSUASGP/index.php");
    exit;
}
