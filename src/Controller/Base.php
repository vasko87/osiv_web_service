<?php

namespace DbService\Controller;

use DbService\Request;
use DbService\Response\HtmlTemplateResponse;

class Base
{
    public function actionIndex(Request $request): HtmlTemplateResponse
    {
        return new HtmlTemplateResponse('home');
    }
}
