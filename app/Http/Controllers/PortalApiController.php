<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PortalApp;
use PortalContent;
use PortalFiles;
use PortalMail;

/**
 * API JSON que usa la aplicación del navegador (public/assets/app y public/*.js).
 * Antes se atendía en index.php?route=api/...; ahora cada ruta está en routes/web.php.
 */
class PortalApiController extends Controller
{
    public function __construct(private readonly PortalApp $portal) {}

    /** Respuesta JSON con el mismo formato y escape que la versión anterior. */
    public static function json(array $body, int $status = 200): JsonResponse
    {
        return new JsonResponse(PortalContent::forJson($body), $status, [],
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
    }

    /** Cuerpo JSON validado por el núcleo (tamaño, formato y estructura). */
    private function input(): array
    {
        return $this->portal->input();
    }

    public function bootstrap(): JsonResponse
    {
        return self::json($this->portal->bootstrap());
    }

    public function save(): JsonResponse
    {
        return self::json($this->portal->save($this->input()));
    }

    public function login(): JsonResponse
    {
        return self::json($this->portal->login($this->input()));
    }

    public function logout(): JsonResponse
    {
        return self::json($this->portal->logout());
    }

    public function changePassword(): JsonResponse
    {
        $this->portal->changePassword($this->input());

        return self::json(['ok' => true]);
    }

    public function file(Request $request): Response
    {
        $file = PortalFiles::serve($this->portal, (string) $request->query('id', ''), $request->query->has('download'));

        return new Response($file['body'], 200, $file['headers']);
    }

    public function upload(): JsonResponse
    {
        return self::json($this->portal->upload(), 201);
    }

    public function challenge(): JsonResponse
    {
        return self::json($this->portal->challenge($this->input()));
    }

    public function interfaceEvent(): JsonResponse
    {
        return self::json($this->portal->interfaceEvent($this->input()));
    }

    public function accounts(): JsonResponse
    {
        return self::json($this->portal->accountOperation($this->input()));
    }

    public function registerCompany(): JsonResponse
    {
        return self::json($this->portal->registerCompany($this->input()), 201);
    }

    public function track(): JsonResponse
    {
        return self::json($this->portal->track($this->input()));
    }

    public function mailStatus(): JsonResponse
    {
        return self::json($this->portal->mailStatus());
    }

    public function mailRetry(): JsonResponse
    {
        return self::json(PortalMail::retry($this->portal, $this->input()));
    }

    public function smtpForm(): JsonResponse
    {
        return self::json($this->portal->smtpForm());
    }

    public function saveSmtp(): JsonResponse
    {
        return self::json($this->portal->saveSmtp($this->input()));
    }

    public function submit(string $type): JsonResponse
    {
        $result = $this->portal->submit($type, $this->input());
        $status = $result['status'];
        unset($result['status']);

        return self::json($result, $status);
    }

    public function lookup(string $type): JsonResponse
    {
        return self::json($this->portal->lookup($type, $this->input()));
    }
}
