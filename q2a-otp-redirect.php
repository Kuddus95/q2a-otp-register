<?php
function qa_get_request_content()
{
    $request = qa_request();

    if ($request === 'register') {
        qa_redirect('otp-register');
    }

    return qa_get_request_content_base();
}
