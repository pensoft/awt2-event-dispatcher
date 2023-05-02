<?php

namespace App\Virtual\Requests;

use App\Enums\NotificationActionEnum;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *      title="Create Notifucation request",
 *      description="Create Notifucation parameters",
 *      type="object",
 *      required={"from", "to", "message", "article", "hash", "actionsss"},
 *      @OA\Xml(
 *         name="NotificationRequest"
 *      )
 * )
 */
class NotificationRequest
{
    /**
     * @OA\Property(
     *     title="from",
     *     description="Notification from",
     *     type="string",
     * )
     *
     * @var string
     */
    private string $from;

    /**
     * @OA\Property(
     *     title="to",
     *     description="Notification to",
     *     type="string",
     * )
     *
     * @var string
     */
    private string $to;


    /**
     * @OA\Property(
     *     title="message",
     *     description="The message of the notification",
     *     type="string",
     * )
     *
     * @var string
     */
    private string $message;

    /**
     * @OA\Property(
     *     title="article",
     *     description="The article object",
     *     type="object",
     *     @OA\Schema(
     *      type="object",
     *          @OA\Property(
     *              property="id",
     *              description="Uuid of the article",
     *              type="string",
     *              example="b8202bff-5aeb-4e1b-9766-11c58e7e4f9d"
     *          ),
     *          @OA\Property(
     *              property="title",
     *              description="Title of the article",
     *              type="string",
     *              example="Title of the article"
     *          ),
     *     )
     * )
     *
     * @var object
     */
    private object $article;


    /**
     * @OA\Property(
     *     title="hash",
     *     description="The hash of the comment",
     *     type="string",
     * )
     *
     * @var string
     */
    private string $hash;

    /**
     * @OA\Property(
     *     title="action",
     *     description="The notification method to be executed",
     *     enum={"sendArticleCommentNotification", "sendArticleInvitationNotification"},
     *     type="string",
     * )
     *
     * @var NotificationActionEnum
     */
    private NotificationActionEnum $action;
}
