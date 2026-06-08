<?php

it('não exibe etapas de 2fa ou otp na página de login', function () {
    $response = $this->get('/admin/login');

    $response->assertOk();
    $response->assertDontSee('2FA', false);
    $response->assertDontSee('OTP', false);
    $response->assertDontSee('one-time', false);
});
