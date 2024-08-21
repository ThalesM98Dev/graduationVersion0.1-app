<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\CreateFeedbackRequest;
use App\Models\Feedback;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FeedbackController extends Controller
{
    public function index()
    {
        $result = Feedback::with('user')->all()->sortByDesc('date');
        return ResponseHelper::success($result);
    }

    public function userFeedbacks()
    {
        $user = User::findOrFail(auth('sanctum')->id());
        $result = $user->feedbacks()
            ->get()->sortByDesc('date');
        return ResponseHelper::success($result);
    }

    public function store(CreateFeedbackRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $result = Feedback::query()
                ->create([
                    'user_id' => auth('sanctum')->id() ?: $request->user_id,
                    'date' => Carbon::now()->format('Y-m-d'),
                    'content' => $request->get('content'),
                ]);
            return ResponseHelper::success($result);
        });
    }

    public function show($id)
    {
        $result = Feedback::query()->findOrFail($id);
        return ResponseHelper::success($result);
    }

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            Feedback::query()->findOrFail($id)->delete();
            return ResponseHelper::success('Feedback deleted');
        });
    }
}
