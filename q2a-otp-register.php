<?php
class q2a_otp_register
{
    public function match_request($request)
    {
        return $request === 'otp-register';
    }

    public function process_request($request)
    {
        require_once QA_INCLUDE_DIR . 'app/users-edit.php';
        require_once QA_INCLUDE_DIR . 'app/captcha.php';
        require_once QA_INCLUDE_DIR . 'app/limits.php';
        require_once QA_INCLUDE_DIR . 'db/users.php';


    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

        $content = qa_content_prepare();
        $content['title'] = 'নতুন সদস্য হিসেবে নিবন্ধন করুন';

        ob_start();

        $status_msg = '';
        $errors = [];

        // Registration logic after OTP
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_user'])) {
            $handle = qa_post_text('handle');
            $email = qa_post_text('email');
            $password1 = qa_post_text('password');
            $password2 = qa_post_text('password2');

            if (!qa_check_form_security_code('register', qa_post_text('code'))) {
                $status_msg = '❌ নিরাপত্তা কোড ভুল।';
            } elseif ($password1 !== $password2) {
                $status_msg = '❌ পাসওয়ার্ড মিলছে না।';
            } else {
                
                // Only accept Gmail addresses
                if (!preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', $email)) {
                    $errors['email'] = '❌ শুধুমাত্র Gmail অ্যাকাউন্ট ব্যবহার করা যাবে (example@gmail.com)';
                }

                 // Terms required
                if (!isset($_POST['terms'])) {
                    $errors['terms'] = 'আপনাকে অবশ্যই শর্তাবলীতে সম্মত হতে হবে।';
                }
                
                // Validate
                $errors = array_merge(
                    $errors, // keep existing Gmail error if present
                    qa_handle_email_filter($handle, $email),
                    qa_password_validate($password1)
                );

                if (empty($errors)) {
                    // All good, register user
                    $userid = qa_create_new_user($email, $password1, $handle);
                    qa_set_logged_in_user($userid, $handle);

                    // Clear session
                    session_destroy();

                    // Redirect to home or wherever
                    qa_redirect('');
                }
            }
        }

        // OTP logic
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Send OTP
            if (isset($_POST['send_otp'])) {
                $mobile = preg_replace('/\D/', '', $_POST['mobile']);
                if (strlen($mobile) < 11) {
                    $status_msg = '❌ মোবাইল নম্বর সঠিক নয়!';
                } else {
                    $otp = rand(100000, 999999);
                    $_SESSION['otp'] = $otp;
                    $_SESSION['otp_mobile'] = $mobile;

                    $api_key = qa_opt('otp_sms_api_key');
                    $sender_id = qa_opt('otp_sms_sender_id');
                    $message = "আপনার OTP: $otp";

                    $params = [
                        'api_key' => $api_key,
                        'senderid' => $sender_id,
                        'number' => $mobile,
                        'message' => $message,
                    ];

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, 'http://bulksmsbd.net/api/smsapi');
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $response = curl_exec($ch);
                    curl_close($ch);

                    if (strpos($response, 'SMS Send Successfully') !== false || strpos($response, '202') !== false) {
                        $_SESSION['otp_sent'] = true;
                        $status_msg = '✅ OTP পাঠানো হয়েছে!';
                    } else {
                        $status_msg = '❌ OTP পাঠাতে ব্যর্থ! উত্তর: ' . htmlspecialchars($response);
                    }
                }
            }

            // Verify OTP
            elseif (isset($_POST['verify_otp'])) {
                if ($_POST['otp_code'] == ($_SESSION['otp'] ?? '')) {
                    $_SESSION['otp_verified'] = true;
                    $_SESSION['otp_verified_time'] = time(); // Save timestamp
                    $status_msg = 'OTP ভেরিফিকেশন সফল হয়েছে!';
                } else {
                    $status_msg = '❌ OTP ভুল হয়েছে!';
                }
            }

        }
        ?>

        <style>
            .qa-form-tall-text {
                width: 100%;
                padding: 8px;
                margin-bottom: 10px;
            }
            .qa-form-tall-button {
                padding: 10px 10px;
                background: #28a745;
                color: white;
                border: none;
                border-radius: 5px;
                cursor: pointer;
            }
            .qa-form-tall-button:hover {
                background: #218838;
            }
        </style>


    <?php
    // OTP expiration logic (must run before checking session below)
    if (isset($_SESSION['otp_verified']) && isset($_SESSION['otp_verified_time'])) {
        $otp_age = time() - $_SESSION['otp_verified_time'];
        if ($otp_age > 300) { // more than 5 minutes
            unset($_SESSION['otp_verified']);
            unset($_SESSION['otp_verified_time']);
            unset($_SESSION['otp_sent']);
            $status_msg = '❌ সময় শেষ! আবার মোবাইল নম্বর দিন।';
        }
    }
    ?>
    
            <?php if (!empty($status_msg)) : ?>
            <center>
            <p style="color: green; font-weight: bold;"><?= qa_html($status_msg) ?></p>
            </center>
            <?php endif; ?>
    

    <?php if (!isset($_SESSION['otp_verified'])) : ?>
 
        <?php if (!isset($_SESSION['otp_sent'])) : ?>
            <form method="post">
                <input type="text" name="mobile" class="qa-form-tall-text" placeholder="মোবাইল নম্বর লিখুন (ex: 01XXXXXXXXX)" required>
                <button type="submit" name="send_otp" class="qa-form-tall-button">OTP পাঠান</button>
            </form>
        <?php else : ?>
            <form method="post">
                <input type="text" name="otp_code" class="qa-form-tall-text" maxlength="6" pattern="\d{6}" placeholder="6-সংখ্যার OTP লিখুন" required>
                <button type="submit" name="verify_otp" class="qa-form-tall-button">ভেরিফাই করুন</button>
            </form>
        <?php endif; ?>


    <?php else : ?>

            <form method="post">
                <label for="handle">সদস্য নাম:</label>
                <input type="text" id="handle" name="handle" placeholder="Username" class="qa-form-tall-text" required><br>
            
                <label for="email">ইমেইল:</label>
                <input type="email" id="email" name="email" placeholder="Email" class="qa-form-tall-text" required><br>
            
                <label for="password">পাসওয়ার্ড:</label>
                <input type="password" id="password" name="password" placeholder="Password" class="qa-form-tall-text" required><br>
            
                <label for="password2">পুনরায় পাসওয়ার্ড:</label>
                <input type="password" id="password2" name="password2" placeholder="Confirm Password" class="qa-form-tall-text" required><br>
            
                <input type="hidden" name="register_user" value="1">
                <input type="hidden" name="code" value="<?= qa_get_form_security_code('register') ?>">

                <div style="margin-bottom: 10px;">
                <label>
                <input type="checkbox" name="terms" id="terms">
                <?= qa_opt('register_terms') ?>
                </label>
                </div>           
                
                <input type="submit" value="Register" class="qa-form-tall-button">
            </form>

        <?php endif; ?>
    
        <?php if (!empty($errors)) : ?>
            <ul style="color: red;">
                <?php foreach ($errors as $err) : ?>
                    <li><?= qa_html($err) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    
        <?php
        $content['custom'] = ob_get_clean();
        return $content;
    }
}
