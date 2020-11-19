<?php

namespace App\Models\Traits;

use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedFilter;
use Gate;

trait AuthorizedQuery
{
    /************************************************************
     * Public interface
     ***********************************************************/
    /**
     * List of method prefix name for the attribute query ability in the model policy.
     */
    protected static $queryWithAbilityMethod    = "with";
    protected static $querySelectAbilityMethod  = "select";
    protected static $queryFilterAbilityMethod  = "filter";
    protected static $querySortAbilityMethod    = "sort";
    protected static $queryIncludeAbilityMethod = "include";
    protected static $queryAppendAbilityMethod  = "append";

    /**
     * List of supported relationships that the entity can be returned with.
     * 
     * Its counterpart `getAuthorizedQueryWith()` validates which of these 
     * relationships are visible for the current user.
     *
     * @return array
     */
    public function getWith() {
        return [];
    }

    /**
     * List of attributes for which sorting is supported through queries.
     * 
     * Its counterpart `getAuthorizedQuerySorts()` validates which of these 
     * attributes are visible for the current user.
     * 
     * Example: ?sort=-name
     * 
     * @return array
     */
    public function getQuerySorts() {
        return [];
    }

    /**
     * List of attributes for which filtering is supported through queries.
     * 
     * Its counterpart `getAuthorizedQueryFilters()` validates which of these 
     * attributes are visible for the current user.
     *
     * Example: ?filter[name]=john&filter[email]=gmail
     * 
     * @return array
     */
    public function getQueryFilters() {
        return [];
    }

    /**
     * List of attributes for which selecting is supported through queries.
     * 
     * Its counterpart `getAuthorizedQuerySelects()` validates which of these 
     * attributes are visible for the current user.
     *
     * Example: ?fields[users]=id,name
     * 
     * @return array
     */
    public function getQuerySelects() {
        return [];
    }

    /**
     * List of relations for which relationship (eager) loading is supported through queries.
     * 
     * Its counterpart `getAuthorizedQueryIncludes()` validates which of these 
     * attributes are visible for the current user.
     *
     * Example: ?include=posts
     * 
     * @return array
     */
    public function getQueryIncludes() {
        return [];
    }

    /**
     * List of custom attributes for which appending is supported through queries.
     * 
     * Its counterpart `getAuthorizedQueryAppends()` validates which of these 
     * attributes are visible for the current user.
     * 
     * Implementation: public function getFullnameAttribute() { return "{$this->firstname} {$this->lastname"; }
     * Example: ?append=fullname
     * 
     * @return array
     */
    public function getQueryAppends() {
        return [];
    }
    
    /************************************************************
     * Authorized queries from policy
     ***********************************************************/
    /**
     * Get list of attributes for which the sorting is allowed for current user.
     * 
     * @var array
     */
    public function getAuthorizedQuerySorts()
    {
        return [$this->primaryKey] + $this->getQueryableAttributesFor(
            "sort", 
            $this->getQuerySorts()
        );
    }

    /**
     * Get list of attributes for which the filtering is allowed for current user.
     *
     * @return array
     */
    public function getAuthorizedQueryFilters()
    {
        return [AllowedFilter::exact($this->primaryKey)] + $this->getQueryableAttributesFor(
            "filter", 
            $this->getQueryFilters()
        );
    }

    /**
     * Get list of attributes for which the selecting is allowed for current user.
     *
     * @return array
     */
    public function getAuthorizedQuerySelects()
    {
        return [$this->primaryKey] + $this->getQueryableAttributesFor(
            "select", 
            $this->getQuerySelects()
        );
    }

    /**
     * Get list of relationships for which eager loading through queries is allowed for current user.
     *
     * @return array
     */
    public function getAuthorizedQueryIncludes()
    {
        return $this->getQueryableAttributesFor(
            "include", 
            $this->getQueryIncludes()
        );
    }

    /**
     * Get list of custom attributes which are allowed for current user. 
     *
     * @return array
     */
    public function getAuthorizedQueryAppends()
    {
        return $this->getQueryableAttributesFor(
            "append", 
            $this->getQueryAppends()
        );
    }

    /**
     * Get list of eager loading relationships which are allowed for current user.
     *
     * @return array
     */
    public function getAuthorizedWith()
    {
        return $this->getQueryableAttributesFor(
            "with", 
            $this->getWith()
        );
    }

    /************************************************************
     * Authorization policy
     ***********************************************************/
     /**
     * Defines a list of query attributes to method mappings
     * used to find proper method in model policy.
     * 
     * @return array
     */
    protected static function queryMappings() {
        return [
            "with"    => static::$queryWithAbilityMethod,
            "sort"    => static::$querySortAbilityMethod,
            "filter"  => static::$queryFilterAbilityMethod,
            "select"  => static::$querySelectAbilityMethod,
            "include" => static::$queryIncludeAbilityMethod,
            "append"  => static::$queryAppendAbilityMethod,
        ];
    }

     /**
     * Get the method name for the attribute query ability in the model policy.
     *
     * @param  string  $type
     * @param  string  $attribute
     * @return string
     */
    public function getAttributeQueryAbilityMethodFor($type, $attribute)
    {
        $res = static::queryMappings()[$type] . Str::studly($attribute);
        return $res;
    }

    /**
     * Get all queryable attributes of specific type for current user.
     *
     * @param  string  $type
     * @param  array   $fields
     * @return array
     */
    public function getQueryableAttributesFor($type, $fields)
    {
        $policy = Gate::getPolicyFor(static::class);

        if (! $policy) {
            return $fields;
        }

        return AttributeGate::getQueryable($this, $type, $fields, $policy);
    }
}