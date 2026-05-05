<?php

namespace Javaabu\Cms\PostTypes;

class PostTypes
{
    /**
     * @param array<int|string, array|PostType> $postTypes
     * @return array<string, array>
     */
    public static function normalize(array $postTypes): array
    {
        $normalized = [];

        foreach ($postTypes as $slug => $postType) {
            if ($postType instanceof PostType) {
                $postTypeArray = $postType->toArray();
                $normalized[$postTypeArray['slug']] = $postTypeArray;
                continue;
            }

            if (is_array($postType)) {
                $resolvedSlug = is_string($slug) ? $slug : ($postType['slug'] ?? null);

                if (! $resolvedSlug) {
                    continue;
                }

                $normalized[$resolvedSlug] = array_merge($postType, ['slug' => $resolvedSlug]);
            }
        }

        return $normalized;
    }
}
