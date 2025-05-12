<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Reinsurance API",
 *     description="Документация для API перестрахования",
 *     @OA\Contact(
 *         email="support@yourdomain.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url="/api/v1",
 *     description="Основной сервер API"
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
