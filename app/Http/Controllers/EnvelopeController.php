<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\StoreEnvelopeRequest;
use App\Services\TripService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnvelopeController extends Controller
{
    protected TripService $tripService;

    public function __construct(TripService $tripService)
    {
        $this->tripService = $tripService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $envelopes = $this->tripService->getEnvelopOrders();
        return ResponseHelper::success($envelopes);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function approve(Request $request): JsonResponse
    {
        $envelope = $this->tripService->approveEnvelopOrder($request);
        return ResponseHelper::success($envelope);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEnvelopeRequest $request): JsonResponse
    {
        $envelope = $this->tripService->createEnvelopOrder($request);
        return ResponseHelper::success($envelope);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
