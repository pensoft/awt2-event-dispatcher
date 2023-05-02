<?php
namespace App\Api\V1\Controllers;

use App\DTOs\CustomJobData;
use App\DTOs\RequestData;
use App\Exceptions\DispatchMethodNotExist;
use Dingo\Api\Http\Request;
use Dingo\Api\Routing\Helpers;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;

class BaseController extends ApiBaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, Helpers;


    /**
     * @param Request $request
     * @return mixed|null
     */
    public function __invoke(Request $request): mixed
    {
        $this->shouldHaveAction($request);

        $requestData = $this->extractData($request);
        $action = Arr::get($requestData->data, 'action');

        return call_user_func_array([$this, $action], [$requestData]);
    }

    public function __call($method, $args)
    {
        if (method_exists($this, $method)) {
            return call_user_func_array([$this, $method], $args);
        }

        throw new DispatchMethodNotExist("Target dispatch action [ {$method} ] not exist!");
    }

    /**
     * @param Request $request
     * @return void
     */
    protected function shouldHaveAction(Request $request): void
    {
        $requestData = $this->extractData($request);

        if(!Arr::has($requestData->data, 'action')){
            throw new DispatchMethodNotExist('Not dispatch method defined!');
        }
    }

    protected function createTaskPayload($data): CustomJobData
    {
        return CustomJobData::fromData($data);
    }

    /**
     * @param Request $request
     * @return RequestData
     */
    protected function extractData(Request $request): RequestData
    {
        return RequestData::fromRequest($request);
    }
}
