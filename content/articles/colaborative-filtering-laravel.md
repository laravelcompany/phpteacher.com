---
title: Laravel examples based on the collaborative filtering
publishDate: 2025-02-09 00:00:00
description: From Code Review to Async and Beyond, PHP developers can continue to improve their skills and build more effective and resilient applications.
image: /assets/services/security.svg
tags:
  - magazines
  - php
  - february
  - 2025
---


# What is Collaborative Filtering?

Collaborative filtering is a widely used technique in recommendation systems that predicts user preferences based on similarities between users or items. It helps platforms like Netflix, Amazon, and Spotify suggest content tailored to individual tastes.

At its core, collaborative filtering is a mathematical method that estimates how a user would rate an item by analyzing patterns in user behavior. For instance, to predict User A’s rating for a particular item, we first measure their similarity to other users. We then use the preferences of the most similar users to estimate User A’s rating.


## Collaborative filtering classification
### User-based collaborative filtering
Generally speaking, we identify similar users through their preference characteristics , and then recommend similar users who have the same behavior towards an item (such as purchasing the item) to target customers.

As shown in the figure below: User A likes item 1 and item 3, and user C also likes item 1 and item 3. So we infer that user A and user C have a great similarity (they may have the same preferences). A has no related behavior for item 4, so item 4, which user C likes, is recommended to user A.

```
          +--------------------+
          |   Target User (A)  |
          +--------------------+
                    |
      ---------------------------------
      |               |               |
+----------------+ +----------------+ +----------------+
| Similar User B | | Similar User C | | Similar User D |
+----------------+ +----------------+ +----------------+
      |               |               |
      |               |               |
+------+  +------+  +------+  +------+
| Item 1|  |Item 2|  |Item 3|  |Item 4|
+------+  +------+  +------+  +------+
      |      |        |          |
      |      |        |          |
      |      |    (Not owned by A)
      |      |---------------------> **Recommended to User A**
```


## Understanding Collaborative Filtering

Collaborative filtering is a technique that makes predictions about a user's interests by collecting preferences from many users. The core idea is simple: if user A and user B have similar reading preferences, and user A likes a book that user B hasn't read yet, that book might be a good recommendation for user B.

## The Laravel Implementation

Our implementation uses the Pearson correlation coefficient to measure similarity between users. This statistical measure helps us identify users with similar reading tastes while accounting for different rating scales (some users might be generally more generous with their ratings than others).

### Key Components

1. **Database Structure**
   We organize our data using three main models: Users, Books, and Ratings. The Ratings model serves as a pivot table, connecting users with books and storing their ratings. This structure allows for efficient querying of user preferences and book information.

2. **Similarity Calculation**
   The Pearson correlation coefficient calculation considers the following:
   - Common books rated by both users
   - Each user's average rating (to account for different rating scales)
   - Deviations from these averages

3. **Recommendation Generation**
   The recommendation process involves several steps:
   - Finding similar users
   - Identifying books rated by similar users but not by the target user
   - Calculating weighted predictions based on user similarity
   - Sorting and returning the top recommendations

### Performance Considerations

To ensure optimal performance, our implementation:
- Uses database joins instead of multiple queries
- Implements caching for frequently accessed data
- Processes recommendations in chunks for large datasets
- Uses eager loading to prevent N+1 query problems

### Error Handling and Edge Cases

The system handles various edge cases gracefully:
- Users with no ratings
- No common books between users
- Division by zero in correlation calculations
- Missing or invalid data

## Real-World Applications

This recommendation system can be extended for various use cases:
- Online bookstores
- Library management systems
- Educational platforms
- Content discovery systems

The same principles can be applied to recommend different types of content, such as articles, courses, or products.

## Future Improvements

Several enhancements could make the system more robust:
1. **Item-Based Filtering**: Implementing book-to-book similarity calculations
2. **Hybrid Approach**: Combining collaborative filtering with content-based recommendations
3. **Machine Learning**: Incorporating more advanced prediction algorithms
4. **Real-time Updates**: Processing new ratings and updating recommendations in real-time
5. **A/B Testing**: Implementing different recommendation strategies to optimize effectiveness

## Conclusion

Building a recommendation system with Laravel demonstrates the framework's versatility in handling complex data processing tasks. By leveraging Laravel's elegant syntax and robust features, we can create sophisticated recommendation systems that enhance user experience and drive engagement.

