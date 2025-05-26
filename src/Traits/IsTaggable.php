<?php

namespace Javaabu\Cms\Traits;

//use App\Helpers\Translation\DbTranslatable\DbTranslatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Javaabu\Cms\Models\Tag;

trait IsTaggable
{
    /**
     * Boot function from laravel.
     */
    public static function bootIsTaggable()
    {
        static::deleted(function ($model) {
            // if the model doesn't support soft deletes
            // or if it is being force deleted
            if (! method_exists($model, 'isForceDeleting') || $model->isForceDeleting()) {
                DB::table('tag_model')
                    ->where('model_id', $model->id)
                    ->where('model_type', $model->type)
                    ->delete();
            }
        });
    }

    /**
     * Belongs to tag
     *
     * @param               $query
     * @param Tag | array $tag_ids
     * @return mixed
     */
    public function scopeBelongsToTag($query, $tag_ids): mixed
    {
        $tag_ids = Arr::wrap($tag_ids);

        if (empty($tag_ids)) {
            return $query;
        }

        // Get goods
        return $query->whereHas('tagWords', function ($query) use ($tag_ids) {
            $query->whereIn('tag_id', $tag_ids);
        });
    }

    /**
     * Has any tag
     *
     * @param Tag | array $tag_ids
     * @return boolean
     */
    public function hasAnyTag($tag_ids)
    {
        $tag_ids = Arr::wrap($tag_ids);

        if (empty($tag_ids)) {
            return false;
        }

        return $this->tagWords
                ->plucK('tag_id')
                ->intersect(collect($tag_ids))
                ->count() > 0;
    }

    /**
     * Join with tags
     *
     * @param $query
     * @return mixed
     */
    public function scopeJoinTagsPivot($query)
    {
        $model = $this;
        return $query->join('tag_model as t_m', function ($join) use ($model) {
            $join->on($model->getTable() . '.id', '=', 't_m.model_id')
                ->where('t_m.model_type', '=', $model->getMorphClass());
        });
    }

    /**
     * Get similar
     *
     * @param string $relation
     * @return Builder
     */
    public function similarByTag($relation = 'tagWords')
    {
        if (empty($relation)) {
            $relation = $this->getTagsRelationName();
        }

        return static::where('id', '!=', $this->id)
            ->similarToTags(
                $this->{$relation}->pluck('tag_id')->all()
            );
    }

    /**
     * Get similar
     *
     * @param              $query
     * @param array|Post $tags
     * @return Builder
     * @internal param string $relation
     */
    public function scopeSimilarToTags($query, $tags = []): Builder
    {
        if ($tags instanceof Model) {
            $query->where($this->getTable() . '.id', '!=', $tags->id);
            $tags = $tags->tagWords()->pluck('tag_id')->all();
        }

        return $query->joinTagsPivot()
            ->selectRaw('count(t_m.tag_id) as tag_similarity, ' . $this->getTable() . '.id' . ',' . $this->getTable() . '.title' . ',' . $this->getTable() . '.slug' . ',' . $this->getTable() . '.published_at')
            ->groupBy('t_m.model_id', $this->getTable() . '.id', $this->getTable() . '.title', $this->getTable() . '.slug', $this->getTable() . '.published_at')
            ->whereIn('t_m.tag_id', $tags);
    }

    /**
     * Belongs to tags
     *
     * @return morphToMany
     */
    public function tagWords(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'model', 'tag_model');
    }

    /**
     * Get the tag links attribute
     *
     * @return string
     */
    public function getTagLinksAttribute()
    {
        return $this->getTagLinks();
    }

    /**
     * Get the tag links attribute
     *
     * @return string
     */
    public function getTagLinks()
    {
        $html = '';

        foreach ($this->tagWords as $tag) {
            $html .= $tag->admin_link . ', ';
        }

        return rtrim($html, ', ');
    }

    /**
     * Sync the tags
     *
     * @param $tag_names
     */
    public function syncTags($tag_names)
    {
        $tag_names = Arr::wrap($tag_names);

        foreach ($tag_names as $i => $tag_name) {
            $tag_names[$i] = Tag::sanitizeName($tag_name); // remove multiple spaces
        }

        // get the existing tags
        $existing_tags = Tag::whereIn('name', $tag_names)->get();

        $tag_ids = $existing_tags->pluck('id')->all();

        // find the missing tags
        foreach ($tag_names as $name) {
            if (! $existing_tags->contains('name', $name)) {
                $new_tag = Tag::firstOrCreate(compact('name'));
                $tag_ids[] = $new_tag->id;
            }
        }

        // sync
        $this->tagWords()->sync($tag_ids);
    }
}
