<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Review;

class Book extends Model
{
    use HasFactory;

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // So notice that even though the scopes are prefixed with the scope word, so method 
    // is scope and the capital letter title, you use the lowercase letter and you skip the 
    // scope prefix when calling the query scopes.
    public function scopeTitle(Builder $query, string $title): Builder|QueryBuilder
    {
        return $query->where('title', 'LIKE', '%' . $title . '%');
    }

    public function scopePopular(Builder $query, $from=null, $to=null): Builder {
         return $query->withCount([
            'reviews' => fn(Builder $q) => $this->dateRangeFilter($q, $from, $to)
        ])
        ->orderBy('reviews_count', 'desc');
    }

    public function scopeHighestRated(Builder $query, $from=null, $to=null): Builder|QueryBuilder {
        return $query->withAvg([
            'reviews' => fn(Builder $q) => $this->dateRangeFilter($q, $from, $to)
            ], 
            'rating')->orderBy('reviews_avg_rating', 'desc');
    }

    public function scopeMinReviews(Builder $query, $minReviews):  Builder|QueryBuilder {
        // you are working with the results of aggregate functions, you have to use this having clause.
        // That's the simple reason why we are using having and not where.
        return $query->having('reviews_count', '>=', $minReviews);
    }


    private function dateRangeFilter(Builder $query, $from = null, $to = null){
        if ($from && !$to) {
            $query->where('created_at', '>=', $from);
        } elseif (!$from && $to) {
            $query->where('created_at', '<=', $to);
        } elseif ($from && $to) {
            $query->whereBetween('created_at', [$from, $to]);
        }
        // we don't have to return anything because query remember that query is an object and objects are
        // passed by reference, not by copy.
        // So you will be modifying an existing object so you don't really have to return anything from this method.
    }
}