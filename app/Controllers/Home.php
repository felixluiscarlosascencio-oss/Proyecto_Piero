<?php

namespace App\Controllers;

use App\Models\ServicioModel;

class Home extends BaseController
{
    public function index(): string
    {
        $recaptchaSiteKey = trim((string) env('RECAPTCHA_SITE_KEY'));
        $recaptchaSecretKey = trim((string) env('RECAPTCHA_SECRET_KEY'));
        $recaptchaConfigured = $recaptchaSiteKey !== '' && $recaptchaSecretKey !== '';
        $recaptchaMisconfigured = ($recaptchaSiteKey !== '') !== ($recaptchaSecretKey !== '');

        return view('public/home', [
            'servicios' => (new ServicioModel())
                ->select('id, nombre_item, descripcion, detalle_largo, galeria_json, etiqueta_destacada')
                ->orderBy('nombre_item', 'ASC')
                ->findAll(),
            'recaptchaSiteKey' => $recaptchaConfigured ? $recaptchaSiteKey : '',
            'recaptchaMisconfigured' => $recaptchaMisconfigured,
        ]);
    }
}