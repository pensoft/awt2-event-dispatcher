<?php
namespace App\Api\V1\Controllers\Notifications;

use App\Api\V1\Controllers\BaseController;
use App\DTOs\RequestData;
use App\Jobs\Notifications\SendArticleCommentNotification;
use App\Jobs\Notifications\SendArticleInvitationNotification;
use Illuminate\Support\Facades\Queue;
use OpenApi\Annotations as OA;

/**
 * @OA\Post(
 *      path="/notifications",
 *      operationId="notifications",
 *      tags={"Notification"},
 *      summary="Create Notification",
 *      description="Create notification",
 *      security={{"passport":{}}},
 *
 *     @OA\RequestBody(
 *          required=true,
 *          @OA\JsonContent(ref="#/components/schemas/NotificationRequest")
 *      ),
 *      @OA\Response(response=200,description="successful operation",
 *          @OA\MediaType(mediaType="application/json")
 *      ),
 *      @OA\Response(response=400, description="Bad request"),
 *      @OA\Response(response=404, description="Resource Not Found"),
 * )
 */
class NotificationsController extends BaseController
{
    public function __construct()
    {
    }

    public function sendArticleCommentNotification(RequestData $request)
    {
        //SendArticleCommentNotification::dispatch($request->data);//->delay(now()->addMinutes(1));
        $job = app()->makeWith(SendArticleCommentNotification::class, ['data'=>$request->data]);
        Queue::connection('rabbitmq')
            ->push($job, '', null, [
                'exchange_routing_key'=>'notification.comment.send',
                'exchange' => 'notification.service',
                'exchange_type' => 'topic',
                'withReply'=>true,
                'replyTo' => env('RABBITMQ_REPLY_TO_QUEUE','task_reply_queue'),
                'user'=>$user ?? $request->user
            ]);
        return $this->response()->array([
            'status' => 'ok',
        ]);

    }

    public function sendArticleInvitationNotification(RequestData $request)
    {

        $job = app()->makeWith(SendArticleInvitationNotification::class, ['data'=>$request->data]);
        Queue::connection('rabbitmq')
            ->push($job, '', null, [
                'exchange_routing_key'=>'notification.invitation.send',
                'exchange' => 'notification.service',
                'exchange_type' => 'topic',
                'withReply'=>false,
                'user'=>$user ?? $request->user
            ]);

        return $this->response()->array([
            'status' => 'ok',
        ]);

        //SendArticleInvitationNotification::dispatch($request->all());//->delay(now()->addMinutes(1));
    }
}
