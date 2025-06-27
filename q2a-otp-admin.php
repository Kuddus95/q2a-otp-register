<?php
class q2a_otp_admin
{
    public function option_default($option)
    {
        switch ($option) {
            case 'otp_sms_api_key':
                return '';
            case 'otp_sms_sender_id':
                return '';
        }
    }

    public function admin_form(&$qa_content)
    {
        $saved = false;

        if (qa_clicked('otp_sms_save_button')) {
            qa_opt('otp_sms_api_key', qa_post_text('otp_sms_api_key'));
            qa_opt('otp_sms_sender_id', qa_post_text('otp_sms_sender_id'));
            $saved = true;
        }

        return [
            'ok' => $saved ? 'SMS API config saved successfully!' : null,

            'fields' => [
                [
                    'label' => '🔐 SMS API Key',
                    'type' => 'text',
                    'value' => qa_opt('otp_sms_api_key'),
                    'tags'  => 'name="otp_sms_api_key"'
                ],
                [
                    'label' => '📛 Sender ID',
                    'type' => 'text',
                    'value' => qa_opt('otp_sms_sender_id'),
                    'tags'  => 'name="otp_sms_sender_id"'
                ],
                
                [
                    'label' => 'To get API key and Sender ID',
                    'type' => 'static',
                    'value' => 'Please Visit: <a href="https://bulksmsbd.net" style="color: blue;">bulksmsbd.com</a>, Get registered and buy any package.',
                    
                    'note' => 'If you have any questions or need support, feel free to reach out to the developer.'
                    
                    ]
                
            ],

            'buttons' => [
                [
                    'label' => 'Save SMS Config',
                    'tags' => 'name="otp_sms_save_button"'
                ]
            ]
        ];
    }
}
