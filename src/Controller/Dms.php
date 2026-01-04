<?php

namespace DbService\Controller;

use DbService\Request;
use DbService\Response\HtmlTemplateResponse;
use DbService\Response\JsonResponse;
use DbService\Service\Dms\DmsService;

class Dms extends Base
{
    private DmsService $service;

    public function __construct()
    {
        $this->service = new DmsService();
    }

    public function actionIndex(Request $request): HtmlTemplateResponse
    {
        return new HtmlTemplateResponse('dms');
    }

    public function actionFetch(Request $request): JsonResponse
    {
        $id = $request->getString('version_id');

        if (empty($id)) {
            return new JsonResponse(400, ['error' => "'ID' query parameters is required"]);
        }

        try {
            $sessionToken = $this->service->getSessionToken();
            $data = $this->service->getObjectInfo($sessionToken, $id);

            return new JsonResponse(200, $data);
        } catch (\Throwable $t) {
            return new JsonResponse(503, ['error' => 'DMS fetch error: ' . $t->getMessage()]);
        }
    }
}
