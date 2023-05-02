<?php

namespace App\Api\V1\Controllers\Pdf;

use App\Api\V1\Controllers\BaseController;
use App\DTOs\RequestData;
use App\Events\Tasks\TaskCreatedEvent;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use OpenApi\Annotations as OA;

/**
 * @OA\Post(
 *      path="/pdf",
 *      operationId="pdf",
 *      tags={"Pdf"},
 *      summary="Create pdf",
 *      description="Create pdf task",
 *      security={{"passport":{}}},
 *
 *     @OA\RequestBody(
 *          required=true,
 *          @OA\JsonContent(ref="#/components/schemas/PdfRequest")
 *      ),
 *      @OA\Response(response=200,description="successful operation",
 *          @OA\MediaType(mediaType="application/json", @OA\Schema(ref="#/components/schemas/TaskCreated"))
 *      ),
 *      @OA\Response(response=400, description="Bad request"),
 *      @OA\Response(response=404, description="Resource Not Found"),
 * )
 */
class PdfController extends BaseController
{
    public function __construct()
    {
    }

    public function export(RequestData $request)
    {
        $data = [];
        if($request->data['articleId']){
            $data = [...$data, 'article_id'=>$request->data['articleId']];
        }
        if($request->data['articleTitle']){
            $data = [ ...$data, 'article_title'=>$request->data['articleTitle']];
        }

        $taskId = Queue::connection('rabbitmq')
            ->pushRaw(json_encode($this->createTaskPayload($request->data)), null, [
                'exchange_routing_key'=>'pdf.event.export',
                'taskType' => 'pdf.export',
                'exchange' => 'pdf.service',
                'exchange_type' => 'topic',
                'withReply'=>true,
                'replyTo' => env('RABBITMQ_REPLY_TO_QUEUE','task_reply_queue'),
                'user'=>$user ?? $request->user,
                'data' => $data
            ]);

        $user = $request->user;

        $data = Redis::get('user:'.$user->id);

        $data = $this->unserialize($data);

        $key=array_search($taskId, array_column(json_decode(json_encode($data),TRUE), 'task_id'));
        if($key !== false){
            $task = $data[$key];
            broadcast(new TaskCreatedEvent($task, $user->id));
        }
        return $this->response()->array(['task_id' => $taskId]);
    }

    protected function unserialize($value)
    {
        return is_numeric($value) ? $value : unserialize($value);
    }
}
