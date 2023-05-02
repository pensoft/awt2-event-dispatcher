<?php

namespace App\Virtual\Requests;

use App\Enums\NotificationActionEnum;
use App\Enums\PdfActionEnum;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *      title="Create Pdf task",
 *      description="Create Pdf task",
 *      type="object",
 *      required={"action"},
 *      @OA\Xml(
 *         name="PdfRequest"
 *      )
 * )
 */
class PdfRequest
{
    /**
     * @OA\Property(
     *     title="action",
     *     description="The pdf method to be executed",
     *     enum={"export"},
     *     type="string",
     * )
     *
     * @var PdfActionEnum
     */
    private PdfActionEnum $action;
}
