<?php

namespace App\Api\V1\Controllers\Tasks;


use App\Api\V1\Controllers\ApiBaseController;
use Dingo\Api\Http\Request;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\Redis;
use OpenApi\Annotations as OA;

class TasksController extends ApiBaseController
{
    use Helpers;

    public function __construct()
    {
    }

    /**
     * @OA\Get(
     *      path="/tasks",
     *      operationId="getTasks",
     *      tags={"Tasks"},
     *      summary="Get all tasks",
     *      description="Returns all tasks",
     *      security={{"passport":{}}},
     *     @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\MediaType(
     *            mediaType="application/json",
     *            @OA\Schema(
     *              type="array",
     *              @OA\Items(ref="#/components/schemas/Task"),
     *            ),
     *          )
     *       ),
     *
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      ),
     *     @OA\Response(
     *          response=404,
     *          description="Resource Not Found"
     *      )
     * )
     */
    public function getTasks(Request $request): array
    {
        $user = $request->user();

        $data = Redis::get('user:'.$user->id);

        $data = $this->unserialize($data);

        return [...($data ?: [])];
    }

    protected function unserialize($value)
    {
        return is_numeric($value) ? $value : unserialize($value);
    }
}
