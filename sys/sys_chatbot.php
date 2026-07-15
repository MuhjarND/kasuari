<?php

$chatbot_gateway_validate_url = getenv('CHATBOT_GATEWAY_VALIDATE_URL') ?: 'https://bot.pta-papuabarat.go.id/api/magic-login/validate';
$chatbot_gateway_internal_api_key = getenv('CHATBOT_GATEWAY_INTERNAL_API_KEY') ?: 'd4be9bf34627df09f8f3398dee46d5676267239ed9c837f58a144133a2e2cd291';
$chatbot_application_code = getenv('CHATBOT_APPLICATION_CODE') ?: 'kasuari';
$chatbot_autologin_error_message = getenv('CHATBOT_AUTOLOGIN_ERROR_MESSAGE')
  ?: 'Link login tidak valid atau sudah kedaluwarsa. Silakan minta link baru melalui chatbot.';

