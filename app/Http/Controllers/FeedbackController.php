<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Feedback;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeedbackController extends Controller
{
    public function index()
    {
        $result = Feedback::all()->sortByDesc('date');
        return ResponseHelper::success($result);
    }

    public function userFeedbacks()
    {
        $user = auth('sanctum')->user();
        $result = Feedback::query()
            ->where('user_id', $user->id)
            ->get()->sortByDesc('date');
        return ResponseHelper::success($result);
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $result = Feedback::query()
                ->create([
                    'user_id' => auth('sanctum')->id() ? : $request->user_id,
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
