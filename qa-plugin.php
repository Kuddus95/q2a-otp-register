<?php
/*
Plugin Name: OTP Verification for Registration
Plugin URI: https://github.com/
Plugin Description: Adds Firebase OTP verification before user registration.
Plugin Version: 1.0
Plugin Date: 2025-06-21
Plugin Author: Aman
Plugin License: GPLv2
*/

if (!defined('QA_VERSION')) {
    header('Location: ../../');
    exit;
}

qa_register_plugin_module('page', 'q2a-otp-register.php', 'q2a_otp_register', 'OTP Register Page');
qa_register_plugin_module('module', 'q2a-otp-admin.php', 'q2a_otp_admin', 'OTP Config Admin');
qa_register_plugin_overrides('q2a-otp-redirect.php');



/*
	Omit PHP closing tag to help avoid accidental output
*/