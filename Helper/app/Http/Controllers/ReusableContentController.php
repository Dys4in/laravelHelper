<?php

namespace App\Http\Controllers;

use App\Models\ReusableContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReusableContentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(ReusableContent::query()
            ->where('user_id', $request->user()->id)
            ->latest('updated_at')->limit(100)
            ->get(['id', 'title', 'content']));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'content' => ['required', 'string', 'max:20000'],
        ]);

        abort_if(
            ReusableContent::where('user_id', $request->user()->id)->count() >= 100,
            422,
            'Límite de 100 fragmentos alcanzado.'
        );

        $item = ReusableContent::create($data + ['user_id' => $request->user()->id]);

        return response()->json($item->only(['id', 'title', 'content']), 201);
    }

    public function destroy(Request $request, ReusableContent $reusableContent): JsonResponse
    {
        abort_unless($reusableContent->user_id === $request->user()->id, 403);
        $reusableContent->delete();

        return response()->json(['deleted' => true]);
    }
}
