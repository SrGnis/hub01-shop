<?php

namespace App\Traits;

use Str;

/**
 * Trait HasUniqueSlug
 *
 * This trait provides functionality to generate unique slugs for Eloquent models.
 * It ensures that the slug is unique by appending a counter if necessary.
 *
 * A aditonal method can be added to the model to customize the slug generation based in information of the model.
 * The method should be named `generateSlug` and should return a string.
 *
 */
trait HasUniqueSlug{

    protected static function bootHasUniqueSlug()
    {
        static::saving(function ($model) {
            if (empty($model->slug)) {
                // Check if the model has a custom generateSlug method
                if(method_exists($model, 'generateSlug')){
                    $model->slug = $model->generateSlug($model);
                }else{
                    $model->slug = static::createSlug($model->name);
                }
            }
        });
    }

    /**
     * Create a unique slug for a model class based on the name field.
     *
     * @param string $name
     * @param string|null $prefix
     * @param int $maxAttempts
     * @return string
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public static function createSlug(string $name, ?string $prefix = null, int $maxAttempts = 10): string
    {

        if (empty($name)) {
            throw new \InvalidArgumentException("Name cannot be empty.");
        }

        $baseSlug = Str::of($name)
            ->slug(dictionary: ['@' => 'at'])
            ->limit(100);

        $slug = $prefix ? "{$prefix}_{$baseSlug}" : $baseSlug;
        $count = 1;

        while (static::where('slug', $slug)->exists() && $count < $maxAttempts) {
            $slug = $prefix ? "{$prefix}_{$baseSlug}-{$count}" : "{$baseSlug}-{$count}";
            $count++;
        }

        if ($count >= $maxAttempts) {
            throw new \RuntimeException("Failed to generate a unique slug after {$maxAttempts} attempts.");
        }

        return $slug;
    }
}
