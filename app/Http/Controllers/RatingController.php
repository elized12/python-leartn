<?php

namespace App\Http\Controllers;

use App\Service\Rating\UserRatingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class RatingController extends Controller
{
    public function index(UserRatingService $ratingService): View
    {
        $leaderboard = $ratingService->leaderboard();
        $currentUserRating = $leaderboard->first(
            fn($rating) => (int) $rating->user->id === (int) Auth::id()
        );

        return view('rating.index', [
            'leaderboard' => $leaderboard,
            'topRatings' => $leaderboard->take(3),
            'otherRatings' => $leaderboard->slice(3)->values(),
            'currentUserRating' => $currentUserRating,
        ]);
    }
}
