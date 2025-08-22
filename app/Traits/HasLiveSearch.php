<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

trait HasLiveSearch
{
    /**
     * Render search results as JSON response
     *
     * @param mixed $items
     * @param string $partialView
     * @param array $extraData
     * @return JsonResponse
     */
    protected function renderSearchResponse($items, string $partialView, array $extraData = []): JsonResponse
    {
        $data = [
            'success' => true,
            'html' => view($partialView, array_merge(['items' => $items], $extraData))->render(),
        ];

        // Add pagination info if items support it
        if (method_exists($items, 'currentPage')) {
            $data['pagination'] = [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'has_more' => $items->hasMorePages(),
            ];
        }

        return response()->json($data);
    }

    /**
     * Apply search filters to query
     *
     * @param mixed $query
     * @param Request $request
     * @param array $searchFields
     * @param string $searchParam
     * @return mixed
     */
    protected function applySearchFilters($query, Request $request, array $searchFields = [], string $searchParam = 'search')
    {
        if ($request->filled($searchParam)) {
            $searchTerm = $request->input($searchParam);

            $query->where(function ($q) use ($searchFields, $searchTerm) {
                foreach ($searchFields as $field) {
                    if (str_contains($field, '.')) {
                        // Handle relationship searches
                        [$relation, $relationField] = explode('.', $field, 2);
                        $q->orWhereHas($relation, function ($relationQuery) use ($relationField, $searchTerm) {
                            $relationQuery->where($relationField, 'like', "%{$searchTerm}%");
                        });
                    } else {
                        // Handle direct field searches
                        $q->orWhere($field, 'like', "%{$searchTerm}%");
                    }
                }
            });
        }

        return $query;
    }

    /**
     * Apply common filters to query
     *
     * @param mixed $query
     * @param Request $request
     * @param array $filters
     * @return mixed
     */
    protected function applyCommonFilters($query, Request $request, array $filters = [])
    {
        foreach ($filters as $param => $field) {
            if ($request->filled($param)) {
                $value = $request->input($param);

                if (is_array($field)) {
                    // Handle complex filters
                    if (isset($field['type']) && $field['type'] === 'relationship') {
                        $query->whereHas($field['relation'], function ($q) use ($field, $value) {
                            $q->where($field['field'], $value);
                        });
                    } elseif (isset($field['type']) && $field['type'] === 'boolean') {
                        $query->where($field['field'], (bool) $value);
                    }
                } else {
                    // Simple field filter
                    $query->where($field, $value);
                }
            }
        }

        return $query;
    }

    /**
     * Get search configuration for a specific model
     *
     * @return array
     */
    protected function getSearchConfig(): array
    {
        return [
            'fields' => [], // Fields to search in
            'filters' => [], // Available filters
            'relations' => [], // Relations to eager load
            'per_page' => 15, // Items per page
        ];
    }

    /**
     * Handle live search request
     *
     * @param Request $request
     * @param mixed $baseQuery
     * @param string $partialView
     * @return JsonResponse
     */
    protected function handleLiveSearch(Request $request, $baseQuery, string $partialView): JsonResponse
    {
        $config = $this->getSearchConfig();

        // Apply eager loading
        if (!empty($config['relations'])) {
            $baseQuery = $baseQuery->with($config['relations']);
        }

        // Apply search filters
        if (!empty($config['fields'])) {
            $baseQuery = $this->applySearchFilters($baseQuery, $request, $config['fields']);
        }

        // Apply common filters
        if (!empty($config['filters'])) {
            $baseQuery = $this->applyCommonFilters($baseQuery, $request, $config['filters']);
        }

        // Get paginated results
        $items = $baseQuery->latest()->paginate($config['per_page'])->withQueryString();

        return $this->renderSearchResponse($items, $partialView, []);
    }

    /**
     * Build search response with error handling
     *
     * @param callable $searchCallback
     * @return JsonResponse
     */
    protected function buildSearchResponse(callable $searchCallback): JsonResponse
    {
        try {
            return $searchCallback();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка поиска: ' . $e->getMessage(),
                'html' => view('partials.search-error')->render()
            ], 500);
        }
    }

    /**
     * Handle empty search results
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function emptySearchResponse(string $message = 'Ничего не найдено'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'html' => view('partials.empty-search', compact('message'))->render(),
            'pagination' => [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => 0,
                'total' => 0,
                'has_more' => false,
            ]
        ]);
    }

    /**
     * Sanitize search input
     *
     * @param string $input
     * @return string
     */
    protected function sanitizeSearchInput(string $input): string
    {
        // Remove special characters that might cause issues
        $input = trim($input);
        $input = preg_replace('/[<>"\']/', '', $input);

        return $input;
    }

    /**
     * Get search suggestions based on input
     *
     * @param Request $request
     * @param mixed $baseQuery
     * @param array $suggestionFields
     * @param int $limit
     * @return JsonResponse
     */
    protected function getSearchSuggestions(Request $request, $baseQuery, array $suggestionFields, int $limit = 5): JsonResponse
    {
        if (!$request->filled('q') || strlen($request->input('q')) < 2) {
            return response()->json(['suggestions' => []]);
        }

        $searchTerm = $this->sanitizeSearchInput($request->input('q'));
        $suggestions = [];

        foreach ($suggestionFields as $field) {
            $results = $baseQuery->where($field, 'like', "%{$searchTerm}%")
                ->distinct()
                ->limit($limit)
                ->pluck($field)
                ->toArray();

            $suggestions = array_merge($suggestions, $results);
        }

        // Remove duplicates and limit results
        $suggestions = array_unique($suggestions);
        $suggestions = array_slice($suggestions, 0, $limit);

        return response()->json(['suggestions' => array_values($suggestions)]);
    }
}
