<?php

namespace App\Services;

use Validator;
use Config;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Dingo\Api\Exception\StoreResourceFailedException;
use App\Models\RestfulModel;

/**
 * This class contains logic for processing restful requests
 *
 * Class RestfulService
 */
class RestfulService
{
    /**
     * @var string $model The Model Class name
     */
    protected $model = null;

    /**
     * RestfulService constructor.
     *
     * @param RestfulModel|null $model The model this service will be concerned with
     */
    public function __construct($model = null)
    {
        $this->model = $model;
    }

    /**
     * Set model to be used in the service
     *
     * @param string|null $model
     * @return $this
     */
    public function setModel($model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Deletes resources of the given model and id(s)
     *
     * @param $model string Model class name
     * @param $id string|array The ID(s) of the models to remove
     * @return mixed
     */
    public function delete($model, $id)
    {
        $deletedCount = $model::destroy($id);

        if ($deletedCount < 1) {
            throw new NotFoundHttpException('Could not find a resource with that ID to delete');
        }

        return $deletedCount;
    }

    /**
     * Create model in the database
     *
     * @param $model
     * @param $data
     * @return mixed
     */
    public function persistResource(RestfulModel $resource)
    {
        try {
            $resource->save();
        } catch (\Exception $e) {
            // Check for QueryException - if so, we may want to display a more meaningful message, or help with
            // development debugging
            if ($e instanceof QueryException) {
                if (stristr($e->getMessage(), 'duplicate')) {
                    throw new ConflictHttpException('The resource already exists: ' . class_basename($resource));
                } elseif (Config::get('api.debug') === true) {
                    throw $e;
                }
            }

            // Default HTTP exception to use for storage errors
            $errorMessage = 'Unexpected error trying to store this resource.';

            if (Config::get('api.debug') === true) {
                $errorMessage .= ' ' . $e->getMessage();
            }

            throw new UnprocessableEntityHttpException($errorMessage);
        }

        return $resource;
    }

    /**
     * Validates a given resource (Restful Model) against a given data set, and throws an API exception on failure
     *
     * @param RestfulModel $resource
     * @param array $data
     * @throws StoreResourceFailedException
     */
    public function validateResource($resource, array $data = null)
    {
        // If no data is provided, validate the resource against it's present attributes
        if (is_null($data)) {
            $data = $resource->getAttributes();
        }

        $validator = Validator::make($data, $resource->getValidationRules(), $resource->getValidationMessages());

        if ($validator->fails()) {
            throw new StoreResourceFailedException('Could not create resource.', $validator->errors());
        }
    }

    /**
     * Validates a given resource (Restful Model) against a given data set in the update context - ie. validating
     * only the fields updated in the provided data set, and throws an API exception on failure
     *
     * @param RestfulModel $resource model resource
     * @param array $data Data we are validating against
     * @throws StoreResourceFailedException
     */
    public function validateResourceUpdate($resource, array $data)
    {
        $validator = Validator::make($data, $this->getRelevantValidationRules($resource, $data), $resource->getValidationMessages());

        if ($validator->fails()) {
            throw new StoreResourceFailedException('Could not update resource with ID "'.$resource->getKey().'".', $validator->errors());
        }
    }

    /**
     * For a given RestfulModel resource and request's data, get the relevant validation rules for updating that resource
     *
     * @param RestfulModel $resource model resource
     * @param array $data Data we are validating against
     * @return array The relevant rules
     */
    public function getRelevantValidationRules($resource, array $data)
    {
        $dataKeys = array_keys($data);
        $rules = $resource->getValidationRulesUpdating();

        $relevantRules = [];
        foreach ($rules as $attribute => $rule) {
            // We only want to compare with the attribute name portion of the rule key (example: only attribute in
            //    attribute.other.irrelevant.items => required)
            // If it matches a key in the data array, then the rule is relevant
            if (in_array(Str::before($attribute, '.'), $dataKeys)) {
                $relevantRules[$attribute] = $rule;
            }
        }

        return $relevantRules;
    }
}