The implementation we've discussed provides a solid foundation for building more complex recommendation systems. Whether you're building an e-commerce platform, content site, or any application where personalized recommendations add value, these principles can help you create more engaging user experiences.

Remember that recommendation systems are iterative - they improve with more data and continuous refinement. Start with this basic implementation and enhance it based on your specific needs and user feedback.


```php
// app/Models/Book.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $fillable = ['title', 'author', 'description'];

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }
}

// app/Models/Rating.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    protected $fillable = ['user_id', 'book_id', 'rating'];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

// app/Services/RecommendationService.php
<?php

namespace App\Services;

use App\Models\Rating;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RecommendationService
{
    /**
     * Calculate Pearson correlation coefficient between two users
     */
    public function calculatePearsonSimilarity(User $user1, User $user2): float
    {
        // Get common books rated by both users
        $commonRatings = DB::table('ratings as r1')
            ->join('ratings as r2', 'r1.book_id', '=', 'r2.book_id')
            ->where('r1.user_id', $user1->id)
            ->where('r2.user_id', $user2->id)
            ->select('r1.rating as rating1', 'r2.rating as rating2')
            ->get();

        if ($commonRatings->isEmpty()) {
            return 0;
        }

        // Calculate means
        $mean1 = $commonRatings->avg('rating1');
        $mean2 = $commonRatings->avg('rating2');

        // Calculate numerator and denominators for Pearson correlation
        $numerator = 0;
        $denominator1 = 0;
        $denominator2 = 0;

        foreach ($commonRatings as $rating) {
            $diff1 = $rating->rating1 - $mean1;
            $diff2 = $rating->rating2 - $mean2;
            
            $numerator += $diff1 * $diff2;
            $denominator1 += $diff1 * $diff1;
            $denominator2 += $diff2 * $diff2;
        }

        if ($denominator1 == 0 || $denominator2 == 0) {
            return 0;
        }

        return $numerator / sqrt($denominator1 * $denominator2);
    }

    /**
     * Get book recommendations for a user
     */
    public function getRecommendations(User $user, int $limit = 5): array
    {
        // Get all users except current user
        $otherUsers = User::where('id', '!=', $user->id)->get();
        
        // Calculate similarities with other users
        $similarities = [];
        foreach ($otherUsers as $otherUser) {
            $similarity = $this->calculatePearsonSimilarity($user, $otherUser);
            if ($similarity > 0) {
                $similarities[$otherUser->id] = $similarity;
            }
        }

        // Get books rated by similar users but not by current user
        $userRatedBooks = $user->ratings()->pluck('book_id')->toArray();
        
        $recommendations = [];
        foreach ($similarities as $similarUserId => $similarity) {
            $similarUserRatings = Rating::where('user_id', $similarUserId)
                ->whereNotIn('book_id', $userRatedBooks)
                ->get();

            foreach ($similarUserRatings as $rating) {
                if (!isset($recommendations[$rating->book_id])) {
                    $recommendations[$rating->book_id] = [
                        'weighted_sum' => 0,
                        'similarity_sum' => 0
                    ];
                }
                
                $recommendations[$rating->book_id]['weighted_sum'] += $rating->rating * $similarity;
                $recommendations[$rating->book_id]['similarity_sum'] += $similarity;
            }
        }

        // Calculate predicted ratings
        $predictedRatings = [];
        foreach ($recommendations as $bookId => $data) {
            if ($data['similarity_sum'] > 0) {
                $predictedRatings[$bookId] = $data['weighted_sum'] / $data['similarity_sum'];
            }
        }

        // Sort by predicted rating and get top recommendations
        arsort($predictedRatings);
        return array_slice($predictedRatings, 0, $limit, true);
    }
}

// app/Http/Controllers/RecommendationController.php
<?php

namespace App\Http\Controllers;

use App\Services\RecommendationService;
use App\Models\Book;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    private $recommendationService;

    public function __construct(RecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    public function getRecommendations(Request $request)
    {
        $user = $request->user();
        $recommendedBookIds = $this->recommendationService->getRecommendations($user);
        
        $books = Book::whereIn('id', array_keys($recommendedBookIds))
            ->get()
            ->map(function ($book) use ($recommendedBookIds) {
                $book->predicted_rating = round($recommendedBookIds[$book->id], 2);
                return $book;
            });

        return response()->json([
            'recommendations' => $books
        ]);
    }
}
```