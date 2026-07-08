<?php
defined('ABSPATH') || exit;
// WordPress uses home.php for the "Posts page" (Settings → Reading).
// Delegate to archive.php so both use the same template.
include get_template_directory() . '/archive.php';
