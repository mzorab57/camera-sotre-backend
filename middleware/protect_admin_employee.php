<?php
require_once __DIR__ . '/require_role.php';
require_auth();
require_role(['admin','employee']);