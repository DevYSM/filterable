<?php

namespace YSM\Filterable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Abstract base class for filtering Eloquent queries
 *
 * @package YSM\Filterable
 */
abstract class Filterable
{
    
    /** @var Request The current HTTP request instance */
    protected Request $request;

    /** @var Builder The Eloquent query builder instance */
    protected Builder $builder;

    /** @var array Track all applied filters and their values */
    protected array $appliedFilters = [];

    /** @var array Filters to apply automatically */
    protected array $autoApplyFilters = [];

    /** @var array aliases request parameters to filter methods */
    protected array $aliases = [];

    /** @var array Whitelist of allowed filters */
    protected array $allowedFilters = [];

    /** @var array Blacklist of forbidden filters */
    protected array $forbiddenFilters = [];

    /** @var array Default filter values */
    protected array $defaults = [];

    /**
     * Initialize a new filter instance
     */
    public function __construct()
    {
        $this->request = app('request');
    }


    /**
     * Create a new filter instance (fluent interface)
     *
     * @return static
     */
    public static function make(): static
    {
        return new static();
    }

    /**
     * Apply filters to the query builder
     *
     * @param Builder $builder The Eloquent query builder instance
     *
     * @return Builder
     */
    public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;
        $this->appliedFilters = [];

        $this->applyAutoFilters();
        $this->applyRequestFilters();

        return $this->builder;
    }

    /**
     * Apply all auto-apply filters
     */
    protected function applyAutoFilters(): void
    {
        foreach ($this->autoApplyFilters as $filter) {
            if (method_exists($this, $filter)) {
                $this->applyFilter($filter);
            }
        }
    }

    /**
     * Execute a filter method
     *
     * @param string     $filter The filter method name
     * @param mixed|null $value  The filter value
     *
     * @throws InvalidArgumentException
     */
    protected function applyFilter(string $filter, mixed $value = null): void
    {
        if (!method_exists($this, $filter)) {
            throw new InvalidArgumentException("Filter method {$filter} does not exist");
        }

        $value = $value ?? $this->getFilterValue($filter);
        $this->appliedFilters[$filter] = $value;

        call_user_func([$this, $filter], $value);
    }

    /**
     * Get the value for a filter from request or defaults
     *
     * @param string $filter  The filter method name
     * @param mixed  $default Default value if none found
     *
     * @return mixed
     */
    protected function getFilterValue(string $filter, $default = null): mixed
    {
        $param = array_search($filter, $this->aliases) ?: $filter;
        return $this->request->input($param, $this->defaults[$filter] ?? $default);
    }

    /**
     * Apply filters from request parameters
     */
    protected function applyRequestFilters(): void
    {
        foreach ($this->request->all() as $param => $value) {
            $filter = $this->getFilterMethod($param);

            if ($this->shouldApplyFilter($filter)) {
                $this->applyFilter($filter, $value);
            }
        }
    }

    /**
     * Get the filter method name for a request parameter
     *
     * @param string $param The request parameter name
     *
     * @return string
     */
    protected function getFilterMethod(string $param): string
    {
        return $this->aliases[$param] ?? $param;
    }

    /**
     * Determine if a filter should be applied
     *
     * @param string $filter The filter method name
     *
     * @return bool
     */
    protected function shouldApplyFilter(string $filter): bool
    {
        return method_exists($this, $filter) &&
            (empty($this->allowedFilters) || in_array($filter, $this->allowedFilters)) &&
            !in_array($filter, $this->forbiddenFilters);
    }

    /**
     * Set a custom request instance
     *
     * @param Request $request
     *
     * @return $this
     */
    public function setRequest(Request $request): self
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Get all applied filters and their values
     *
     * @return array
     */
    public function getAppliedFilters(): array
    {
        return $this->appliedFilters;
    }

    /**
     * Set filters to apply automatically
     *
     * @param array $filters
     *
     * @return $this
     */
    public function autoApply(array $filters): self
    {
        $this->autoApplyFilters = $filters;
        return $this;
    }

    /**
     * aliases request parameters to filter methods
     *
     * @param array $aliases ['request_param' => 'filterMethod']
     *
     * @return $this
     */
    public function aliases(array $aliases): self
    {
        $this->aliases = $aliases;
        return $this;
    }

    /**
     * Whitelist specific filters
     *
     * @param array $filters
     *
     * @return $this
     */
    public function only(array $filters): self
    {
        $this->allowedFilters = $filters;
        return $this;
    }

    /**
     * Blacklist specific filters
     *
     * @param array $filters
     *
     * @return $this
     */
    public function except(array $filters): self
    {
        $this->forbiddenFilters = $filters;
        return $this;
    }

    /**
     * Set default filter values
     *
     * @param array $defaults
     *
     * @return $this
     */
    public function defaults(array $defaults): self
    {
        $this->defaults = $defaults;
        return $this;
    }

    /**
     * Get all configured filters and their settings
     *
     * @return array
     */
    public function getConfiguredFilters(): array
    {
        return [
            'autoApply' => $this->autoApplyFilters,
            'aliases' => $this->aliases,
            'allowed' => $this->allowedFilters,
            'forbidden' => $this->forbiddenFilters,
            'defaults' => $this->defaults
        ];
    }


}
