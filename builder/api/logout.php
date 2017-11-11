<?php
unset($_SESSION['username']); // will logout only
//session_destroy(); // will delete ALL data associated with that user.

echo json_encode(true);